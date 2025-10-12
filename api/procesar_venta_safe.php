<?php
/**
 * api/procesar_venta_safe.php
 * Versión simplificada y robusta de procesamiento de ventas
 * Sin dependencias externas, manejo de errores mejorado
 * RELEVANT FILES: api/config.php, api/bd_conexion.php
 */

// Activar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['cart']) || empty($data['cart'])) {
        throw new Exception('Datos de venta inválidos');
    }
    
    // Extraer datos
    $cliente = $data['cliente']['name'] ?? 'Consumidor Final';
    $metodoPago = $data['paymentMethod'] ?? 'efectivo';
    $subtotal = floatval($data['subtotal'] ?? 0);
    $descuento = floatval($data['discount'] ?? 0);
    $total = floatval($data['total'] ?? 0);
    $cart = $data['cart'];
    
    // Generar número de comprobante simple
    $numeroComprobante = 'V-' . date('YmdHis') . '-' . rand(1000, 9999);
    
    $pdo->beginTransaction();
    
    // Insertar venta
    $stmt = $pdo->prepare("
        INSERT INTO ventas (
            cliente_nombre, fecha, metodo_pago, subtotal, 
            descuento, monto_total, estado, numero_comprobante, 
            detalles_json
        ) VALUES (?, NOW(), ?, ?, ?, ?, 'completado', ?, ?)
    ");
    
    $detallesJson = json_encode(['cart' => $cart]);
    
    $stmt->execute([
        $cliente,
        $metodoPago,
        $subtotal,
        $descuento,
        $total,
        $numeroComprobante,
        $detallesJson
    ]);
    
    $ventaId = $pdo->lastInsertId();
    
    // Actualizar stock de productos
    foreach ($cart as $item) {
        $productoId = $item['id'];
        $cantidad = intval($item['quantity'] ?? 1);
        
        $stmtStock = $pdo->prepare("
            UPDATE productos 
            SET stock = stock - ?, 
                stock_actual = stock_actual - ? 
            WHERE id = ?
        ");
        
        try {
            $stmtStock->execute([$cantidad, $cantidad, $productoId]);
        } catch (Exception $e) {
            error_log("Error actualizando stock producto {$productoId}: " . $e->getMessage());
        }
    }
    
    // Registrar en caja si hay turno abierto
    try {
        $stmtTurno = $pdo->query("SELECT id FROM turnos_caja WHERE estado = 'abierto' LIMIT 1");
        $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
        
        if ($turno) {
            $campo = match(strtolower($metodoPago)) {
                'efectivo' => 'ventas_efectivo',
                'transferencia' => 'ventas_transferencia',
                'tarjeta' => 'ventas_tarjeta',
                'qr', 'mercadopago' => 'ventas_qr',
                default => 'ventas_efectivo'
            };
            
            $stmtUpdate = $pdo->prepare("
                UPDATE turnos_caja 
                SET {$campo} = {$campo} + ?,
                    cantidad_ventas = cantidad_ventas + 1
                WHERE id = ?
            ");
            $stmtUpdate->execute([$total, $turno['id']]);
        }
    } catch (Exception $e) {
        error_log("Error registrando en caja: " . $e->getMessage());
    }
    
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'mensaje' => 'Venta procesada exitosamente',
        'venta_id' => $ventaId,
        'numero_comprobante' => $numeroComprobante,
        'monto_total' => $total
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en procesar_venta_safe: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>

