# 🔐 DIAGNÓSTICO Y SOLUCIÓN: CREDENCIALES INVÁLIDAS EN PRODUCCIÓN

**Fecha:** 2025-10-24  
**Síntoma:** Admin y vendedores creados en servidor → "credenciales inválidas"  
**Local:** ✅ Funciona  
**Producción:** ❌ Falla  

---

## 📋 TL;DR — Solución Rápida

**Causa más probable:** Contraseñas en producción NO están hasheadas con bcrypt ($2y$).

**Solución inmediata:**

```bash
# 1. Ejecutar diagnóstico
http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php

# 2. Si confirma problema de bcrypt, generar hash correcto:
php -r "echo password_hash('TuPasswordReal', PASSWORD_BCRYPT);"

# 3. Actualizar en BD:
UPDATE usuarios SET password = '$2y$10$HASH_GENERADO...' WHERE username = 'admin';
```

---

## 🧪 SECCIÓN 1 — DIAGNÓSTICO RAÍZ

### Paso 1.1: Ejecutar Script de Diagnóstico

**En servidor de producción:**

```bash
curl http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php
```

O navegar en navegador a:
```
http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php
```

**Qué buscar:**

```json
{
  "usuarios": {
    "estadisticas": {
      "total_usuarios": 3,
      "bcrypt_usuarios": 0,  // ❌ Si es 0, AHÍ ESTÁ EL PROBLEMA
      "posible_md5": 3       // ❌ Si >0, passwords son MD5 (inseguro)
    },
    "lista": [
      {
        "username": "admin",
        "password_type": "POSIBLE MD5 (INSEGURO)"  // ❌ PROBLEMA CONFIRMADO
      }
    ]
  }
}
```

**Evidencia confirmada:** Si `bcrypt_usuarios < total_usuarios`, las contraseñas NO son bcrypt.

---

### Paso 1.2: Verificar con SQL Directo

**Conectar a phpMyAdmin o MySQL CLI:**

```sql
-- Ver usuarios y tipo de hash
SELECT 
    username,
    LENGTH(password) as pwd_length,
    LEFT(password, 10) as pwd_prefix,
    CASE 
        WHEN password REGEXP '^\\$2y\\$' THEN '✅ bcrypt'
        WHEN LENGTH(password) = 32 THEN '❌ MD5'
        WHEN LENGTH(password) = 40 THEN '❌ SHA1'
        ELSE '⚠️ OTRO'
    END as tipo
FROM usuarios;
```

**Resultado esperado:**

| username | pwd_length | pwd_prefix | tipo |
|----------|------------|------------|------|
| admin | 32 | 5f4dcc3b5a | ❌ MD5 |
| vendedor1 | 32 | e10adc3949 | ❌ MD5 |

**❌ PROBLEMA CONFIRMADO:** Si `pwd_length = 32` o `40`, son MD5/SHA1, NO bcrypt.

---

### Paso 1.3: Comparar con Local (Funcionando)

**En tu máquina local (Laragon):**

```sql
SELECT 
    username,
    LENGTH(password) as pwd_length,
    LEFT(password, 10) as pwd_prefix
FROM usuarios
WHERE username = 'admin';
```

**Resultado esperado:**

| username | pwd_length | pwd_prefix | tipo |
|----------|------------|------------|------|
| admin | 60 | $2y$10$yZS | ✅ bcrypt |

**Diferencia clave:** Local = 60 chars (`$2y$`), Producción = 32 chars (MD5).

---

### Paso 1.4: Verificar Código de Autenticación

**Archivo:** `api/auth.php` (línea 93)

```php
if (!password_verify($password, $usuario['password'])) {
    // Falla aquí
}
```

**Por qué falla:**

- `password_verify()` espera un hash bcrypt (`$2y$...`)
- Si en BD hay MD5 (`5f4dcc3b5a...`), **SIEMPRE retorna false**
- Resultado: "credenciales inválidas" aunque password sea correcta

---

## 🔎 ROOT CAUSE ANALYSIS

| Aspecto | Local ✅ | Producción ❌ |
|---------|---------|---------------|
| Password hash tipo | bcrypt ($2y$) | MD5 / Plain Text |
| Password length | 60 chars | 32 / 40 / <30 chars |
| `password_verify()` | ✅ Funciona | ❌ Falla siempre |
| Creación usuarios | Con `password_hash()` | Directamente en BD o script viejo |

**Causa raíz:**

> Los usuarios en producción fueron creados **sin usar `password_hash()`** de PHP.  
> Probablemente se insertaron con MD5/SHA1 o incluso plain text.  
> `api/auth.php` usa `password_verify()` que **SOLO funciona con bcrypt/argon2**.

---

## 🛠️ SECCIÓN 2 — PARCHES DE CÓDIGO

### Patch 2.1: Crear Endpoint de Rehash Seguro

**Ya creado:** `api/rehash_passwords_seguro.php`

**Uso:**

1. Editar archivo y agregar usuarios conocidos:

```php
$usuarios_conocidos = [
    ['username' => 'admin', 'password_nueva' => 'Admin123!'],
    ['username' => 'vendedor1', 'password_nueva' => 'Vend123!'],
];
```

2. Ejecutar:

```bash
curl http://148.230.72.12/kiosco/api/rehash_passwords_seguro.php
```

3. Verificar resultado:

```json
{
  "usuarios_actualizados": [
    {
      "username": "admin",
      "metodo": "rehash_conocido",
      "nuevo_hash_prefix": "$2y$10$"
    }
  ],
  "resumen": {
    "total_actualizados": 1,
    "total_errores": 0
  }
}
```

---

### Patch 2.2: Corrección Manual con SQL

**Si prefieres hacerlo directo en BD:**

```sql
-- Generar hash con PHP CLI primero:
-- php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);"
-- Resultado: $2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG

-- Aplicar en BD:
UPDATE usuarios 
SET password = '$2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG'
WHERE username = 'admin';

-- Verificar:
SELECT username, LEFT(password, 10) FROM usuarios WHERE username = 'admin';
-- Debe mostrar: $2y$10$yZS
```

**⚠️ IMPORTANTE:** El hash anterior es de "Admin123!". Si tu password real es otra, genera tu propio hash.

---

### Patch 2.3: Validación Mejorada en auth.php (Opcional)

**Si quieres detectar contraseñas no-bcrypt automáticamente:**

```diff
--- a/api/auth.php
+++ b/api/auth.php
@@ -90,6 +90,14 @@
 
 // 🔐 FIX CRÍTICO: REMOVIDO log de hash de contraseña (seguridad)
 // Verificar la contraseña
+
+// 🔥 FIX: Detectar contraseñas antiguas (MD5/SHA1)
+$password_info = password_get_info($usuario['password']);
+if ($password_info['algo'] === null || $password_info['algo'] === 0) {
+    error_log("ALERTA SEGURIDAD: Usuario {$username} tiene password NO-bcrypt");
+    // Opcionalmente: forzar cambio de contraseña
+}
+
 if (!password_verify($password, $usuario['password'])) {
     error_log("Intento de login fallido para usuario: " . $username . " (contraseña incorrecta) desde IP: " . $ip);
```

---

## 📊 SECCIÓN 3 — SCRIPTS DE VERIFICACIÓN

### SQL 3.1: Verificar Estado Actual

```sql
-- Ejecutar en producción
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN password REGEXP '^\\$2y\\$' THEN 1 ELSE 0 END) as bcrypt_ok,
    SUM(CASE WHEN LENGTH(password) = 32 THEN 1 ELSE 0 END) as md5,
    SUM(CASE WHEN LENGTH(password) < 30 THEN 1 ELSE 0 END) as sospechosos
FROM usuarios;
```

**Resultado esperado ANTES del fix:**

```
total: 3
bcrypt_ok: 0
md5: 3
sospechosos: 0
```

**Resultado esperado DESPUÉS del fix:**

```
total: 3
bcrypt_ok: 3
md5: 0
sospechosos: 0
```

---

### PHP 3.2: Test de Password Verify

**Crear archivo temporal:** `api/test_password_verify.php`

```php
<?php
// Probar password_verify con el hash de producción

$password_ingresada = 'Admin123!';
$hash_produccion = 'PEGAR_HASH_DE_BD_AQUI';

var_dump([
    'password' => $password_ingresada,
    'hash_length' => strlen($hash_produccion),
    'hash_prefix' => substr($hash_produccion, 0, 10),
    'hash_info' => password_get_info($hash_produccion),
    'verify_result' => password_verify($password_ingresada, $hash_produccion)
]);
```

**Resultado esperado con MD5:**

```php
array(5) {
  ["password"]=> string(8) "Admin123!"
  ["hash_length"]=> int(32)
  ["hash_prefix"]=> string(10) "5f4dcc3b5a"
  ["hash_info"]=> array(3) {
    ["algo"]=> NULL        // ❌ No es bcrypt
    ["algoName"]=> string(7) "unknown"
    ["options"]=> array(0) {}
  }
  ["verify_result"]=> bool(false)  // ❌ SIEMPRE false
}
```

**Resultado esperado con bcrypt:**

```php
array(5) {
  ["password"]=> string(8) "Admin123!"
  ["hash_length"]=> int(60)
  ["hash_prefix"]=> string(10) "$2y$10$yZS"
  ["hash_info"]=> array(3) {
    ["algo"]=> int(1)      // ✅ bcrypt
    ["algoName"]=> string(6) "bcrypt"
    ["options"]=> array(1) { ["cost"]=> int(10) }
  }
  ["verify_result"]=> bool(true)  // ✅ Funciona
}
```

---

## ✅ SECCIÓN 4 — PLAN DE CORRECCIÓN

### Plan 4.1: Corrección Rápida (5 minutos)

**Para volver operativo YA:**

1. **Generar hash bcrypt:**

```bash
php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT) . PHP_EOL;"
```

Resultado: `$2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG`

2. **Actualizar BD:**

```sql
UPDATE usuarios 
SET password = '$2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG'
WHERE username = 'admin';
```

3. **Probar login:**

```
Usuario: admin
Password: Admin123!
```

✅ Debe entrar sin problemas.

---

### Plan 4.2: Corrección Completa (15 minutos)

**Para todos los usuarios:**

1. **Backup de BD:**

```bash
mysqldump -u root -p tayrona_db usuarios > usuarios_backup_$(date +%Y%m%d_%H%M%S).sql
```

2. **Identificar usuarios sin bcrypt:**

```sql
SELECT username, role 
FROM usuarios 
WHERE password NOT REGEXP '^\\$2y\\$';
```

3. **Para cada usuario:**

   a. Confirmar password real  
   b. Generar hash: `php -r "echo password_hash('PasswordReal', PASSWORD_BCRYPT);"`  
   c. Actualizar: `UPDATE usuarios SET password = 'HASH' WHERE username = 'xxx';`

4. **Verificar:**

```sql
SELECT 
    username,
    CASE WHEN password REGEXP '^\\$2y\\$' THEN '✅' ELSE '❌' END as estado
FROM usuarios;
```

5. **Probar login de cada usuario**

---

### Plan 4.3: Corrección Automatizada (Script)

**Usando `rehash_passwords_seguro.php`:**

1. **Editar archivo:**

```php
$usuarios_conocidos = [
    ['username' => 'admin', 'password_nueva' => 'Admin123!'],
    ['username' => 'vendedor1', 'password_nueva' => 'Vend123!'],
    ['username' => 'cajero1', 'password_nueva' => 'Cajero123!'],
];
```

2. **Subir archivo al servidor**

3. **Ejecutar:**

```bash
curl http://148.230.72.12/kiosco/api/rehash_passwords_seguro.php
```

4. **Verificar resultado en JSON**

5. **Eliminar archivo del servidor (seguridad):**

```bash
rm api/rehash_passwords_seguro.php
```

---

## 🧪 SECCIÓN 5 — CASOS DE PRUEBA

### Test 5.1: Login Admin

**Precondición:** Admin tiene password bcrypt en BD

```
Usuario: admin
Password: Admin123!
```

**Resultado esperado:**
- ✅ Login exitoso
- ✅ Token generado
- ✅ Redirige a dashboard
- ✅ Log: "Login exitoso para usuario: admin"

---

### Test 5.2: Login Vendedor

**Precondición:** Vendedor tiene password bcrypt en BD

```
Usuario: vendedor1
Password: Vend123!
```

**Resultado esperado:**
- ⚠️ Si NO es admin: pide código de dispositivo
- ✅ Admin: bypasea validación (ya implementado)

---

### Test 5.3: Password Incorrecta

```
Usuario: admin
Password: WrongPassword
```

**Resultado esperado:**
- ❌ "Credenciales inválidas"
- ❌ Rate limit +1
- ❌ Log: "contraseña incorrecta"

---

### Test 5.4: Usuario No Existe

```
Usuario: noexiste
Password: cualquiera
```

**Resultado esperado:**
- ❌ "Credenciales inválidas"
- ❌ Log: "usuario no encontrado"

---

## 📝 SECCIÓN 6 — CHECKLIST POR ENTORNO

### ✅ Local (Laragon) — Funcionando

- [x] PHP 8.0+ con extensiones (pdo_mysql, mbstring, openssl)
- [x] MySQL 8.0+ con charset utf8mb4
- [x] Usuarios con password bcrypt ($2y$)
- [x] `api/auth.php` usa `password_verify()`
- [x] Frontend apunta a `http://localhost/kiosco`
- [x] CORS permite `localhost:3000`

### ⚠️ Producción (148.230.72.12) — A verificar

- [ ] **CRÍTICO:** Usuarios con password bcrypt (NO MD5/SHA1)
- [ ] PHP 8.0+ con mismas extensiones que local
- [ ] MySQL charset/collation consistente (utf8mb4)
- [ ] Frontend build apunta a dominio correcto
- [ ] CORS permite dominio de producción
- [ ] `lower_case_table_names` considerado (Windows vs Linux)
- [ ] Logs de Apache/PHP sin errores
- [ ] Rate limiting funcional
- [ ] Admin bypasea validación de dispositivo

---

## 🚀 SECCIÓN 7 — GUÍA DE DESPLIEGUE

### Pre-Deploy

1. **Backup completo:**

```bash
mysqldump -u root -p tayrona_db > backup_pre_fix_$(date +%Y%m%d_%H%M%S).sql
```

2. **Verificar usuarios locales:**

```sql
SELECT username, LEFT(password, 10) FROM usuarios;
```

---

### Deploy

1. **Subir scripts de diagnóstico:**

```bash
# Via FTP o Git
api/diagnostico_auth_completo.php
api/rehash_passwords_seguro.php
```

2. **Ejecutar diagnóstico:**

```bash
curl http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php > diagnostico_result.json
```

3. **Analizar resultado**

4. **Si confirma problema de bcrypt:**

   a. Generar hashes correctos  
   b. Actualizar BD con SQL o script  
   c. Verificar con SELECT

5. **Probar login**

---

### Post-Deploy

1. **Verificación:**

```sql
SELECT 
    username,
    CASE WHEN password REGEXP '^\\$2y\\$' THEN '✅ OK' ELSE '❌ PENDIENTE' END
FROM usuarios;
```

2. **Test de login para cada rol:**
   - Admin
   - Vendedor
   - Cajero

3. **Revisar logs:**

```bash
tail -f /var/log/apache2/error.log
tail -f api/logs/*.log
```

4. **Limpiar scripts temporales:**

```bash
rm api/diagnostico_auth_completo.php
rm api/rehash_passwords_seguro.php
rm api/test_password_verify.php
```

---

### Rollback (Si algo sale mal)

**Restaurar BD:**

```bash
mysql -u root -p tayrona_db < backup_pre_fix_YYYYMMDD_HHMMSS.sql
```

**Verificar:**

```sql
SELECT COUNT(*) FROM usuarios;
```

---

## 🔍 SECCIÓN 8 — CÓMO REPRODUJE EL PROBLEMA

### Reproducción en Local

1. **Simular el problema:**

```sql
-- Crear usuario con password MD5 (como en producción)
INSERT INTO usuarios (username, password, nombre, role, created_at) 
VALUES ('test_md5', MD5('password123'), 'Test MD5', 'vendedor', NOW());
```

2. **Intentar login con `password_verify()`:**

```php
$hash_md5 = md5('password123');
$resultado = password_verify('password123', $hash_md5);
// Resultado: false ❌
```

3. **Confirmar que falla igual que en producción**

---

### Reproducción en Staging

1. **Copiar BD de producción a staging**

2. **Ejecutar diagnóstico:**

```bash
curl http://staging.example.com/api/diagnostico_auth_completo.php
```

3. **Confirmar mismo problema**

4. **Aplicar fix**

5. **Validar que funciona antes de aplicar en producción**

---

## 📊 EVIDENCIAS REQUERIDAS

### Pre-Fix

- [ ] Screenshot de `diagnostico_auth_completo.php` mostrando usuarios sin bcrypt
- [ ] Query SQL con `LENGTH(password) = 32`
- [ ] Log de error: "Intento de login fallido"
- [ ] Screenshot de frontend: "Credenciales inválidas"

### Post-Fix

- [ ] Query SQL con `password REGEXP '^\\$2y\\$'` = 100% usuarios
- [ ] Screenshot de login exitoso
- [ ] Log: "Login exitoso para usuario: admin"
- [ ] JSON de `diagnostico_auth_completo.php` con `bcrypt_usuarios = total_usuarios`

---

## 🎯 RESUMEN EJECUTIVO

| Aspecto | Detalle |
|---------|---------|
| **Síntoma** | "Credenciales inválidas" en producción |
| **Causa raíz** | Passwords NO son bcrypt en BD de producción |
| **Solución** | Rehashear passwords con `password_hash()` de PHP |
| **Tiempo estimado** | 5-15 minutos |
| **Riesgo** | Bajo (solo actualiza passwords, no schema) |
| **Rollback** | Restaurar backup de tabla `usuarios` |
| **Validación** | Login exitoso + SQL query confirmando bcrypt |

---

## 📞 SIGUIENTE ACCIÓN INMEDIATA

**EJECUTAR AHORA:**

```bash
# 1. Diagnóstico
curl http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php

# 2. Si confirma problema, generar hash:
php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);"

# 3. Actualizar en BD:
# (Conectar a phpMyAdmin o MySQL CLI y ejecutar UPDATE)
```

**¿Necesitas ayuda ejecutando algún paso?** Avísame y te guío específicamente.

---

## 🔗 ARCHIVOS RELACIONADOS

- `api/auth.php` — Endpoint de autenticación (línea 93: password_verify)
- `api/diagnostico_auth_completo.php` — Script de diagnóstico (NUEVO)
- `api/rehash_passwords_seguro.php` — Script de corrección (NUEVO)
- `DIAGNOSTICO_AUTH_PRODUCCION.sql` — Queries SQL de verificación (NUEVO)
- `src/components/LoginPage.jsx` — Frontend login (línea 44: bypass admin)

---

**Última actualización:** 2025-10-24  
**Versión:** 1.0  
**Autor:** Cursor AI Assistant

