<?php
/**
 * 🕐 CRON PARA PROCESAR TAREAS EN BACKGROUND
 * Este script debe ejecutarse cada minuto via cron job
 * 
 * Agregar a crontab:
 * * * * * * /usr/bin/php /path/to/api/cron_background_tasks.php >> /var/log/background_tasks.log 2>&1
 */

// Solo permitir ejecución desde línea de comandos
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse desde línea de comandos\n");
}

// Cambiar al directorio del script
chdir(__DIR__);

echo "[" . date('Y-m-d H:i:s') . "] Iniciando procesamiento de tareas en background\n";

try {
    require_once 'background_processor.php';
    
    $processor = new BackgroundTaskProcessor();
    $processor->processPendingTasks();
    
    echo "[" . date('Y-m-d H:i:s') . "] Procesamiento completado\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
?>
