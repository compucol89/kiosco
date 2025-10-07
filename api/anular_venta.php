<?php
require_once 'cors_middleware.php';
require_once 'bd_conexion.php';

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Leer datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $venta_id = $input['venta_id'] ?? null;
    $motivo = $input['motivo'] ?? '';
    $usuario = $input['usuario'] ?? 'Administrador';
    $usuario_role = $input['usuario_role'] ?? '';
    
    // Validaciones básicas
    if (!$venta_id) {
        throw new Exception('ID de venta requerido');
    }
    
    if (!$motivo || strlen(trim($motivo)) < 3) {
        throw new Exception('Motivo de anulación requerido (mínimo 3 caracteres)');
    }
    
    // VERIFICACIÓN DE PERMISOS: Solo administradores pueden anular ventas
    if ($usuario_role !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Solo los administradores pueden anular ventas'
        ]);
        exit;
    }
    
    // Obtener conexión
    $pdo = Conexion::obtenerConexion();
    if (!$pdo) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Verificar que la venta existe y está completada
    $stmt = $pdo->prepare("SELECT id, estado, monto_total, cliente_nombre, metodo_pago FROM ventas WHERE id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }
    
    if ($venta['estado'] !== 'completado' && $venta['estado'] !== 'completada') {
        throw new Exception('Solo se pueden anular ventas completadas');
    }
    
    // Actualizar el estado de la venta a anulado
    $stmt = $pdo->prepare("
        UPDATE ventas 
        SET estado = 'anulado', 
            observaciones = CONCAT(COALESCE(observaciones, ''), 
                                 'ANULADA el ', NOW(), ' por ', ?, 
                                 '. Motivo: ', ?, '. ')
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$usuario, $motivo, $venta_id]);
    
    if (!$result) {
        throw new Exception('Error al anular la venta');
    }
    
    // Registrar movimiento en caja (egreso por anulación)
    $concepto = "Anulación de venta #{$venta_id} - {$venta['cliente_nombre']} - {$motivo}";
    $stmt = $pdo->prepare("
        INSERT INTO caja_movimientos (tipo, concepto, monto, venta_id, usuario, fecha, observaciones) 
        VALUES ('egreso', ?, ?, ?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $concepto,
        $venta['monto_total'],
        $venta_id,
        $usuario,
        "Venta anulada por motivo: {$motivo}"
    ]);
    
    // TODO: Aquí se podría implementar la reversión del stock si es necesario
    // Obtener detalles de la venta y aumentar el stock de los productos
    
    // Registrar en log de auditoría (si existe tabla de auditoría)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_ventas (venta_id, accion, usuario, fecha, detalles) 
            VALUES (?, 'ANULACION', ?, NOW(), ?)
        ");
        $stmt->execute([
            $venta_id, 
            $usuario, 
            json_encode([
                'motivo' => $motivo,
                'venta_original' => $venta,
                'timestamp' => date('Y-m-d H:i:s')
            ])
        ]);
    } catch (Exception $e) {
        // Si no existe tabla de auditoría, continuamos (no es crítico)
        error_log("Advertencia: No se pudo registrar en auditoría: " . $e->getMessage());
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Venta anulada correctamente',
        'data' => [
            'venta_id' => $venta_id,
            'estado_anterior' => $venta['estado'],
            'estado_nuevo' => 'anulado',
            'motivo' => $motivo,
            'usuario' => $usuario,
            'fecha_anulacion' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en anular_venta.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al anular la venta: ' . $e->getMessage()
    ]);
}
?> 