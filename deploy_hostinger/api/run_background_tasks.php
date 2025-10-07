<?php
/**
 * 🚀 EJECUTOR MANUAL DE TAREAS EN BACKGROUND
 * Para ejecutar desde navegador cuando no se tiene cron configurado
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Evitar timeout
set_time_limit(60);

try {
    require_once 'background_processor.php';
    
    $processor = new BackgroundTaskProcessor();
    
    // Capturar salida
    ob_start();
    $processor->processPendingTasks();
    $output = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tareas en background procesadas',
        'output' => $output,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error procesando tareas: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
