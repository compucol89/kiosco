# 🔐 CÓMO OBTENER CREDENCIALES DE BASE DE DATOS

**Servidor:** `148.230.72.12`  
**Problema:** Login no funciona porque `db_config.php` tiene credenciales de desarrollo local

---

## 🎯 NECESITAS CONSEGUIR ESTAS 3 CREDENCIALES:

1. **Nombre de la base de datos** (ej: `kiosco_db`, `u123456_kiosco`)
2. **Usuario de MySQL** (ej: `root`, `u123456_admin`)
3. **Password de MySQL** (el password del usuario)

---

## 📋 MÉTODOS PARA OBTENER LAS CREDENCIALES

### MÉTODO 1: Panel de Control (cPanel/Plesk) ⭐ RECOMENDADO

#### Si tienes cPanel:

1. **Acceder a cPanel:**
   ```
   http://148.230.72.12:2083
   o
   http://tudominio.com:2083
   ```

2. **Buscar sección "Bases de Datos":**
   - Click en **"Bases de datos MySQL"** o **"MySQL Databases"**

3. **Ver bases de datos existentes:**
   - Verás una lista de bases de datos
   - Busca una que se llame `kiosco`, `kiosco_db` o similar
   - **Anota el nombre completo** (ej: `u123456789_kiosco`)

4. **Ver usuarios MySQL:**
   - En la misma página, busca "Usuarios actuales"
   - Anota el usuario asociado (ej: `u123456789_admin`)

5. **Password:**
   - Si no tienes el password, puedes:
     - **Opción A:** Cambiar el password del usuario
     - **Opción B:** Crear nuevo usuario con password conocido

#### Si tienes Plesk:

1. **Acceder a Plesk:**
   ```
   http://148.230.72.12:8880
   o
   https://148.230.72.12:8443
   ```

2. **Ir a "Bases de datos":**
   - Panel lateral → **Bases de datos**

3. **Ver bases de datos:**
   - Click en la base de datos `kiosco` o similar
   - Verás: nombre, usuario, host

4. **Anotar credenciales:**
   - Nombre de BD
   - Usuario
   - Cambiar password si no lo recuerdas

---

### MÉTODO 2: SSH/Terminal (Si tienes acceso)

```bash
# Conectar por SSH
ssh tu_usuario@148.230.72.12

# Buscar archivos de configuración existentes
find . -name "db_config.php" -o -name "config.php" | head -5

# Ver contenido (si existe alguna config antigua)
cat ruta/al/archivo/config.php
```

---

### MÉTODO 3: Preguntar a tu Proveedor de Hosting

Si no tienes acceso al panel:

**Contactar soporte con:**
- Nombre de tu cuenta
- Dominio o IP: `148.230.72.12`
- Preguntar:
  - ¿Cuál es el nombre de mi base de datos?
  - ¿Cuál es el usuario de MySQL?
  - ¿Cuál es el password? (o resetear)

---

### MÉTODO 4: Buscar en Archivos Existentes

Si ya tenías algo funcionando antes, buscar en archivos antiguos:

**Archivos comunes que pueden tener las credenciales:**
```
/kiosco/api/config.php
/kiosco/config.php
/kiosco/includes/config.php
/kiosco/wp-config.php (si usabas WordPress)
/kiosco/.env
```

**Por FTP/FileZilla:**
1. Conectar al servidor
2. Navegar a `/kiosco` o `/public_html`
3. Buscar estos archivos
4. Descargar y abrir para ver credenciales

---

## ✅ UNA VEZ QUE TENGAS LAS CREDENCIALES:

### Paso 1: Editar el archivo

Abrir `api/db_config.PRODUCCION.php` y reemplazar:

```php
define('DB_NAME', 'TU_NOMBRE_DE_BASE_DE_DATOS');   // ← Cambiar aquí
define('DB_USER', 'TU_USUARIO_MYSQL');             // ← Cambiar aquí
define('DB_PASS', 'TU_PASSWORD_MYSQL');            // ← Cambiar aquí
```

**Ejemplo real:**
```php
define('DB_NAME', 'u456789123_kiosco');
define('DB_USER', 'u456789123_admin');
define('DB_PASS', 'MiP@ssw0rd2025!');
```

### Paso 2: Renombrar archivo

```
api/db_config.PRODUCCION.php  →  api/db_config.php
```

### Paso 3: Subir al servidor

Subir el archivo `api/db_config.php` corregido al servidor en:
```
/kiosco/api/db_config.php
```

### Paso 4: Verificar

Abrir en navegador:
```
http://148.230.72.12/kiosco/api/test_conexion.php
```

---

## 🧪 TEST DE CONEXIÓN (OPCIONAL)

Crear archivo temporal para probar:

**Archivo:** `api/test_bd.php`
```php
<?php
require_once 'db_config.php';
require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    if ($pdo) {
        echo "✅ Conexión exitosa a la base de datos!\n";
        echo "Host: " . DB_HOST . "\n";
        echo "Base de datos: " . DB_NAME . "\n";
        echo "Usuario: " . DB_USER . "\n";
        
        // Probar query
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        echo "Usuarios en BD: " . $result['total'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
?>
```

Subir este archivo y abrir:
```
http://148.230.72.12/kiosco/api/test_bd.php
```

**Si muestra "✅ Conexión exitosa"** → Credenciales correctas ✅  
**Si muestra "❌ Error"** → Credenciales incorrectas, revisar

---

## 📊 CREDENCIALES COMUNES SEGÚN TIPO DE HOSTING

| Hosting | DB_HOST | DB_NAME | DB_USER | Nota |
|---------|---------|---------|---------|------|
| **Hostinger** | `localhost` | `u123456_xxx` | `u123456_xxx` | Usuario comienza con `u` + números |
| **cPanel** | `localhost` | `usuario_db` | `usuario_xxx` | Usuario = nombre cuenta |
| **Plesk** | `localhost` | `nombre_db` | `admin_xxx` | Usuario = admin_ + nombre |
| **VPS** | `localhost` | Cualquiera | `root` o custom | Depende de tu config |
| **Cloud** | IP remoto | Cualquiera | Cualquiera | Puede no ser localhost |

---

## ⚠️ ERRORES COMUNES

### Error: "Access denied for user"
**Causa:** Usuario o password incorrecto  
**Solución:** Verificar credenciales en panel de control

### Error: "Unknown database"
**Causa:** Nombre de base de datos incorrecto  
**Solución:** Verificar nombre exacto en panel

### Error: "Can't connect to MySQL server"
**Causa:** Host incorrecto o MySQL no está corriendo  
**Solución:** Verificar que MySQL esté activo, contactar soporte

### Error: "SQLSTATE[HY000] [2002]"
**Causa:** Host incorrecto (probablemente no es `localhost`)  
**Solución:** Preguntar a hosting si es `localhost` o una IP

---

## 🎯 RESUMEN RÁPIDO

1. ✅ Acceder a tu panel de control (cPanel/Plesk)
2. ✅ Ir a sección "Bases de datos"
3. ✅ Anotar: Nombre de BD, Usuario, Password
4. ✅ Editar `api/db_config.PRODUCCION.php` con esas credenciales
5. ✅ Renombrar a `api/db_config.php`
6. ✅ Subir al servidor
7. ✅ Probar login en `http://148.230.72.12/kiosco`

---

**¿No tienes acceso al panel?** Contacta a tu proveedor de hosting y pide las credenciales de MySQL.

**¿Sigues teniendo problemas?** Compárteme el mensaje de error exacto y te ayudo a solucionarlo.

