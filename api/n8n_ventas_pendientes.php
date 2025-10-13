<?php
/**
 * api/n8n_ventas_pendientes.php
 * API para n8n: Obtiene ventas pendientes de facturación
 * Permite a n8n consumir ventas y procesarlas con AFIP
 * RELEVANT FILES: api/n8n_marcar_facturada.php, api/bd_conexion.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    
    // Parámetros opcionales
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
    $desde_id = isset($_GET['desde_id']) ? intval($_GET['desde_id']) : 0;
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-7 days'));
    
    // Query: Ventas completadas que NO tienen CAE (no facturadas)
    $sql = "
        SELECT 
            id,
            cliente_nombre,
            DATE_FORMAT(fecha, '%Y-%m-%d %H:%i:%s') as fecha,
            metodo_pago,
            subtotal,
            descuento,
            monto_total,
            numero_comprobante,
            detalles_json,
            tipo_comprobante,
            condicion_fiscal,
            impuestos_total,
            usuario_id,
            caja_id
        FROM ventas
        WHERE estado = 'completado'
        AND (cae IS NULL OR cae = '' OR comprobante_fiscal IS NULL OR comprobante_fiscal = '')
        AND fecha >= ?
        AND id > ?
        ORDER BY id ASC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_desde, $desde_id, $limite]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar detalles_json para que sea un objeto
    foreach ($ventas as &$venta) {
        if ($venta['detalles_json']) {
            $detalles = json_decode($venta['detalles_json'], true);
            $venta['productos'] = $detalles['cart'] ?? [];
        } else {
            $venta['productos'] = [];
        }
        // No enviar el JSON raw
        unset($venta['detalles_json']);
    }
    
    // Estadísticas
    $stmtStats = $pdo->prepare("
        SELECT COUNT(*) as total_pendientes
        FROM ventas
        WHERE estado = 'completado'
        AND (cae IS NULL OR cae = '' OR comprobante_fiscal IS NULL OR comprobante_fiscal = '')
        AND fecha >= ?
    ");
    $stmtStats->execute([$fecha_desde]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'total_pendientes' => intval($stats['total_pendientes']),
        'cantidad_devuelta' => count($ventas),
        'ventas' => $ventas,
        'paginacion' => [
            'limite' => $limite,
            'desde_id' => $desde_id,
            'proximo_desde_id' => count($ventas) > 0 ? end($ventas)['id'] : $desde_id
        ],
        'filtros_aplicados' => [
            'fecha_desde' => $fecha_desde,
            'limite' => $limite
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'codigo' => $e->getCode()
    ]);
}
?>

