<?php
// Habilitar visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeceras para CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log detallado
$log_file = 'guardar_venta_simple.log';
file_put_contents($log_file, "=== " . date('Y-m-d H:i:s') . " - Iniciando proceso ===\n", FILE_APPEND);

// Incluir archivo de configuración
file_put_contents($log_file, "Cargando configuración...\n", FILE_APPEND);
require_once 'config.php';
file_put_contents($log_file, "Configuración cargada correctamente\n", FILE_APPEND);

try {
    // Comprobar si estamos recibiendo un POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido. Esta API solo acepta solicitudes POST.");
    }
    
    file_put_contents($log_file, "Método POST verificado\n", FILE_APPEND);
    
    // Obtener los datos de la solicitud
    $jsonData = file_get_contents('php://input');
    file_put_contents($log_file, "Datos recibidos: " . substr($jsonData, 0, 100) . "...\n", FILE_APPEND);
    
    // Guardar el JSON para depuración
    file_put_contents('ultima_venta_simple.json', $jsonData);
    
    // Validar los datos recibidos
    $data = json_decode($jsonData, true);
    if ($data === null) {
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }
    
    file_put_contents($log_file, "JSON decodificado correctamente\n", FILE_APPEND);
    
    // Extraer datos básicos de la venta
    $cliente = isset($data['cliente']) && isset($data['cliente']['name']) ? $data['cliente']['name'] : 'Consumidor Final';
    $metodo_pago = $data['paymentMethod'] ?? 'efectivo';
    $subtotal = floatval($data['subtotal'] ?? 0);
    $descuento = floatval($data['discount'] ?? 0);
    $monto_total = floatval($data['total'] ?? 0);
    $items = $data['cart'] ?? [];
    
    // Procesar los productos para mejor visualización
    $procesados = [];
    foreach ($items as $item) {
        // Asegurar que cada item tenga todas las propiedades necesarias
        $procesados[] = [
            'id' => $item['id'] ?? '',
            'code' => $item['code'] ?? $item['id'] ?? '',
            'name' => $item['name'] ?? 'Producto sin nombre',
            'price' => floatval($item['price'] ?? 0),
            'quantity' => intval($item['quantity'] ?? 1),
            'subtotal' => floatval($item['price'] ?? 0) * intval($item['quantity'] ?? 1)
        ];
    }
    
    // Obtener un ID único para la venta
    $comprobante = 'VENTA-' . date('YmdHis') . '-' . rand(1000, 9999);
    
    file_put_contents($log_file, "Datos extraídos correctamente\n", FILE_APPEND);
    
    // Guardar detalles para incluir en JSON
    $detalles = json_encode([
        'cliente' => $data['cliente'] ?? ['name' => 'Consumidor Final'],
        'items' => $procesados,
        'fecha' => date('Y-m-d H:i:s'),
        'paymentMethod' => $metodo_pago,
        'comprobante' => $comprobante
    ], JSON_UNESCAPED_UNICODE);
    
    file_put_contents($log_file, "Intentando conexión a base de datos\n", FILE_APPEND);
    
    // Verificar conexión a BD
    try {
        $pdo->query("SELECT 1");
        file_put_contents($log_file, "Conexión a BD exitosa\n", FILE_APPEND);
    } catch (PDOException $e) {
        file_put_contents($log_file, "Error de conexión a BD: " . $e->getMessage() . "\n", FILE_APPEND);
        throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
    }
    
    // Preparar SQL básico
    $sql = "INSERT INTO ventas (cliente_nombre, metodo_pago, subtotal, descuento, monto_total, numero_comprobante, detalles_json) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    file_put_contents($log_file, "SQL preparado: $sql\n", FILE_APPEND);
    
    try {
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $cliente,
            $metodo_pago,
            $subtotal,
            $descuento,
            $monto_total,
            $comprobante,
            $detalles
        ]);
        
        if (!$resultado) {
            throw new Exception("Error al insertar la venta: " . implode(", ", $stmt->errorInfo()));
        }
        
        $venta_id = $pdo->lastInsertId();
        file_put_contents($log_file, "Venta insertada con ID: $venta_id\n", FILE_APPEND);
        
    } catch (PDOException $e) {
        file_put_contents($log_file, "Error de PDO: " . $e->getMessage() . "\n", FILE_APPEND);
        throw new Exception("Error al ejecutar SQL: " . $e->getMessage());
    }
    
    // También guardar en archivo para redundancia
    try {
        if (!is_dir('ventas')) {
            mkdir('ventas', 0755);
            file_put_contents($log_file, "Directorio ventas creado\n", FILE_APPEND);
        }
        
        $archivo_json = "ventas/{$venta_id}_detalle.json";
        file_put_contents($archivo_json, $detalles);
        file_put_contents($log_file, "Datos guardados en archivo: $archivo_json\n", FILE_APPEND);
        
    } catch (Exception $e) {
        file_put_contents($log_file, "Error al guardar en archivo: " . $e->getMessage() . " (continuando...)\n", FILE_APPEND);
        // No detener por error en guardado de archivo
    }
    
    // Responder con éxito
    file_put_contents($log_file, "Enviando respuesta de éxito\n", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'message' => 'Venta registrada correctamente',
        'venta_id' => $venta_id,
        'numero_comprobante' => $comprobante
    ]);
    
    file_put_contents($log_file, "=== Proceso completado con éxito ===\n\n", FILE_APPEND);
    
} catch (Exception $e) {
    // Registrar error detallado
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Traza: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    file_put_contents($log_file, "=== Proceso terminado con error ===\n\n", FILE_APPEND);
    
    // Registrar error en archivo específico
    file_put_contents('error_ventas_simple.txt', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    
    // Enviar respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la venta: ' . $e->getMessage()
    ]);
} 