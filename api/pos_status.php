<?php
/**
 * 🚨 ENDPOINT DE EMERGENCIA PARA EL POS
 * Endpoint simplificado solo para verificar estado de caja
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si hay turno abierto
    $stmt = $pdo->prepare("SELECT * FROM turnos_caja WHERE estado = 'abierto' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($turno) {
        // Hay caja abierta
        echo json_encode([
            'success' => true,
            'estado' => 'abierta',
            'caja_abierta' => true,
            'caja' => [
                'id' => $turno['id'],
                'estado' => 'abierta',
                'monto_apertura' => $turno['monto_apertura'],
                'usuario_id' => $turno['usuario_id']
            ],
            'totales' => [
                'efectivo_fisico' => floatval($turno['efectivo_teorico']),
                'efectivo_teorico' => floatval($turno['efectivo_teorico']),
                'total_ventas' => floatval($turno['ventas_efectivo']) + floatval($turno['ventas_transferencia']) + floatval($turno['ventas_tarjeta']) + floatval($turno['ventas_qr'])
            ],
            'efectivo_disponible' => floatval($turno['efectivo_teorico']),
            'message' => 'Caja abierta - POS operativo'
        ]);
    } else {
        // No hay caja abierta
        echo json_encode([
            'success' => true,
            'estado' => 'cerrada',
            'caja_abierta' => false,
            'caja' => null,
            'totales' => [
                'efectivo_fisico' => 0,
                'efectivo_teorico' => 0,
                'total_ventas' => 0
            ],
            'efectivo_disponible' => 0,
            'message' => 'Caja cerrada - Debe abrir caja para operar'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'estado' => 'error',
        'caja_abierta' => false
    ]);
}
?>























