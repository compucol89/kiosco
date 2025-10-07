<?php
/**
 * api/test_flujo_completo_automatico.php
 * Script de prueba automática del flujo completo del sistema
 * Simula apertura, ventas, movimientos y verificaciones
 * RELEVANT FILES: bd_conexion.php, procesar_venta.php, gestion_caja_completa.php
 */

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'bd_conexion.php';

echo "=== FLUJO COMPLETO DE PRUEBA AUTOMÁTICO ===\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== PASO 1: ABRIR CAJA =====
    echo "1️⃣ APERTURA DE CAJA\n";
    echo str_repeat("-", 60) . "\n";
    
    $monto_apertura = 10000;
    $usuario_id = 1; // Harold Zuluaga
    
    $stmt = $pdo->prepare("
        INSERT INTO turnos_caja 
        (usuario_id, fecha_apertura, monto_apertura, estado) 
        VALUES (?, NOW(), ?, 'abierto')
    ");
    $stmt->execute([$usuario_id, $monto_apertura]);
    $turno_id = $pdo->lastInsertId();
    
    echo "   ✅ Turno #$turno_id abierto\n";
    echo "   💰 Monto apertura: $$monto_apertura\n";
    echo "   👤 Usuario: Harold Zuluaga (ID: $usuario_id)\n\n";
    
    // ===== PASO 2: BUSCAR UN PRODUCTO PARA VENDER =====
    echo "2️⃣ PREPARANDO PRODUCTO PARA VENTAS\n";
    echo str_repeat("-", 60) . "\n";
    
    // Buscar un producto con stock y precio cercano a $1000
    $stmt = $pdo->query("
        SELECT id, nombre, precio_venta, precio_costo, stock 
        FROM productos 
        WHERE stock > 10 
        AND precio_venta BETWEEN 900 AND 1100
        AND activo = 1
        LIMIT 1
    ");
    $producto = $stmt->fetch();
    
    if (!$producto) {
        // Si no hay producto cercano a $1000, usar cualquiera
        $stmt = $pdo->query("
            SELECT id, nombre, precio_venta, precio_costo, stock 
            FROM productos 
            WHERE stock > 10 AND activo = 1
            ORDER BY precio_venta DESC
            LIMIT 1
        ");
        $producto = $stmt->fetch();
    }
    
    echo "   📦 Producto: {$producto['nombre']}\n";
    echo "   💵 Precio: \${$producto['precio_venta']}\n";
    echo "   💰 Costo: \${$producto['precio_costo']}\n";
    echo "   📊 Stock inicial: {$producto['stock']}\n\n";
    
    // ===== PASO 3: CREAR 4 VENTAS (UNA POR MÉTODO DE PAGO) =====
    echo "3️⃣ CREANDO 4 VENTAS (UNA POR CADA MÉTODO)\n";
    echo str_repeat("-", 60) . "\n";
    
    $metodos_pago = ['efectivo', 'tarjeta', 'transferencia', 'qr'];
    $ventas_creadas = [];
    
    foreach ($metodos_pago as $index => $metodo) {
        $precio_venta = floatval($producto['precio_venta']);
        $cantidad = 1;
        
        // Aplicar 10% descuento solo en efectivo
        $descuento = ($metodo === 'efectivo') ? ($precio_venta * 0.10) : 0;
        $subtotal = $precio_venta * $cantidad;
        $monto_total = $subtotal - $descuento;
        
        // Crear detalles JSON
        $detalles = [
            'cart' => [
                [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $precio_venta,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ]
            ],
            'totales' => [
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $monto_total
            ]
        ];
        
        // Insertar venta (sin especificar fecha, usará datetime default)
        $stmt = $pdo->prepare("
            INSERT INTO ventas 
            (cliente_nombre, metodo_pago, subtotal, descuento, monto_total, estado, detalles_json, usuario_id) 
            VALUES (?, ?, ?, ?, ?, 'completado', ?, ?)
        ");
        $stmt->execute([
            'Cliente Test ' . ($index + 1),
            $metodo,
            $subtotal,
            $descuento,
            $monto_total,
            json_encode($detalles),
            $usuario_id
        ]);
        
        $venta_id = $pdo->lastInsertId();
        $ventas_creadas[] = $venta_id;
        
        // Reducir stock
        $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$cantidad, $producto['id']]);
        
        // Si es efectivo, registrar movimiento en caja
        if ($metodo === 'efectivo') {
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_caja_detallados 
                (turno_id, tipo, monto, descripcion, categoria, usuario_id) 
                VALUES (?, 'ingreso', ?, ?, 'ventas', ?)
            ");
            $stmt->execute([
                $turno_id,
                $monto_total,
                "Venta #{$venta_id} - {$producto['nombre']}",
                $usuario_id
            ]);
        }
        
        echo "   ✅ Venta #$venta_id - " . strtoupper($metodo) . "\n";
        echo "      💵 Precio: \${$precio_venta} x $cantidad = \${$subtotal}\n";
        echo "      🎁 Descuento: \${$descuento}\n";
        echo "      💰 Total: \${$monto_total}\n\n";
    }
    
    // ===== PASO 4: MOVIMIENTO DE ENTRADA (INGRESO EXTRA) =====
    echo "4️⃣ MOVIMIENTO DE ENTRADA (Ingreso Extra)\n";
    echo str_repeat("-", 60) . "\n";
    
    $ingreso_extra = 500;
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_caja_detallados 
        (turno_id, tipo, monto, descripcion, categoria, referencia, usuario_id) 
        VALUES (?, 'ingreso', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $turno_id,
        $ingreso_extra,
        'Ingreso extra de prueba',
        'otros_ingresos',
        'TEST-001',
        $usuario_id
    ]);
    
    echo "   ✅ Ingreso registrado\n";
    echo "   💵 Monto: +\${$ingreso_extra}\n";
    echo "   📝 Descripción: Ingreso extra de prueba\n\n";
    
    // ===== PASO 5: MOVIMIENTO DE SALIDA (EGRESO) =====
    echo "5️⃣ MOVIMIENTO DE SALIDA (Egreso)\n";
    echo str_repeat("-", 60) . "\n";
    
    $egreso = 300;
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_caja_detallados 
        (turno_id, tipo, monto, descripcion, categoria, referencia, usuario_id) 
        VALUES (?, 'egreso', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $turno_id,
        $egreso,
        'Gasto de prueba - compra insumos',
        'gastos_operativos',
        'EGRESO-001',
        $usuario_id
    ]);
    
    echo "   ✅ Egreso registrado\n";
    echo "   💸 Monto: -\${$egreso}\n";
    echo "   📝 Descripción: Gasto de prueba\n\n";
    
    // ===== PASO 6: CALCULAR TOTALES DEL TURNO =====
    echo "6️⃣ ACTUALIZANDO TOTALES DEL TURNO\n";
    echo str_repeat("-", 60) . "\n";
    
    // Calcular ventas por método
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto_total ELSE 0 END) as ventas_efectivo,
            SUM(CASE WHEN metodo_pago = 'tarjeta' THEN monto_total ELSE 0 END) as ventas_tarjeta,
            SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto_total ELSE 0 END) as ventas_transferencia,
            SUM(CASE WHEN metodo_pago = 'qr' THEN monto_total ELSE 0 END) as ventas_qr,
            COUNT(*) as cantidad_ventas
        FROM ventas
        WHERE DATE(fecha) = CURDATE()
        AND estado = 'completado'
    ");
    $stmt->execute();
    $stats_ventas = $stmt->fetch();
    
    // Calcular movimientos de caja
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_entradas,
            SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as total_salidas
        FROM movimientos_caja_detallados
        WHERE turno_id = ?
    ");
    $stmt->execute([$turno_id]);
    $stats_movimientos = $stmt->fetch();
    
    // Actualizar turno
    $stmt = $pdo->prepare("
        UPDATE turnos_caja SET
            ventas_efectivo = ?,
            ventas_tarjeta = ?,
            ventas_transferencia = ?,
            ventas_qr = ?,
            cantidad_ventas = ?,
            total_entradas = ?,
            total_salidas = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $stats_ventas['ventas_efectivo'],
        $stats_ventas['ventas_tarjeta'],
        $stats_ventas['ventas_transferencia'],
        $stats_ventas['ventas_qr'],
        $stats_ventas['cantidad_ventas'],
        $stats_movimientos['total_entradas'],
        $stats_movimientos['total_salidas'],
        $turno_id
    ]);
    
    echo "   ✅ Turno actualizado con totales\n";
    echo "   💵 Ventas efectivo: \${$stats_ventas['ventas_efectivo']}\n";
    echo "   💳 Ventas tarjeta: \${$stats_ventas['ventas_tarjeta']}\n";
    echo "   🔄 Ventas transferencia: \${$stats_ventas['ventas_transferencia']}\n";
    echo "   📱 Ventas QR: \${$stats_ventas['ventas_qr']}\n";
    echo "   📊 Total ventas: {$stats_ventas['cantidad_ventas']}\n";
    echo "   💰 Total entradas: \${$stats_movimientos['total_entradas']}\n";
    echo "   💸 Total salidas: \${$stats_movimientos['total_salidas']}\n\n";
    
    // ===== PASO 7: CÁLCULO DE EFECTIVO DISPONIBLE =====
    echo "7️⃣ CÁLCULO DE EFECTIVO DISPONIBLE\n";
    echo str_repeat("-", 60) . "\n";
    
    $efectivo_disponible = $monto_apertura + $stats_movimientos['total_entradas'] - $stats_movimientos['total_salidas'];
    
    echo "   🏁 Apertura: \${$monto_apertura}\n";
    echo "   ➕ Entradas: \${$stats_movimientos['total_entradas']}\n";
    echo "   ➖ Salidas: \${$stats_movimientos['total_salidas']}\n";
    echo "   ━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   🎯 Efectivo Disponible: \${$efectivo_disponible}\n\n";
    
    // ===== PASO 8: VERIFICACIÓN FINAL =====
    echo "8️⃣ VERIFICACIÓN FINAL\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = CURDATE()");
    $total_ventas = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turnos_caja WHERE estado = 'abierto'");
    $turnos_abiertos = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM movimientos_caja_detallados WHERE turno_id = ?");
    $stmt->execute([$turno_id]);
    $total_movimientos = $stmt->fetch()['total'];
    
    echo "   ✅ Ventas creadas: $total_ventas\n";
    echo "   ✅ Turnos abiertos: $turnos_abiertos\n";
    echo "   ✅ Movimientos registrados: $total_movimientos\n\n";
    
    // ===== RESUMEN FINAL =====
    echo "=" . str_repeat("=", 59) . "\n";
    echo "🎉 PRUEBA COMPLETADA EXITOSAMENTE\n";
    echo "=" . str_repeat("=", 59) . "\n\n";
    
    echo "📊 RESUMEN:\n";
    echo "   • Turno #$turno_id abierto con \$10,000\n";
    echo "   • 4 ventas realizadas (una por cada método de pago)\n";
    echo "   • 1 ingreso extra de \$500\n";
    echo "   • 1 egreso de \$300\n";
    echo "   • Efectivo disponible calculado: \${$efectivo_disponible}\n\n";
    
    echo "🎯 PRÓXIMOS PASOS:\n";
    echo "   1. Ve al Dashboard: http://localhost:3000\n";
    echo "   2. Verifica que muestre:\n";
    echo "      - Caja Abierta ✅\n";
    echo "      - Efectivo Disponible: \${$efectivo_disponible}\n";
    echo "      - 4 ventas del día\n";
    echo "   3. Ve a Análisis y verifica los datos\n";
    echo "   4. Ve a Control de Caja y verifica los movimientos\n\n";
    
    echo json_encode([
        'success' => true,
        'turno_id' => $turno_id,
        'ventas_ids' => $ventas_creadas,
        'efectivo_disponible' => $efectivo_disponible,
        'totales' => [
            'apertura' => $monto_apertura,
            'entradas' => $stats_movimientos['total_entradas'],
            'salidas' => $stats_movimientos['total_salidas'],
            'disponible' => $efectivo_disponible
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
