# 🚨 INSTRUCCIONES INMEDIATAS: FIX LOGIN EN PRODUCCIÓN

**Fecha:** 2025-10-24  
**Problema:** Admin y vendedores no pueden entrar en servidor (148.230.72.12)  
**Causa probable:** Contraseñas NO son bcrypt en producción  

---

## ⚡ SOLUCIÓN EN 3 PASOS (5 MINUTOS)

### 📋 PASO 1: DIAGNÓSTICO

Ejecuta esto en tu navegador o terminal:

```
http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php
```

**Qué buscar:**

```json
{
  "usuarios": {
    "estadisticas": {
      "bcrypt_usuarios": 0,  // ❌ Si es 0 = PROBLEMA CONFIRMADO
      "total_usuarios": 3
    }
  }
}
```

Si `bcrypt_usuarios` es menor que `total_usuarios`, **CONFIRMADO**: las contraseñas están en MD5/SHA1 (NO bcrypt).

---

### 🔧 PASO 2: GENERAR HASH CORRECTO

En tu terminal local (Git Bash o PowerShell):

```bash
php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);"
```

**Resultado:** `$2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG`

⚠️ **IMPORTANTE:** Si tu password de admin NO es `Admin123!`, usa tu password real.

---

### 💾 PASO 3: ACTUALIZAR BASE DE DATOS

Conectar a phpMyAdmin en el servidor y ejecutar:

```sql
UPDATE usuarios 
SET password = '$2y$10$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG'
WHERE username = 'admin';
```

**Verificar:**

```sql
SELECT username, LEFT(password, 10) as hash_prefix 
FROM usuarios 
WHERE username = 'admin';
```

**Resultado esperado:** `$2y$10$yZS`

---

### ✅ PASO 4: PROBAR LOGIN

```
http://148.230.72.12/kiosco

Usuario: admin
Password: Admin123!
```

**Debe entrar sin problemas** ✅

---

## 🔄 SI TIENES VARIOS USUARIOS

### Opción A: Manual (uno por uno)

Para cada usuario:

1. Generar hash: `php -r "echo password_hash('SuPassword', PASSWORD_BCRYPT);"`
2. Actualizar: `UPDATE usuarios SET password = 'HASH' WHERE username = 'xxx';`

---

### Opción B: Automatizado (Script)

1. **Editar** `api/rehash_passwords_seguro.php` (línea 35):

```php
$usuarios_conocidos = [
    ['username' => 'admin', 'password_nueva' => 'Admin123!'],
    ['username' => 'vendedor1', 'password_nueva' => 'Vend123!'],
    ['username' => 'cajero1', 'password_nueva' => 'Cajero123!'],
];
```

2. **Subir archivo** al servidor

3. **Ejecutar:**

```bash
curl http://148.230.72.12/kiosco/api/rehash_passwords_seguro.php
```

4. **Ver resultado** (JSON con usuarios actualizados)

5. **Eliminar archivo** del servidor por seguridad:
   - Conectar por FTP
   - Borrar `api/rehash_passwords_seguro.php`

---

## 📚 DOCUMENTACIÓN COMPLETA

Para más detalles, consulta:

- **`SOLUCION_CREDENCIALES_INVALIDAS.md`** — Guía completa (500+ líneas)
- **`DIAGNOSTICO_AUTH_PRODUCCION.sql`** — Queries SQL de verificación
- **`api/diagnostico_auth_completo.php`** — Script de diagnóstico
- **`api/rehash_passwords_seguro.php`** — Script de corrección

---

## 🧪 VERIFICACIÓN POST-FIX

```sql
SELECT 
    username,
    CASE 
        WHEN password REGEXP '^\\$2y\\$' THEN '✅ CORRECTO'
        ELSE '❌ PENDIENTE'
    END as estado
FROM usuarios;
```

**Resultado esperado:**

| username | estado |
|----------|--------|
| admin | ✅ CORRECTO |
| vendedor1 | ✅ CORRECTO |
| cajero1 | ✅ CORRECTO |

---

## ❓ PREGUNTAS FRECUENTES

### ¿Por qué falla?

**Respuesta:** `password_verify()` en PHP **SOLO** funciona con bcrypt/argon2. Si en BD hay MD5/SHA1, **SIEMPRE retorna false**.

### ¿Es seguro generar el hash en local?

**Respuesta:** Sí, el algoritmo bcrypt es determinístico. El hash generado en local funciona igual en producción.

### ¿Qué pasa con los datos existentes?

**Respuesta:** Solo se modifica la columna `password`. El resto de datos (nombre, rol, etc.) permanece intacto.

### ¿Cómo hago rollback?

**Respuesta:** 

1. Backup antes de modificar:
```bash
mysqldump -u root -p tayrona_db usuarios > backup_usuarios.sql
```

2. Si algo sale mal:
```bash
mysql -u root -p tayrona_db < backup_usuarios.sql
```

---

## 🆘 SI TIENES PROBLEMAS

### Error: "No se pudo conectar al servidor"

**Solución:** Verifica que `diagnostico_auth_completo.php` esté en `api/` del servidor.

### Error: "Access denied for user"

**Solución:** Verifica credenciales de BD en `api/db_config.php` (pero **NO TOCAR** ese archivo en servidor, ya está protegido).

### Login sigue fallando después del fix

**Posibles causas:**

1. Password incorrecta (verifica que usaste la correcta al generar hash)
2. Rate limiting (esperar 15 minutos)
3. Problema de CORS (revisar `api/cors_middleware.php`)
4. Problema de dispositivo confiable (admin ya tiene bypass implementado)

**Diagnóstico adicional:**

```bash
# Ver logs de Apache/PHP
tail -f /var/log/apache2/error.log
```

---

## 📞 RESUMEN ULTRA-CORTO

```bash
# 1. Diagnóstico
http://148.230.72.12/kiosco/api/diagnostico_auth_completo.php

# 2. Generar hash
php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);"

# 3. Actualizar BD
UPDATE usuarios SET password = '$2y$10$...' WHERE username = 'admin';

# 4. Probar login
http://148.230.72.12/kiosco
```

**¡Listo! 🚀**

---

**Creado:** 2025-10-24  
**Autor:** Cursor AI Assistant  
**Versión:** 1.0

