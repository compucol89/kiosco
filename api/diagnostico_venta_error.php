<?php
/**
 * Diagnóstico de errores en procesar_venta_ultra_rapida.php
 * Ver errores específicos del servidor
 */

// Activar todos los errores para ver qué pasa
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

try {
    echo json_encode([
        'success' => true,
        'servidor' => [
            'php_version' => phpversion(),
            'extensions' => get_loaded_extensions(),
            'error_log_path' => ini_get('error_log'),
            'display_errors' => ini_get('display_errors'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ],
        'archivos' => [
            'procesar_venta_exists' => file_exists('procesar_venta_ultra_rapida.php'),
            'procesar_venta_readable' => is_readable('procesar_venta_ultra_rapida.php'),
            'bd_conexion_exists' => file_exists('bd_conexion.php'),
            'bd_conexion_readable' => is_readable('bd_conexion.php')
        ],
        'instrucciones' => 'Revisa los logs de error del servidor PHP para ver el error exacto'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

