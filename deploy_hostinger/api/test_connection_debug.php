<?php
/**
 * api/test_connection_debug.php
 * Script de diagnóstico para conexión a base de datos en Railway
 * Muestra información detallada para debug
 * RELEVANT FILES: bd_conexion_railway_fix.php, config.php, auth.php
 */

// Headers para debug
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Si es OPTIONS, responder OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion_railway_fix.php';

try {
    echo json_encode([
        'titulo' => 'DIAGNÓSTICO DE CONEXIÓN RAILWAY',
        'timestamp' => date('Y-m-d H:i:s'),
        
        // Variables de entorno
        'variables_entorno' => [
            'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? 'NO DEFINIDA',
            'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? 'NO DEFINIDA',
            'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'NO DEFINIDA',
            'MYSQL_USER' => $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? 'NO DEFINIDA',
            'MYSQL_PASSWORD' => isset($_ENV['MYSQL_PASSWORD']) || getenv('MYSQL_PASSWORD') ? 'DEFINIDA' : 'NO DEFINIDA',
        ],
        
        // Información del servidor
        'servidor_info' => [
            'PHP_VERSION' => PHP_VERSION,
            'PDO_DISPONIBLE' => extension_loaded('pdo'),
            'PDO_MYSQL_DISPONIBLE' => extension_loaded('pdo_mysql'),
            'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'NO DISPONIBLE',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'NO DISPONIBLE'
        ],
        
        // Test de conexión
        'test_conexion' => ConexionRailway::probarConexion(),
        
        // Información adicional
        'debug_info' => [
            'all_env_vars' => array_keys($_ENV),
            'getenv_test' => getenv(),
            'server_vars' => array_keys($_SERVER)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
