<?php
/**
 * 🔄 PROCESADOR DE TAREAS EN BACKGROUND
 * Procesa todas las tareas que no bloquean la respuesta al usuario
 */

require_once 'config.php';

class BackgroundTaskProcessor {
    
    private $queueDir = 'queue/';
    private $maxRetries = 3;
    
    public function __construct() {
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
    }
    
    /**
     * 🚀 Procesar todas las tareas pendientes
     */
    public function processPendingTasks() {
        $files = glob($this->queueDir . 'venta_*.json');
        
        if (empty($files)) {
            echo "📭 No hay tareas pendientes en la cola\n";
            return;
        }
        
        echo "📋 Encontradas " . count($files) . " tareas pendientes\n";
        
        foreach ($files as $file) {
            $this->processTaskFile($file);
        }
        
        echo "✅ Procesamiento de cola completado\n";
    }
    
    /**
     * 📋 Procesar un archivo de tarea específico
     */
    private function processTaskFile($file) {
        try {
            $data = json_decode(file_get_contents($file), true);
            if (!$data) return;
            
            $ventaId = $data['venta_id'];
            
            echo "🔄 Procesando venta ID: {$ventaId}\n";
            
            // 1. Comprobante AFIP
            $this->processAFIPReceipt($data);
            
            // 2. Facturación electrónica
            $this->processElectronicInvoicing($data);
            
            // 3. Métricas y logging adicional
            $this->processAdditionalLogging($data);
            
            // Eliminar tarea completada
            unlink($file);
            
            echo "✅ Venta {$ventaId} procesada completamente\n";
            
        } catch (Exception $e) {
            echo "❌ Error procesando {$file}: " . $e->getMessage() . "\n";
            
            // Marcar como error para revisión manual
            $errorFile = str_replace('.json', '_error.json', $file);
            rename($file, $errorFile);
        }
    }
    
    /**
     * 🧾 Procesar comprobante AFIP
     */
    private function processAFIPReceipt($data) {
        try {
            if (file_exists('afip_service.php')) {
                require_once 'afip_service.php';
                
                $resultado = generarComprobanteFiscalDesdVenta($data['venta_id']);
                
                if ($resultado['success']) {
                    echo "  ✅ Comprobante AFIP generado\n";
                } else {
                    echo "  ⚠️ Error AFIP: " . $resultado['error'] . "\n";
                }
            }
        } catch (Exception $e) {
            echo "  ❌ Error comprobante AFIP: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * ⚡ Procesar facturación electrónica
     */
    private function processElectronicInvoicing($data) {
        try {
            if (file_exists('afip_async_processor.php')) {
                require_once 'afip_async_processor.php';
                
                $processor = new AFIPAsyncProcessor();
                $resultado = $processor->processImmediate($data['venta_id']);
                
                if ($resultado['success']) {
                    echo "  ✅ Facturación electrónica completada\n";
                } else {
                    echo "  ⚠️ Error facturación: " . $resultado['error'] . "\n";
                }
            }
        } catch (Exception $e) {
            echo "  ❌ Error facturación: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 📊 Procesar métricas y logging adicional
     */
    private function processAdditionalLogging($data) {
        try {
            // Log de métricas y auditoría (notificaciones webhook/Telegram removidas)
            error_log("📊 Background processing completado para venta ID: " . $data['venta_id']);
            echo "  ✅ Métricas y logging completados\n";
        } catch (Exception $e) {
            echo "  ❌ Error en logging: " . $e->getMessage() . "\n";
        }
    }
}

// Ejecutar procesador si se llama directamente
if (php_sapi_name() === 'cli') {
    $processor = new BackgroundTaskProcessor();
    $processor->processPendingTasks();
}
?>
