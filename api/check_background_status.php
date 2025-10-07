<?php
/**
 * 📊 API PARA VERIFICAR ESTADO DE PROCESOS EN BACKGROUND
 * Permite al frontend consultar si las tareas post-venta se completaron
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

function getBackgroundTaskStatus($ventaId) {
    $queueFile = "queue/venta_{$ventaId}.json";
    $errorFile = "queue/venta_{$ventaId}_error.json";
    
    if (file_exists($errorFile)) {
        return [
            'status' => 'error',
            'message' => 'Error en procesamiento background',
            'details' => json_decode(file_get_contents($errorFile), true)
        ];
    }
    
    if (file_exists($queueFile)) {
        $data = json_decode(file_get_contents($queueFile), true);
        $timeWaiting = time() - $data['timestamp'];
        
        return [
            'status' => 'pending',
            'message' => 'Procesando en background',
            'time_waiting' => $timeWaiting,
            'estimated_completion' => $timeWaiting > 60 ? 'soon' : 'within_1_minute'
        ];
    }
    
    return [
        'status' => 'completed',
        'message' => 'Todos los procesos completados',
        'completion_time' => 'unknown'
    ];
}

function getAFIPStatus($pdo, $ventaId) {
    try {
        // Verificar si existe comprobante AFIP para esta venta
        $stmt = $pdo->prepare("
            SELECT estado_afip, numero_comprobante_afip, cae, fecha_procesamiento 
            FROM ventas_afip 
            WHERE venta_id = ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$ventaId]);
        $afip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($afip) {
            return [
                'status' => $afip['estado_afip'],
                'numero_comprobante' => $afip['numero_comprobante_afip'],
                'cae' => $afip['cae'],
                'fecha' => $afip['fecha_procesamiento']
            ];
        }
        
        return ['status' => 'pending', 'message' => 'Pendiente de procesamiento AFIP'];
        
    } catch (Exception $e) {
        return ['status' => 'unknown', 'error' => $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $ventaId = $_GET['venta_id'] ?? null;
        
        if (!$ventaId) {
            throw new Exception('ID de venta requerido');
        }
        
        $backgroundStatus = getBackgroundTaskStatus($ventaId);
        $afipStatus = getAFIPStatus($pdo, $ventaId);
        
        echo json_encode([
            'success' => true,
            'venta_id' => $ventaId,
            'background_tasks' => $backgroundStatus,
            'afip_status' => $afipStatus,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método no permitido'
    ]);
}
?>
