<?php
/**
 * api/gestionar_configuracion_facturacion.php  
 * API para gestionar qué métodos de pago requieren facturación AFIP
 * RELEVANT FILES: config_facturacion.php, procesar_venta_ultra_rapida.php
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config_facturacion.php';

try {
    // GET: Obtener configuración actual
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $config = obtenerConfiguracionFacturacion();
        
        echo json_encode([
            'success' => true,
            'configuracion' => $config,
            'descripcion' => 'Configuración de facturación por método de pago'
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // POST/PUT: Actualizar configuración
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['metodo_pago'])) {
            throw new Exception('Método de pago no especificado');
        }
        
        $result = actualizarConfiguracionFacturacion(
            $data['metodo_pago'],
            $data['requiere_factura'] ?? false
        );
        
        if ($result['success']) {
            $config_actualizada = obtenerConfiguracionFacturacion();
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'configuracion' => $config_actualizada
            ], JSON_PRETTY_PRINT);
        } else {
            throw new Exception($result['error']);
        }
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>






