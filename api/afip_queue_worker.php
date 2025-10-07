<?php
/**
 * WORKER DE COLA AFIP - PROCESAMIENTO CONTINUO DE FACTURAS
 * 
 * Script optimizado para procesar facturas electrónicas en background
 * Puede ejecutarse via cron job o de forma continua
 */

require_once 'afip_async_processor.php';

class AFIPQueueWorker {
    
    private $processor;
    private $is_running = false;
    private $max_execution_time;
    private $batch_size;
    private $sleep_interval;
    
    public function __construct($max_execution_time = 300, $batch_size = 5, $sleep_interval = 10) {
        $this->processor = new AFIPAsyncProcessor();
        $this->max_execution_time = $max_execution_time; // 5 minutos por defecto
        $this->batch_size = $batch_size;
        $this->sleep_interval = $sleep_interval; // 10 segundos entre batches
        
        // Configurar tiempo límite de ejecución
        set_time_limit($this->max_execution_time + 60);
    }
    
    /**
     * 🔄 EJECUTAR WORKER DE FORMA CONTINUA
     */
    public function runContinuous() {
        echo "🚀 Iniciando worker continuo de cola AFIP\n";
        echo "⏱️ Tiempo máximo: {$this->max_execution_time}s\n";
        echo "📦 Tamaño de lote: {$this->batch_size}\n";
        echo "💤 Intervalo: {$this->sleep_interval}s\n";
        echo str_repeat("-", 50) . "\n";
        
        $this->is_running = true;
        $start_time = time();
        $total_processed = 0;
        $total_errors = 0;
        
        while ($this->is_running) {
            // Verificar tiempo límite
            if (time() - $start_time >= $this->max_execution_time) {
                echo "⏰ Tiempo límite alcanzado, terminando worker\n";
                break;
            }
            
            try {
                // Procesar lote
                $result = $this->processor->processQueue($this->batch_size);
                
                if ($result['total'] > 0) {
                    $total_processed += $result['processed'];
                    $total_errors += $result['errors'];
                    
                    echo sprintf(
                        "[%s] Procesado: %d/%d (✅%d ❌%d)\n",
                        date('H:i:s'),
                        $result['processed'],
                        $result['total'],
                        $result['processed'],
                        $result['errors']
                    );
                } else {
                    echo sprintf("[%s] Cola vacía, esperando...\n", date('H:i:s'));
                }
                
            } catch (Exception $e) {
                echo sprintf("[%s] ❌ Error en worker: %s\n", date('H:i:s'), $e->getMessage());
                $total_errors++;
            }
            
            // Dormir antes del siguiente lote
            sleep($this->sleep_interval);
        }
        
        $execution_time = time() - $start_time;
        echo str_repeat("-", 50) . "\n";
        echo "📊 Resumen de ejecución:\n";
        echo "  • Tiempo total: {$execution_time}s\n";
        echo "  • Facturas procesadas: {$total_processed}\n";
        echo "  • Errores: {$total_errors}\n";
        echo "  • Tasa de éxito: " . ($total_processed > 0 ? round(($total_processed / ($total_processed + $total_errors)) * 100, 1) : 0) . "%\n";
    }
    
    /**
     * ⚡ EJECUTAR LOTE ÚNICO (para cron jobs)
     */
    public function runBatch() {
        echo "📦 Ejecutando lote único de procesamiento AFIP\n";
        
        try {
            $result = $this->processor->processQueue($this->batch_size);
            
            if ($result['total'] > 0) {
                echo sprintf(
                    "✅ Lote completado: %d/%d procesados (❌%d errores)\n",
                    $result['processed'],
                    $result['total'],
                    $result['errors']
                );
                
                return [
                    'success' => true,
                    'processed' => $result['processed'],
                    'errors' => $result['errors'],
                    'total' => $result['total']
                ];
            } else {
                echo "ℹ️ Cola vacía, nada que procesar\n";
                return [
                    'success' => true,
                    'processed' => 0,
                    'errors' => 0,
                    'total' => 0,
                    'message' => 'Cola vacía'
                ];
            }
            
        } catch (Exception $e) {
            echo "❌ Error ejecutando lote: " . $e->getMessage() . "\n";
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🛑 DETENER WORKER
     */
    public function stop() {
        $this->is_running = false;
        echo "🛑 Deteniendo worker...\n";
    }
    
    /**
     * 📊 MOSTRAR ESTADÍSTICAS DE LA COLA
     */
    public function showQueueStats() {
        try {
            $stats = $this->getQueueStatistics();
            
            echo "📊 ESTADÍSTICAS DE COLA AFIP\n";
            echo str_repeat("=", 40) . "\n";
            echo "Pendientes:   " . str_pad($stats['pending'], 6, ' ', STR_PAD_LEFT) . "\n";
            echo "Procesando:   " . str_pad($stats['processing'], 6, ' ', STR_PAD_LEFT) . "\n";
            echo "Completadas:  " . str_pad($stats['completed'], 6, ' ', STR_PAD_LEFT) . "\n";
            echo "Fallidas:     " . str_pad($stats['failed'], 6, ' ', STR_PAD_LEFT) . "\n";
            echo str_repeat("-", 40) . "\n";
            echo "Total:        " . str_pad($stats['total'], 6, ' ', STR_PAD_LEFT) . "\n";
            
            if ($stats['total'] > 0) {
                $success_rate = round(($stats['completed'] / $stats['total']) * 100, 1);
                echo "Tasa éxito:   " . str_pad($success_rate . '%', 6, ' ', STR_PAD_LEFT) . "\n";
            }
            
            echo str_repeat("=", 40) . "\n";
            
        } catch (Exception $e) {
            echo "❌ Error obteniendo estadísticas: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 📈 OBTENER ESTADÍSTICAS DE LA COLA
     */
    private function getQueueStatistics() {
        $pdo = Conexion::obtenerConexion();
        
        $stmt = $pdo->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM afip_processing_queue 
            GROUP BY status
        ");
        
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'pending' => $results['pending'] ?? 0,
            'processing' => $results['processing'] ?? 0,
            'completed' => $results['completed'] ?? 0,
            'failed' => $results['failed'] ?? 0,
            'total' => array_sum($results)
        ];
    }
}

// ========== EJECUCIÓN DEL SCRIPT ==========

// Detectar método de ejecución
$mode = $_GET['mode'] ?? $argv[1] ?? 'batch';
$action = $_GET['action'] ?? $argv[2] ?? 'run';

$worker = new AFIPQueueWorker();

switch ($action) {
    case 'run':
        if ($mode === 'continuous') {
            // Modo continuo
            $worker->runContinuous();
        } else {
            // Modo lote (para cron)
            $result = $worker->runBatch();
            
            // Si se ejecuta via web, devolver JSON
            if (isset($_GET['mode'])) {
                header('Content-Type: application/json');
                echo json_encode($result);
            }
        }
        break;
        
    case 'stats':
        $worker->showQueueStats();
        break;
        
    case 'metrics':
        if (isset($_GET['action'])) {
            header('Content-Type: application/json');
            $processor = new AFIPAsyncProcessor();
            $metrics = $processor->getPerformanceMetrics(7);
            echo json_encode(['success' => true, 'metrics' => $metrics]);
        } else {
            echo "📈 Métricas de performance (últimos 7 días):\n";
            $processor = new AFIPAsyncProcessor();
            $metrics = $processor->getPerformanceMetrics(7);
            
            foreach ($metrics as $metric) {
                echo sprintf(
                    "%s: %d facturas, %.2fs promedio, %.1f%% éxito\n",
                    $metric['date'],
                    $metric['total_facturas'],
                    $metric['avg_time'] ?? 0,
                    $metric['total_facturas'] > 0 ? ($metric['successful'] / $metric['total_facturas']) * 100 : 0
                );
            }
        }
        break;
        
    default:
        echo "🤖 Worker de Cola AFIP\n\n";
        echo "Uso:\n";
        echo "  php afip_queue_worker.php [batch|continuous] [run|stats|metrics]\n";
        echo "  curl http://localhost/kiosco/api/afip_queue_worker.php?mode=batch&action=run\n\n";
        echo "Modos:\n";
        echo "  batch      - Procesar un lote y terminar (para cron jobs)\n";
        echo "  continuous - Ejecutar de forma continua\n\n";
        echo "Acciones:\n";
        echo "  run     - Procesar cola\n";
        echo "  stats   - Mostrar estadísticas\n";
        echo "  metrics - Mostrar métricas de performance\n";
}
?> 