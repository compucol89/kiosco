<?php
/**
 * api/test_escenario_especifico.php
 * Escenario de prueba específico con depósito grande
 * RELEVANT FILES: bd_conexion.php
 */

header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== ESCENARIO DE PRUEBA ESPECÍFICO ===\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $usuario_id = 1;
    
    // PASO 1: APERTURA $10,000
    echo "1️⃣ APERTURA DE CAJA: \$10,000\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("INSERT INTO turnos_caja (usuario_id, fecha_apertura, monto_apertura, estado) VALUES (?, NOW(), 10000, 'abierto')");
    $stmt->execute([$usuario_id]);
    $turno_id = $pdo->lastInsertId();
    
    echo "   ✅ Turno #$turno_id abierto con \$10,000\n\n";
    
    // PASO 2: BUSCAR PRODUCTO ~$1000
    $stmt = $pdo->query("SELECT id, nombre, precio_venta, precio_costo FROM productos WHERE precio_venta BETWEEN 900 AND 1100 AND stock > 10 AND activo = 1 LIMIT 1");
    $producto = $stmt->fetch();
    
    if (!$producto) {
        $stmt = $pdo->query("SELECT id, nombre, precio_venta, precio_costo FROM productos WHERE stock > 10 AND activo = 1 ORDER BY precio_venta DESC LIMIT 1");
        $producto = $stmt->fetch();
    }
    
    $precio = floatval($producto['precio_venta']);
    $costo = floatval($producto['precio_costo']);
    
    echo "📦 Producto seleccionado: {$producto['nombre']}\n";
    echo "   Precio: \$$precio | Costo: \$$costo\n\n";
    
    // PASO 3: 4 VENTAS (UNA POR MÉTODO)
    echo "2️⃣ CREANDO 4 VENTAS (~\$1,000 cada una)\n";
    echo str_repeat("-", 70) . "\n";
    
    $metodos = ['efectivo', 'tarjeta', 'transferencia', 'qr'];
    $ventas_ids = [];
    
    foreach ($metodos as $metodo) {
        $descuento = ($metodo === 'efectivo') ? ($precio * 0.10) : 0;
        $monto_total = $precio - $descuento;
        
        $detalles = json_encode([
            'cart' => [['id' => $producto['id'], 'nombre' => $producto['nombre'], 'precio' => $precio, 'cantidad' => 1]]
        ]);
        
        $stmt = $pdo->prepare("INSERT INTO ventas (cliente_nombre, metodo_pago, subtotal, descuento, monto_total, estado, detalles_json, usuario_id) VALUES (?, ?, ?, ?, ?, 'completado', ?, ?)");
        $stmt->execute(["Cliente " . ucfirst($metodo), $metodo, $precio, $descuento, $monto_total, $detalles, $usuario_id]);
        $venta_id = $pdo->lastInsertId();
        $ventas_ids[] = $venta_id;
        
        // Reducir stock
        $pdo->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ?")->execute([$producto['id']]);
        
        // Registrar movimiento si es efectivo
        if ($metodo === 'efectivo') {
            $stmt = $pdo->prepare("INSERT INTO movimientos_caja_detallados (turno_id, tipo, monto, descripcion, categoria, usuario_id) VALUES (?, 'ingreso', ?, ?, 'ventas', ?)");
            $stmt->execute([$turno_id, $monto_total, "Venta #$venta_id - Efectivo", $usuario_id]);
        }
        
        echo "   ✅ Venta #$venta_id - " . strtoupper($metodo) . ": \$$precio - \$$descuento desc = \$$monto_total\n";
    }
    echo "\n";
    
    // PASO 4: ENTRADA $100,000 (DEPÓSITO)
    echo "3️⃣ ENTRADA DE EFECTIVO: \$100,000\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("INSERT INTO movimientos_caja_detallados (turno_id, tipo, monto, descripcion, categoria, referencia, usuario_id) VALUES (?, 'ingreso', 100000, 'Depósito bancario', 'deposito', 'DEP-001', ?)");
    $stmt->execute([$turno_id, $usuario_id]);
    
    echo "   ✅ Depósito registrado: \$100,000\n";
    echo "   📝 Categoría: Depósito\n";
    echo "   🔖 Referencia: DEP-001\n\n";
    
    // PASO 5: SALIDA $90,000
    echo "4️⃣ SALIDA DE EFECTIVO: \$90,000\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("INSERT INTO movimientos_caja_detallados (turno_id, tipo, monto, descripcion, categoria, referencia, usuario_id) VALUES (?, 'egreso', 90000, 'Pago a proveedor', 'proveedores', 'PAG-001', ?)");
    $stmt->execute([$turno_id, $usuario_id]);
    
    echo "   ✅ Egreso registrado: \$90,000\n";
    echo "   📝 Categoría: Proveedores\n";
    echo "   🔖 Referencia: PAG-001\n\n";
    
    // PASO 6: ACTUALIZAR TURNO
    echo "5️⃣ ACTUALIZANDO TOTALES DEL TURNO\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->prepare("SELECT SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as entradas, SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as salidas FROM movimientos_caja_detallados WHERE turno_id = ?");
    $stmt->execute([$turno_id]);
    $movs = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE turnos_caja SET ventas_efectivo = 810, ventas_tarjeta = 900, ventas_transferencia = 900, ventas_qr = 900, cantidad_ventas = 4, total_entradas = ?, total_salidas = ? WHERE id = ?");
    $stmt->execute([$movs['entradas'], $movs['salidas'], $turno_id]);
    
    $efectivo_disponible = 10000 + $movs['entradas'] - $movs['salidas'];
    
    echo "   ✅ Totales actualizados\n";
    echo "   🏁 Apertura: \$10,000\n";
    echo "   ➕ Entradas: \${$movs['entradas']}\n";
    echo "   ➖ Salidas: \${$movs['salidas']}\n";
    echo "   ━━━━━━━━━━━━━━━━━━\n";
    echo "   🎯 Efectivo Disponible: \$$efectivo_disponible\n\n";
    
    // RESUMEN
    echo str_repeat("=", 70) . "\n";
    echo "🎉 ESCENARIO DE PRUEBA COMPLETADO\n";
    echo str_repeat("=", 70) . "\n\n";
    
    echo "📊 RESUMEN FINANCIERO:\n";
    echo "   • Apertura: \$10,000\n";
    echo "   • 4 ventas totales: \$3,510\n";
    echo "   • Depósito: +\$100,000\n";
    echo "   • Pago proveedor: -\$90,000\n";
    echo "   • Efectivo Final: \$$efectivo_disponible\n\n";
    
    echo "🎯 AHORA VERIFICA EN EL SISTEMA:\n";
    echo "   1. Dashboard → Efectivo: \$$efectivo_disponible\n";
    echo "   2. Análisis → 4 ventas + ganancias\n";
    echo "   3. Control Caja → 5 movimientos (1 venta efec + 1 dep + 1 pago)\n\n";
    
    echo json_encode(['success' => true, 'efectivo_final' => $efectivo_disponible], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>


