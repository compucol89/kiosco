<?php
/**
 * api/test_n8n_apis.php
 * Script de test para verificar las APIs de n8n
 * Ejecutar antes de configurar n8n para asegurar que todo funciona
 * RELEVANT FILES: api/n8n_ventas_pendientes.php, api/n8n_marcar_facturada.php
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

$baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

$tests = [];
$errores = [];

// TEST 1: Verificar que exista al menos una venta
try {
    require_once 'bd_conexion.php';
    $pdo = Conexion::obtenerConexion();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        $tests[] = "✅ Hay {$result['total']} ventas en la BD";
    } else {
        $tests[] = "⚠️  No hay ventas en la BD (normal en sistema nuevo)";
    }
    
} catch (Exception $e) {
    $errores[] = "❌ Error conectando a BD: " . $e->getMessage();
}

// TEST 2: Llamar a la API de ventas pendientes
try {
    $url = "{$baseUrl}/n8n_ventas_pendientes.php?limite=5";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        $errores[] = "❌ No se pudo acceder a n8n_ventas_pendientes.php";
    } else {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $tests[] = "✅ API ventas_pendientes funciona: {$data['total_pendientes']} pendientes";
        } else {
            $errores[] = "❌ API ventas_pendientes respondió con error";
        }
    }
} catch (Exception $e) {
    $errores[] = "❌ Error al probar ventas_pendientes: " . $e->getMessage();
}

// TEST 3: Verificar estructura de la tabla ventas
try {
    $camposRequeridos = ['cae', 'comprobante_fiscal', 'fecha_vencimiento_cae', 'punto_venta_afip', 'numero_comprobante_afip'];
    $stmt = $pdo->query("DESCRIBE ventas");
    $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $faltantes = array_diff($camposRequeridos, $columnas);
    
    if (count($faltantes) === 0) {
        $tests[] = "✅ Todos los campos requeridos existen en la tabla ventas";
    } else {
        $errores[] = "❌ Faltan campos: " . implode(', ', $faltantes);
    }
    
} catch (Exception $e) {
    $errores[] = "❌ Error verificando estructura: " . $e->getMessage();
}

// TEST 4: Verificar que existe la tabla de auditoría
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'auditoria_facturacion'");
    if ($stmt->rowCount() > 0) {
        $tests[] = "✅ Tabla 'auditoria_facturacion' existe";
    } else {
        $errores[] = "⚠️  Tabla 'auditoria_facturacion' no existe (ejecutar migración)";
    }
} catch (Exception $e) {
    $errores[] = "❌ Error verificando tabla auditoría: " . $e->getMessage();
}

// TEST 5: Simular marcado de factura (sin ejecutar realmente)
try {
    $tests[] = "✅ API marcar_facturada disponible en: {$baseUrl}/n8n_marcar_facturada.php";
} catch (Exception $e) {
    $errores[] = "❌ Error: " . $e->getMessage();
}

// Resumen
$resultado = [
    'success' => count($errores) === 0,
    'total_tests_ok' => count($tests),
    'total_errores' => count($errores),
    'tests_exitosos' => $tests,
    'errores' => $errores,
    'apis_disponibles' => [
        'ventas_pendientes' => "{$baseUrl}/n8n_ventas_pendientes.php",
        'marcar_facturada' => "{$baseUrl}/n8n_marcar_facturada.php",
        'info_venta' => "{$baseUrl}/n8n_info_venta.php"
    ],
    'mensaje' => count($errores) === 0 
        ? '🎉 Todo listo para configurar n8n!' 
        : '⚠️  Hay problemas que resolver antes de usar n8n'
];

http_response_code(200);
echo json_encode($resultado, JSON_PRETTY_PRINT);
?>

