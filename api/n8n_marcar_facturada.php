<?php
/**
 * api/n8n_marcar_facturada.php
 * API para n8n: Actualiza ventas con datos de facturación desde AFIP
 * Recibe el CAE y datos del comprobante generado en n8n
 * RELEVANT FILES: api/n8n_ventas_pendientes.php, api/bd_conexion.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST']);
    exit;
}

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    
    // Leer datos del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validar datos requeridos
    if (!isset($data['venta_id']) || !isset($data['cae'])) {
        throw new Exception('Faltan datos requeridos: venta_id y cae son obligatorios');
    }
    
    $ventaId = intval($data['venta_id']);
    $cae = $data['cae'];
    $comprobanteNumero = $data['comprobante_numero'] ?? '';
    $comprobanteFiscal = $data['comprobante_fiscal'] ?? "CAE: {$cae}";
    $vencimientoCae = $data['vencimiento_cae'] ?? null;
    $puntoVenta = $data['punto_venta'] ?? null;
    $numeroComprobante = $data['numero_comprobante_afip'] ?? null;
    
    // Verificar que la venta existe
    $stmt = $pdo->prepare("SELECT id, estado FROM ventas WHERE id = ?");
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        throw new Exception("Venta #{$ventaId} no encontrada");
    }
    
    if ($venta['estado'] !== 'completado') {
        throw new Exception("Venta #{$ventaId} no está en estado completado");
    }
    
    // Actualizar venta con datos de facturación
    $sqlUpdate = "
        UPDATE ventas 
        SET 
            cae = ?,
            comprobante_fiscal = ?
    ";
    
    $params = [$cae, $comprobanteFiscal];
    
    // Agregar campos opcionales si vienen
    if ($vencimientoCae) {
        $sqlUpdate .= ", fecha_vencimiento_cae = ?";
        $params[] = $vencimientoCae;
    }
    
    if ($puntoVenta) {
        $sqlUpdate .= ", punto_venta_afip = ?";
        $params[] = $puntoVenta;
    }
    
    if ($numeroComprobante) {
        $sqlUpdate .= ", numero_comprobante_afip = ?";
        $params[] = $numeroComprobante;
    }
    
    $sqlUpdate .= " WHERE id = ?";
    $params[] = $ventaId;
    
    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute($params);
    
    // Log de auditoría (opcional)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO auditoria_facturacion (venta_id, cae, fecha_facturacion, origen)
            VALUES (?, ?, NOW(), 'n8n')
        ");
        $logStmt->execute([$ventaId, $cae]);
    } catch (Exception $e) {
        // Si la tabla de auditoría no existe, no es crítico
        error_log("No se pudo registrar en auditoría: " . $e->getMessage());
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Venta facturada correctamente',
        'venta_id' => $ventaId,
        'cae' => $cae,
        'comprobante_fiscal' => $comprobanteFiscal,
        'fecha_actualizacion' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

