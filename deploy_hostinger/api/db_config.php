<?php
/**
 * api/db_config.php
 * ARCHIVO ÚNICO DE CONFIGURACIÓN DE BASE DE DATOS
 * Modificar solo este archivo para cambiar credenciales
 * RELEVANT FILES: bd_conexion.php, todos los archivos de API
 */

// ========================================================================
// 🔐 CREDENCIALES DE BASE DE DATOS
// ========================================================================
// INSTRUCCIONES:
// - En LOCAL (Laragon): Dejar como está
// - En HOSTINGER: Cambiar host, database, user, password
// - En RAILWAY/otro: Descomentar sección correspondiente
// ========================================================================

// 🏠 CONFIGURACIÓN LOCAL (LARAGON)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ========================================================================
// 📌 PARA HOSTINGER - Descomentar y configurar:
// ========================================================================
// define('DB_HOST', 'localhost');
// define('DB_PORT', '3306');
// define('DB_NAME', 'u123456789_kiosco');     // ⚠️ CAMBIAR
// define('DB_USER', 'u123456789_admin');      // ⚠️ CAMBIAR
// define('DB_PASS', 'TU_PASSWORD_AQUI');      // ⚠️ CAMBIAR
// define('DB_CHARSET', 'utf8mb4');

// ========================================================================
// 📌 PARA RAILWAY/CLOUD - Descomentar y configurar:
// ========================================================================
// $db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
// if ($db_url) {
//     $parts = parse_url($db_url);
//     define('DB_HOST', $parts['host']);
//     define('DB_PORT', $parts['port'] ?? '3306');
//     define('DB_NAME', ltrim($parts['path'] ?? '/railway', '/'));
//     define('DB_USER', $parts['user']);
//     define('DB_PASS', $parts['pass']);
//     define('DB_CHARSET', 'utf8mb4');
// }

// ========================================================================
// ⚙️ OPCIONES DE CONEXIÓN PDO
// ========================================================================
// No se puede usar define() con arrays en PHP < 7.0, usar constante global
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
define('DB_PERSISTENT', false); // Conexiones persistentes solo en producción

// Log de configuración cargada
error_log("[DB_CONFIG] Configuración cargada - Host: " . DB_HOST . " - DB: " . DB_NAME);
?>
