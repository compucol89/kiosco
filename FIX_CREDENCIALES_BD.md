# 🔧 FIX: Credenciales de Base de Datos para Producción

**Fecha:** 21/10/2025  
**Problema:** Login no funciona en producción  
**Causa:** `api/db_config.php` tiene credenciales de desarrollo local

---

## 🔴 PROBLEMA DETECTADO

```php
// ❌ ARCHIVO ACTUAL: api/db_config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kiosco_db');      // ← Base de datos LOCAL
define('DB_USER', 'root');           // ← Usuario LOCAL
define('DB_PASS', '');               // ← Password vacío (solo local)
```

**Estas credenciales solo funcionan en Laragon (tu PC), NO en el servidor de producción.**

---

## ✅ SOLUCIÓN

### Paso 1: Obtener Credenciales Reales

**Necesitas conseguir del servidor `148.230.72.12`:**

1. **Nombre de la base de datos** (ej: `u456789_kiosco`, `kiosco_prod`)
2. **Usuario de MySQL** (ej: `u456789_admin`, `kiosco_user`)
3. **Password de MySQL** (el password real del servidor)

**¿Cómo obtenerlas?**
- Acceder a **cPanel** en `http://148.230.72.12:2083`
- O acceder a **Plesk** en `http://148.230.72.12:8880`
- O preguntar a tu proveedor de hosting

📖 **Guía detallada:** Ver archivo `COMO_OBTENER_CREDENCIALES_BD.md`

---

### Paso 2: Editar Archivo de Configuración

He creado el archivo: **`api/db_config.PRODUCCION.php`**

**Editarlo y cambiar estas 3 líneas:**

```php
define('DB_NAME', 'TU_NOMBRE_DE_BASE_DE_DATOS');   // ⚠️ CAMBIAR
define('DB_USER', 'TU_USUARIO_MYSQL');             // ⚠️ CAMBIAR
define('DB_PASS', 'TU_PASSWORD_MYSQL');            // ⚠️ CAMBIAR
```

**Ejemplo con credenciales reales:**
```php
define('DB_NAME', 'u456789123_kiosco');
define('DB_USER', 'u456789123_admin');
define('DB_PASS', 'MySecureP@ss2025!');
```

---

### Paso 3: Renombrar y Subir

```bash
# 1. Renombrar el archivo:
api/db_config.PRODUCCION.php  →  api/db_config.php

# 2. Subir al servidor (reemplazar el existente):
Servidor: 148.230.72.12
Ruta: /kiosco/api/db_config.php
```

**Métodos para subir:**
- **FileZilla (FTP):** Conectar y arrastrar archivo
- **cPanel File Manager:** Subir y reemplazar
- **SCP/SFTP:** `scp api/db_config.php usuario@148.230.72.12:/ruta/kiosco/api/`

---

### Paso 4: Verificar Conexión

**Test 1: Verificar que cargue el archivo**
```
http://148.230.72.12/kiosco/api/bd_conexion.php
```

Debe mostrar: Nada (blank) o error de conexión (normal)

**Test 2: Probar login**
```
http://148.230.72.12/kiosco
```

1. Usuario: `admin`
2. Password: tu contraseña
3. Click "Iniciar Sesión"

**✅ Si entra al Dashboard** → Credenciales correctas  
**❌ Si sigue fallando** → Revisar credenciales

---

## 🧪 SCRIPT DE PRUEBA (OPCIONAL)

Para probar la conexión sin hacer login, crear:

**Archivo:** `api/test_conexion_bd.php`

```php
<?php
// Test de conexión a base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Test de Conexión a Base de Datos</h2>\n";

try {
    require_once 'db_config.php';
    echo "✅ db_config.php cargado<br>\n";
    echo "Host: " . DB_HOST . "<br>\n";
    echo "Base de datos: " . DB_NAME . "<br>\n";
    echo "Usuario: " . DB_USER . "<br>\n";
    echo "Password: " . (empty(DB_PASS) ? '(vacío)' : '****') . "<br>\n";
    
    require_once 'bd_conexion.php';
    echo "✅ bd_conexion.php cargado<br>\n";
    
    $pdo = Conexion::obtenerConexion();
    
    if ($pdo) {
        echo "<h3>✅ CONEXIÓN EXITOSA</h3>\n";
        
        // Probar query
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        echo "Total usuarios en BD: " . $result['total'] . "<br>\n";
        
        // Verificar admin
        $stmt = $pdo->query("SELECT id, username, role FROM usuarios WHERE role='admin' LIMIT 1");
        $admin = $stmt->fetch();
        if ($admin) {
            echo "Admin encontrado: " . $admin['username'] . " (ID: " . $admin['id'] . ")<br>\n";
        }
        
        echo "<hr>\n";
        echo "🎉 Base de datos configurada correctamente. Puedes probar el login.";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR DE CONEXIÓN</h3>\n";
    echo "<pre>";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "</pre>";
    
    echo "<hr>\n";
    echo "<h4>🔧 Posibles soluciones:</h4>\n";
    echo "<ul>";
    echo "<li>Verificar que DB_NAME sea correcto</li>";
    echo "<li>Verificar que DB_USER sea correcto</li>";
    echo "<li>Verificar que DB_PASS sea correcto</li>";
    echo "<li>Verificar que MySQL esté activo en el servidor</li>";
    echo "<li>Contactar a tu proveedor de hosting</li>";
    echo "</ul>";
}
?>
```

**Subir este archivo y abrir:**
```
http://148.230.72.12/kiosco/api/test_conexion_bd.php
```

---

## 📊 CHECKLIST

- [ ] Obtener credenciales reales del servidor (cPanel/Plesk)
- [ ] Editar `api/db_config.PRODUCCION.php` con credenciales reales
- [ ] Renombrar a `api/db_config.php`
- [ ] Subir archivo al servidor (reemplazar existente)
- [ ] Probar con `test_conexion_bd.php` (opcional)
- [ ] Probar login en `http://148.230.72.12/kiosco`
- [ ] ✅ Confirmar acceso al Dashboard

---

## ⚠️ ERRORES COMUNES Y SOLUCIONES

### Error: "Access denied for user 'root'@'localhost'"

**Causa:** Usuario o password incorrecto

**Solución:**
```php
// Verificar credenciales en panel de control
// NO usar 'root' en producción, usar tu usuario real
define('DB_USER', 'u456789_admin');  // ← Usuario real del servidor
define('DB_PASS', 'password_real');   // ← Password real del servidor
```

---

### Error: "Unknown database 'kiosco_db'"

**Causa:** Nombre de base de datos incorrecto

**Solución:**
```php
// Verificar nombre exacto en panel de control
define('DB_NAME', 'u456789_kiosco');  // ← Nombre exacto de la BD en servidor
```

---

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Causa:** MySQL no está corriendo o host incorrecto

**Solución:**
```php
// Verificar que DB_HOST sea correcto (generalmente 'localhost')
define('DB_HOST', 'localhost');

// Si el host es diferente, pregunta a tu hosting
// Algunos usan: '127.0.0.1' o 'mysql.tudominio.com'
```

---

## 🎯 RESUMEN

**Problema:** `db_config.php` tiene credenciales locales (Laragon)  
**Solución:** Reemplazar con credenciales del servidor de producción  
**Archivo a editar:** `api/db_config.PRODUCCION.php` → renombrar a `api/db_config.php`  
**Archivo a subir:** `/kiosco/api/db_config.php` en el servidor

**Una vez corregido, el login funcionará** ✅

---

## 📞 ¿NECESITAS AYUDA?

**Si sigues teniendo problemas:**

1. Comparte el mensaje de error completo
2. Comparte captura del panel de control (sin passwords)
3. Comparte resultado de `test_conexion_bd.php`

Te ayudo a diagnosticar y resolver el problema específico.

---

**Archivos creados:**
- ✅ `api/db_config.PRODUCCION.php` (plantilla con credenciales)
- ✅ `COMO_OBTENER_CREDENCIALES_BD.md` (guía detallada)
- ✅ `FIX_CREDENCIALES_BD.md` (este archivo)

**Próximo paso:** Obtener credenciales reales y editar el archivo.

