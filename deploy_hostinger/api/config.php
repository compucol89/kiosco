<?php
// Configuración de la base de datos
// Incluir el archivo de conexión para usar la clase Conexion
require_once 'bd_conexion.php';

// Crear conexión a la base de datos
$pdo = Conexion::obtenerConexion();

// Configuración de entorno
$env = getenv('APP_ENV') ?: 'production'; // Usar 'production' por defecto

// Configuración según el entorno
if ($env === 'development') {
    // Entorno de desarrollo - mostrar errores
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Entorno de producción - ocultar errores, usar logs
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    
    // Asegurar que los errores se registren en log
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Configuración de zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Constantes generales de la aplicación
define('APP_NAME', 'Tayrona Almacén');
define('APP_VERSION', '1.0.1');

// Función para depuración
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $log .= ' - ' . json_encode($data);
    }
    error_log($log);
}

// Función para respuesta JSON estandarizada
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

// Función para respuesta de error estandarizada
function json_error($message, $status = 500) {
    json_response([
        'success' => false,
        'error' => $message
    ], $status);
}
?> 