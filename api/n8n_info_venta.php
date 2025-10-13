<?php
/**
 * api/n8n_info_venta.php
 * API para n8n: Obtiene información detallada de UNA venta específica
 * Útil para debugging o reprocesamiento individual
 * RELEVANT FILES: api/n8n_ventas_pendientes.php
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

try {
    $pdo = Conexion::obtenerConexion();
    
    if (!isset($_GET['id'])) {
        throw new Exception('Parámetro id es requerido');
    }
    
    $ventaId = intval($_GET['id']);
    
    $sql = "
        SELECT 
            v.*,
            DATE_FORMAT(v.fecha, '%Y-%m-%d %H:%i:%s') as fecha_formateada,
            u.nombre as vendedor_nombre,
            u.username as vendedor_username
        FROM ventas v
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => "Venta #{$ventaId} no encontrada"
        ]);
        exit;
    }
    
    // Decodificar productos
    if ($venta['detalles_json']) {
        $detalles = json_decode($venta['detalles_json'], true);
        $venta['productos'] = $detalles['cart'] ?? [];
    } else {
        $venta['productos'] = [];
    }
    
    // Estado de facturación
    $venta['estado_facturacion'] = [
        'facturada' => !empty($venta['cae']),
        'tiene_cae' => !empty($venta['cae']),
        'tiene_comprobante_fiscal' => !empty($venta['comprobante_fiscal'])
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'venta' => $venta
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

