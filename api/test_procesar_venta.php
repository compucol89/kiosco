<?php
/**
 * Test simulado de procesamiento de venta
 * Para identificar dónde falla exactamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

$pasos = [];

try {
    $pasos[] = '1. Script iniciado';
    
    // Test 1: Cargar bd_conexion.php
    require_once 'bd_conexion.php';
    $pasos[] = '2. bd_conexion.php cargado OK';
    
    // Test 2: Conectar a BD
    $pdo = Conexion::obtenerConexion();
    $pasos[] = '3. Conexión a BD establecida OK';
    
    // Test 3: Verificar tabla ventas
    $stmt = $pdo->query("SELECT COUNT(*) FROM ventas");
    $total = $stmt->fetchColumn();
    $pasos[] = "4. Tabla ventas accesible OK ({$total} ventas)";
    
    // Test 4: Intentar insertar venta de prueba
    $testData = [
        'cliente_nombre' => 'TEST',
        'metodo_pago' => 'efectivo',
        'monto_total' => 100,
        'subtotal' => 100,
        'descuento' => 0,
        'estado' => 'completado',
        'detalles_json' => json_encode(['cart' => []])
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO ventas (cliente_nombre, metodo_pago, monto_total, subtotal, descuento, estado, detalles_json)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $testData['cliente_nombre'],
        $testData['metodo_pago'],
        $testData['monto_total'],
        $testData['subtotal'],
        $testData['descuento'],
        $testData['estado'],
        $testData['detalles_json']
    ]);
    
    $ventaId = $pdo->lastInsertId();
    $pasos[] = "5. Venta de prueba insertada OK (ID: {$ventaId})";
    
    // Eliminar venta de prueba
    $pdo->exec("DELETE FROM ventas WHERE id = {$ventaId}");
    $pasos[] = "6. Venta de prueba eliminada OK";
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Todos los tests pasaron correctamente',
        'pasos_completados' => $pasos,
        'diagnostico' => 'El error 500 probablemente ocurre DESPUÉS de guardar la venta, en la generación del ticket o respuesta'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'pasos_completados' => $pasos,
        'fallo_en' => 'Paso ' . (count($pasos) + 1)
    ], JSON_PRETTY_PRINT);
}
?>

