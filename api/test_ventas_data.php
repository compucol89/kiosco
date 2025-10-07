<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== DIAGNÓSTICO DE DATOS DE VENTAS ===\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    
    // Test 1: ¿Existe la tabla ventas?
    echo "1. Verificando tabla ventas...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'ventas'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ Tabla 'ventas' existe\n\n";
    } else {
        echo "   ❌ Tabla 'ventas' NO existe\n";
        echo "   💡 Necesitas crear la tabla ventas\n\n";
        exit;
    }
    
    // Test 2: Ver estructura de la tabla
    echo "2. Estructura de tabla ventas:\n";
    $stmt = $pdo->query("DESCRIBE ventas");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Test 3: Contar ventas totales
    echo "3. Total de ventas en la BD:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
    $result = $stmt->fetch();
    echo "   📊 Total: {$result['total']} ventas\n\n";
    
    if ($result['total'] == 0) {
        echo "   ⚠️ No hay ventas en la base de datos\n";
        echo "   💡 Necesitas registrar algunas ventas primero\n\n";
        exit;
    }
    
    // Test 4: Ventas de hoy
    echo "4. Ventas de HOY:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = CURDATE()");
    $result = $stmt->fetch();
    echo "   📊 Ventas hoy: {$result['total']}\n\n";
    
    // Test 5: Últimas 5 ventas
    echo "5. Últimas 5 ventas registradas:\n";
    $stmt = $pdo->query("SELECT id, fecha, monto_total, metodo_pago, estado FROM ventas ORDER BY fecha DESC LIMIT 5");
    $ventas = $stmt->fetchAll();
    
    if (count($ventas) > 0) {
        foreach ($ventas as $v) {
            echo "   - ID: {$v['id']}, Fecha: {$v['fecha']}, Total: \${$v['monto_total']}, Método: {$v['metodo_pago']}, Estado: {$v['estado']}\n";
        }
    } else {
        echo "   ⚠️ No hay ventas registradas\n";
    }
    echo "\n";
    
    // Test 6: Rango de fechas de ventas
    echo "6. Rango de fechas de ventas:\n";
    $stmt = $pdo->query("SELECT MIN(fecha) as primera, MAX(fecha) as ultima FROM ventas");
    $result = $stmt->fetch();
    if ($result['primera']) {
        echo "   Primera venta: {$result['primera']}\n";
        echo "   Última venta: {$result['ultima']}\n";
    }
    echo "\n";
    
    // Test 7: Verificar turno activo
    echo "7. Turnos de caja:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turnos_caja");
    $result = $stmt->fetch();
    echo "   📊 Total turnos: {$result['total']}\n";
    
    $stmt = $pdo->query("SELECT * FROM turnos_caja WHERE estado = 'abierto' ORDER BY id DESC LIMIT 1");
    $turno = $stmt->fetch();
    if ($turno) {
        echo "   ✅ Turno abierto ID: {$turno['id']}\n";
        echo "   Fecha apertura: {$turno['fecha_apertura']}\n";
        echo "   Monto apertura: \${$turno['monto_apertura']}\n";
    } else {
        echo "   ⚠️ No hay turno abierto\n";
    }
    
    echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>






