<?php
/**
 * api/procesar_venta_simple_v2.php
 * Versión ultra-simplificada para resolver error 500 en producción
 * Sin dependencias externas, máxima compatibilidad
 */

// Headers PRIMERO antes de cualquier output
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Permitir GET y POST para debugging
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'mensaje' => 'API funcionando correctamente',
        'version' => 'v2-simple',
        'metodo_requerido' => 'POST'
    ]);
    exit();
}

try {
    require_once 'bd_conexion.php';
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    
    $pdo = Conexion::obtenerConexion();
    
    // Leer datos del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['cart'])) {
        throw new Exception('Datos inválidos');
    }
    
    // Extraer información
    $cliente = $data['cliente']['name'] ?? 'Consumidor Final';
    $metodoPago = $data['paymentMethod'] ?? 'efectivo';
    $subtotal = floatval($data['subtotal'] ?? 0);
    $descuento = floatval($data['discount'] ?? 0);
    $total = floatval($data['total'] ?? 0);
    
    // Número de comprobante simple
    $numeroComprobante = 'V' . date('YmdHis') . rand(100, 999);
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Insertar venta
    $stmt = $pdo->prepare("
        INSERT INTO ventas (
            cliente_nombre, fecha, metodo_pago, 
            subtotal, descuento, monto_total, 
            estado, numero_comprobante, detalles_json
        ) VALUES (?, NOW(), ?, ?, ?, ?, 'completado', ?, ?)
    ");
    
    $stmt->execute([
        $cliente,
        $metodoPago,
        $subtotal,
        $descuento,
        $total,
        $numeroComprobante,
        json_encode(['cart' => $data['cart']])
    ]);
    
    $ventaId = $pdo->lastInsertId();
    
    // Actualizar stock
    foreach ($data['cart'] as $item) {
        $productoId = $item['id'];
        $cantidad = intval($item['quantity'] ?? 1);
        
        $pdo->prepare("UPDATE productos SET stock = stock - ?, stock_actual = stock_actual - ? WHERE id = ?")
            ->execute([$cantidad, $cantidad, $productoId]);
    }
    
    // Actualizar turno de caja si existe
    $turno = $pdo->query("SELECT id FROM turnos_caja WHERE estado = 'abierto' LIMIT 1")->fetch();
    
    if ($turno) {
        $campo = match(strtolower($metodoPago)) {
            'efectivo' => 'ventas_efectivo',
            'transferencia' => 'ventas_transferencia',
            'tarjeta' => 'ventas_tarjeta',
            'qr' => 'ventas_qr',
            default => 'ventas_efectivo'
        };
        
        $pdo->prepare("UPDATE turnos_caja SET {$campo} = {$campo} + ?, cantidad_ventas = cantidad_ventas + 1 WHERE id = ?")
            ->execute([$total, $turno['id']]);
    }
    
    $pdo->commit();
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Venta procesada exitosamente',
        'venta_id' => $ventaId,
        'numero_comprobante' => $numeroComprobante,
        'monto_total' => $total,
        'metodo_pago' => $metodoPago
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_completo' => $e->getTraceAsString()
    ]);
}
?>

