<?php
/**
 * PROCESADOR ASÍNCRONO DE FACTURAS AFIP
 * 
 * Sistema optimizado para procesamiento en background de facturas electrónicas
 * Mejora significativa en tiempos de respuesta y UX
 */

require_once 'bd_conexion.php';
require_once 'afip_service.php';
require_once 'afip_logger.php';

class AFIPAsyncProcessor {
    
    private $pdo;
    private $afip_service;
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->afip_service = new AFIPService();
        $this->initializeAsyncTables();
    }
    
    /**
     * 🚀 ENCOLAR VENTA PARA PROCESAMIENTO ASÍNCRONO
     * Respuesta inmediata al usuario, procesamiento en background
     */
    public function enqueueFacturacion($venta_id, $priority = 'normal') {
        try {
            logAfipInfo("Encolando venta para facturación asíncrona", [
                'venta_id' => $venta_id,
                'priority' => $priority
            ]);
            
            // Insertar en cola de procesamiento
            $stmt = $this->pdo->prepare("
                INSERT INTO afip_processing_queue (
                    venta_id, 
                    status, 
                    priority, 
                    created_at, 
                    retry_count,
                    next_attempt
                ) VALUES (?, 'pending', ?, NOW(), 0, NOW())
                ON DUPLICATE KEY UPDATE 
                    status = 'pending',
                    retry_count = 0,
                    next_attempt = NOW()
            ");
            
            $stmt->execute([$venta_id, $priority]);
            
            return [
                'success' => true,
                'message' => 'Venta encolada para facturación',
                'estimated_time' => '2-5 segundos',
                'queue_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (Exception $e) {
            logAfipError("Error encolando venta para facturación", [
                'venta_id' => $venta_id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * ⚡ PROCESAR COLA DE FACTURAS EN BACKGROUND
     * Procesamiento optimizado con manejo de errores y reintentos
     */
    public function processQueue($max_items = 10) {
        logAfipInfo("Iniciando procesamiento de cola AFIP", ['max_items' => $max_items]);
        
        // Obtener elementos pendientes de la cola (por prioridad)
        $stmt = $this->pdo->prepare("
            SELECT * FROM afip_processing_queue 
            WHERE status = 'pending' 
            AND next_attempt <= NOW()
            ORDER BY 
                CASE priority 
                    WHEN 'high' THEN 1 
                    WHEN 'normal' THEN 2 
                    WHEN 'low' THEN 3 
                END,
                created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$max_items]);
        $queue_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processed = 0;
        $errors = 0;
        
        foreach ($queue_items as $item) {
            $result = $this->processQueueItem($item);
            if ($result['success']) {
                $processed++;
            } else {
                $errors++;
            }
        }
        
        logAfipInfo("Cola procesada", [
            'items_procesados' => $processed,
            'errors' => $errors,
            'total_items' => count($queue_items)
        ]);
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($queue_items)
        ];
    }
    
    /**
     * 🔄 PROCESAR ELEMENTO INDIVIDUAL DE LA COLA
     */
    private function processQueueItem($item) {
        $venta_id = $item['venta_id'];
        $queue_id = $item['id'];
        
        try {
            // Marcar como procesando
            $this->updateQueueStatus($queue_id, 'processing');
            
            logAfipInfo("Procesando factura asíncrona", [
                'venta_id' => $venta_id,
                'queue_id' => $queue_id,
                'retry_count' => $item['retry_count']
            ]);
            
            // Procesar factura con timeout optimizado
            $start_time = microtime(true);
            $resultado = $this->afip_service->generarComprobanteFiscal($venta_id);
            $processing_time = microtime(true) - $start_time;
            
            if ($resultado['success']) {
                // Éxito - marcar como completado
                $this->updateQueueStatus($queue_id, 'completed', $resultado, $processing_time);
                
                // Actualizar venta con datos fiscales
                $this->updateVentaWithFiscalData($venta_id, $resultado);
                
                logAfipInfo("Factura procesada exitosamente", [
                    'venta_id' => $venta_id,
                    'processing_time' => round($processing_time, 2) . 's',
                    'cae' => $resultado['comprobante']['comprobante']['cae'] ?? 'N/A'
                ]);
                
                return ['success' => true];
                
            } else {
                // Error - manejar reintentos
                return $this->handleQueueError($queue_id, $item, $resultado['error'], $processing_time);
            }
            
        } catch (Exception $e) {
            logAfipError("Error procesando elemento de cola", [
                'venta_id' => $venta_id,
                'queue_id' => $queue_id,
                'error' => $e->getMessage()
            ]);
            
            return $this->handleQueueError($queue_id, $item, $e->getMessage());
        }
    }
    
    /**
     * 🛠️ MANEJAR ERRORES Y REINTENTOS
     */
    private function handleQueueError($queue_id, $item, $error, $processing_time = 0) {
        $retry_count = $item['retry_count'] + 1;
        $max_retries = 3;
        
        if ($retry_count <= $max_retries) {
            // Calcular próximo intento con backoff exponencial
            $delay_minutes = pow(2, $retry_count); // 2, 4, 8 minutos
            $next_attempt = date('Y-m-d H:i:s', strtotime("+{$delay_minutes} minutes"));
            
            $this->updateQueueStatus($queue_id, 'pending', [
                'error' => $error,
                'retry_count' => $retry_count,
                'next_attempt' => $next_attempt
            ], $processing_time);
            
            logAfipWarning("Reintentando factura después de error", [
                'queue_id' => $queue_id,
                'retry_count' => $retry_count,
                'next_attempt' => $next_attempt,
                'error' => $error
            ]);
            
            return ['success' => false, 'retry_scheduled' => true];
            
        } else {
            // Máximo de reintentos alcanzado
            $this->updateQueueStatus($queue_id, 'failed', [
                'error' => $error,
                'final_retry_count' => $retry_count
            ], $processing_time);
            
            logAfipError("Factura falló después de máximo de reintentos", [
                'queue_id' => $queue_id,
                'venta_id' => $item['venta_id'],
                'retry_count' => $retry_count,
                'error' => $error
            ]);
            
            return ['success' => false, 'failed_permanently' => true];
        }
    }
    
    /**
     * 📊 OBTENER ESTADO DE FACTURACIÓN
     */
    public function getFacturacionStatus($venta_id) {
        $stmt = $this->pdo->prepare("
            SELECT q.*, v.numero_comprobante, v.comprobante_fiscal, v.cae
            FROM afip_processing_queue q
            LEFT JOIN ventas v ON q.venta_id = v.id
            WHERE q.venta_id = ?
            ORDER BY q.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$venta_id]);
        $queue_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$queue_item) {
            return [
                'status' => 'not_found',
                'message' => 'No se encontró información de facturación'
            ];
        }
        
        $response = [
            'status' => $queue_item['status'],
            'venta_id' => $venta_id,
            'created_at' => $queue_item['created_at'],
            'processing_time' => $queue_item['processing_time'],
            'retry_count' => $queue_item['retry_count']
        ];
        
        switch ($queue_item['status']) {
            case 'pending':
                $response['message'] = 'Factura en cola de procesamiento';
                $response['estimated_completion'] = $queue_item['next_attempt'];
                break;
                
            case 'processing':
                $response['message'] = 'Generando factura electrónica...';
                break;
                
            case 'completed':
                $response['message'] = 'Factura generada exitosamente';
                $response['comprobante_fiscal'] = $queue_item['comprobante_fiscal'];
                $response['cae'] = $queue_item['cae'];
                if ($queue_item['result_data']) {
                    $result_data = json_decode($queue_item['result_data'], true);
                    $response['datos_fiscales'] = $result_data['datos_fiscales'] ?? null;
                }
                break;
                
            case 'failed':
                $response['message'] = 'Error generando factura';
                if ($queue_item['error_data']) {
                    $error_data = json_decode($queue_item['error_data'], true);
                    $response['error'] = $error_data['error'] ?? 'Error desconocido';
                }
                break;
        }
        
        return $response;
    }
    
    /**
     * 🗃️ ACTUALIZAR ESTADO EN COLA
     */
    private function updateQueueStatus($queue_id, $status, $data = null, $processing_time = null) {
        $fields = ['status = ?'];
        $params = [$status];
        
        if ($processing_time !== null) {
            $fields[] = 'processing_time = ?';
            $params[] = round($processing_time, 2);
        }
        
        if ($data) {
            if ($status === 'completed') {
                $fields[] = 'result_data = ?';
                $params[] = json_encode($data);
            } else {
                $fields[] = 'error_data = ?';
                $params[] = json_encode($data);
                
                if (isset($data['retry_count'])) {
                    $fields[] = 'retry_count = ?';
                    $params[] = $data['retry_count'];
                }
                
                if (isset($data['next_attempt'])) {
                    $fields[] = 'next_attempt = ?';
                    $params[] = $data['next_attempt'];
                }
            }
        }
        
        $fields[] = 'updated_at = NOW()';
        $params[] = $queue_id;
        
        $sql = "UPDATE afip_processing_queue SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * 💾 ACTUALIZAR VENTA CON DATOS FISCALES
     */
    private function updateVentaWithFiscalData($venta_id, $resultado) {
        try {
            $fiscal = $resultado['comprobante']['comprobante'];
            
            $stmt = $this->pdo->prepare("
                UPDATE ventas 
                SET comprobante_fiscal = ?, cae = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $fiscal['numero_comprobante'],
                $fiscal['cae'],
                $venta_id
            ]);
            
        } catch (Exception $e) {
            logAfipError("Error actualizando venta con datos fiscales", [
                'venta_id' => $venta_id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 🏗️ INICIALIZAR TABLAS PARA PROCESAMIENTO ASÍNCRONO
     */
    private function initializeAsyncTables() {
        // Tabla de cola de procesamiento
        $sql_queue = "
        CREATE TABLE IF NOT EXISTS afip_processing_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venta_id INT NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
            retry_count INT DEFAULT 0,
            processing_time DECIMAL(5,2) NULL,
            result_data TEXT NULL,
            error_data TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            next_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_venta (venta_id),
            INDEX idx_status_priority (status, priority),
            INDEX idx_next_attempt (next_attempt),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB;
        ";
        
        // Tabla de métricas de performance
        $sql_metrics = "
        CREATE TABLE IF NOT EXISTS afip_performance_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            hour TINYINT NOT NULL,
            total_processed INT DEFAULT 0,
            total_failed INT DEFAULT 0,
            avg_processing_time DECIMAL(5,2) DEFAULT 0,
            max_processing_time DECIMAL(5,2) DEFAULT 0,
            min_processing_time DECIMAL(5,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date_hour (date, hour)
        ) ENGINE=InnoDB;
        ";
        
        $this->pdo->exec($sql_queue);
        $this->pdo->exec($sql_metrics);
    }
    
    /**
     * 📈 OBTENER MÉTRICAS DE PERFORMANCE
     */
    public function getPerformanceMetrics($days = 7) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_facturas,
                AVG(processing_time) as avg_time,
                MAX(processing_time) as max_time,
                MIN(processing_time) as min_time,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM afip_processing_queue 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * 🎯 ENDPOINT PARA PROCESAMIENTO ASÍNCRONO
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    $processor = new AFIPAsyncProcessor();
    
    switch ($action) {
        case 'enqueue':
            $venta_id = $_POST['venta_id'] ?? null;
            $priority = $_POST['priority'] ?? 'normal';
            
            if (!$venta_id) {
                echo json_encode(['success' => false, 'error' => 'venta_id requerido']);
                exit;
            }
            
            $result = $processor->enqueueFacturacion($venta_id, $priority);
            echo json_encode($result);
            break;
            
        case 'process':
            $max_items = $_POST['max_items'] ?? 10;
            $result = $processor->processQueue($max_items);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    $processor = new AFIPAsyncProcessor();
    
    switch ($action) {
        case 'status':
            $venta_id = $_GET['venta_id'] ?? null;
            
            if (!$venta_id) {
                echo json_encode(['success' => false, 'error' => 'venta_id requerido']);
                exit;
            }
            
            $result = $processor->getFacturacionStatus($venta_id);
            echo json_encode($result);
            break;
            
        case 'metrics':
            $days = $_GET['days'] ?? 7;
            $result = $processor->getPerformanceMetrics($days);
            echo json_encode(['success' => true, 'metrics' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
}
?> 