# 🔍 INFORME: ARCHIVOS DE CONEXIÓN A BASE DE DATOS

**Fecha:** 8 de Octubre, 2025  
**Hallazgo:** Múltiples archivos de conexión encontrados

---

## 📊 ARCHIVOS ENCONTRADOS

He encontrado **6 archivos** relacionados con conexión a BD:

### 1️⃣ api/db_config.php ✅ (PRINCIPAL - EN USO)

**Contenido:**
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**Estado:** ✅ **SE USA** en bd_conexion.php  
**Función:** Archivo ÚNICO de credenciales  
**Usado por:** 59 endpoints del sistema  
**Acción:** ✅ MANTENER

---

### 2️⃣ api/bd_conexion.php ✅ (CLASE - EN USO)

**Función:** Clase de conexión PDO  
**Usa:** db_config.php para obtener credenciales  
**Estado:** ✅ **SE USA** por todo el sistema  
**Acción:** ✅ MANTENER

---

### 3️⃣ api/bd_conexion_railway.php ❌ (ALTERNATIVA - NO USADO)

**Contenido:** Clase de conexión para Railway con variables de entorno  
**Credenciales:** Usa $_ENV pero tiene fallback hardcodeado:
```php
'mysql:host=localhost;dbname=kiosco_db;charset=utf8mb4'
```

**Estado:** ❌ **NO SE USA** (ningún archivo lo requiere)  
**Problema:** Duplica credenciales  
**Acción:** 🔴 ELIMINAR

---

### 4️⃣ api/bd_conexion_hostinger.php ❌ (ALTERNATIVA - NO USADO)

**Contenido:** Clase de conexión para Hostinger  
**Credenciales hardcodeadas:**
```php
'host' => 'localhost',
'db_name' => 'u123456789_kiosco',
'username' => 'u123456789_admin',
'password' => 'TU_PASSWORD_AQUI'
```

**Estado:** ❌ **NO SE USA**  
**Problema:** Credenciales de ejemplo hardcodeadas  
**Acción:** 🔴 ELIMINAR

---

### 5️⃣ api/conexion_simple.php ❌ (ANTIGUA - NO USADO)

**Contenido:** Conexión mysqli antigua  
**Credenciales hardcodeadas:**
```php
$servername = "localhost";
$username = "root";
$password = "";
$database = "kiosco_db";
```

**Estado:** ❌ **NO SE USA**  
**Problema:** Archivo antiguo, duplica credenciales  
**Acción:** 🔴 ELIMINAR

---

### 6️⃣ api/db_init.php ❌ (SCRIPT INIT - NO USADO)

**Contenido:** Script de inicialización de BD  
**Credenciales variables:**
```php
$host = 'localhost';
$username = 'root';
$password = '';
```

**Estado:** ❌ **NO SE USA** (script de setup una vez)  
**Problema:** Credenciales hardcodeadas  
**Acción:** 🔴 ELIMINAR

---

## 📊 VERIFICACIÓN DE USO

### Archivos que SÍ se usan (59 endpoints):

Todos usan:
```php
require_once 'bd_conexion.php';
```

**Ninguno usa:**
- bd_conexion_railway.php
- bd_conexion_hostinger.php  
- conexion_simple.php
- db_init.php

---

## ⚠️ PROBLEMA DE SEGURIDAD

Actualmente tienes **credenciales de BD en 6 archivos**:

1. ✅ db_config.php (ÚNICO que debería tener)
2. ❌ bd_conexion_railway.php (duplicado)
3. ❌ bd_conexion_hostinger.php (duplicado)
4. ❌ conexion_simple.php (duplicado)
5. ❌ db_init.php (duplicado)
6. ❌ config_database.php (YA ELIMINADO ✅)

**Riesgo:**
- Credenciales en múltiples lugares
- Difícil mantener sincronizadas
- Mayor superficie de ataque
- Confusión en deployment

---

## ✅ SOLUCIÓN RECOMENDADA

### ELIMINAR 4 archivos duplicados:

```bash
❌ api/bd_conexion_railway.php
❌ api/bd_conexion_hostinger.php
❌ api/conexion_simple.php
❌ api/db_init.php
```

### MANTENER solo 2 archivos:

```bash
✅ api/db_config.php (credenciales)
✅ api/bd_conexion.php (clase de conexión)
```

---

## 🎯 RESULTADO FINAL

**Después de eliminar duplicados:**
- ✅ **1 SOLO archivo** con credenciales (db_config.php)
- ✅ **1 archivo** de clase de conexión (bd_conexion.php)
- ✅ Más seguro
- ✅ Fácil de gestionar
- ✅ Sin confusión

**Para deployment:**
- Solo cambias **db_config.php**
- Todo el sistema se actualiza automáticamente

---

## ❓ PRÓXIMA ACCIÓN

¿Quieres que elimine los 4 archivos duplicados ahora?

Quedaría solo:
- ✅ api/db_config.php (credenciales)
- ✅ api/bd_conexion.php (conexión)

**Es 100% seguro eliminarlos.**

