<?php
/**
 * api/db_config.php
 * ARCHIVO ÚNICO DE CONFIGURACIÓN DE BASE DE DATOS - PRODUCCIÓN
 * Modificar solo este archivo para cambiar credenciales
 * RELEVANT FILES: bd_conexion.php, todos los archivos de API
 */

// ========================================================================
// 🔐 CREDENCIALES DE BASE DE DATOS - PRODUCCIÓN
// ========================================================================
// ⚠️ IMPORTANTE: Reemplazar con tus credenciales reales del servidor
// ========================================================================

// 🌐 CONFIGURACIÓN DE PRODUCCIÓN (148.230.72.12)
define('DB_HOST', 'localhost');                    // ← Generalmente "localhost"
define('DB_PORT', '3306');                         // ← Puerto estándar MySQL
define('DB_NAME', 'TU_NOMBRE_DE_BASE_DE_DATOS');   // ⚠️ CAMBIAR
define('DB_USER', 'TU_USUARIO_MYSQL');             // ⚠️ CAMBIAR
define('DB_PASS', 'TU_PASSWORD_MYSQL');            // ⚠️ CAMBIAR
define('DB_CHARSET', 'utf8mb4');

// ========================================================================
// EJEMPLOS COMUNES SEGÚN HOSTING:
// ========================================================================

// 📌 HOSTINGER:
// define('DB_NAME', 'u123456789_kiosco');
// define('DB_USER', 'u123456789_admin');
// define('DB_PASS', 'tu_password_seguro');

// 📌 cPanel/WHM:
// define('DB_NAME', 'usuario_kiosco');
// define('DB_USER', 'usuario_admin');
// define('DB_PASS', 'tu_password');

// 📌 Plesk:
// define('DB_NAME', 'kiosco_db');
// define('DB_USER', 'admin_kiosco');
// define('DB_PASS', 'tu_password');

// ========================================================================
// ⚙️ OPCIONES DE CONEXIÓN PDO (NO MODIFICAR)
// ========================================================================
$GLOBALS['DB_OPTIONS'] = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 30
];

// ========================================================================
// 🌍 CONFIGURACIÓN ADICIONAL
// ========================================================================
define('DB_TIMEZONE', 'America/Argentina/Buenos_Aires');
define('DB_PERSISTENT', false);

// Log de configuración cargada
error_log("[DB_CONFIG] Configuración PRODUCCIÓN cargada - Host: " . DB_HOST . " - DB: " . DB_NAME);
?>

