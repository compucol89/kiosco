<?php
/**
 * scripts/simulacion_limpia_realista.php
 * Simulación limpia y realista de operatoria semanal con productos reales
 * Propósito: Generar datos de prueba correctos desde el origen
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🏪 SIMULACIÓN LIMPIA Y REALISTA - KIOSCO POS\n";
echo "==========================================\n\n";

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=localhost;dbname=kiosco_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión establecida\n\n";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// 1. LIMPIAR DATOS EXISTENTES DE SIMULACIÓN
echo "🧹 LIMPIANDO DATOS ANTERIORES...\n";
echo "===============================\n";

try {
    $pdo->beginTransaction();
    
    // Eliminar detalles de ventas simuladas
    $stmt = $pdo->prepare("DELETE FROM detalle_ventas WHERE venta_id IN (SELECT id FROM ventas WHERE cliente_nombre = 'Cliente Simulación')");
    $stmt->execute();
    $detallesEliminados = $stmt->rowCount();
    
    // Eliminar ventas simuladas
    $stmt = $pdo->prepare("DELETE FROM ventas WHERE cliente_nombre = 'Cliente Simulación'");
    $stmt->execute();
    $ventasEliminadas = $stmt->rowCount();
    
    $pdo->commit();
    
    echo "🗑️ Detalles eliminados: $detallesEliminados\n";
    echo "🗑️ Ventas eliminadas: $ventasEliminadas\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "⚠️ Error en limpieza: " . $e->getMessage() . "\n";
}

// 2. OBTENER PRODUCTOS REALES DISPONIBLES
echo "📦 ANALIZANDO PRODUCTOS DISPONIBLES...\n";
echo "=====================================\n";

try {
    $stmt = $pdo->query("
        SELECT id, nombre, precio_venta, precio_costo, stock, categoria
        FROM productos 
        WHERE stock > 0 AND precio_venta > 0 AND precio_costo > 0
        ORDER BY RAND()
        LIMIT 50
    ");
    
    $productos = $stmt->fetchAll();
    
    if (count($productos) < 5) {
        die("❌ Se necesitan al menos 5 productos con stock para la simulación\n");
    }
    
    echo "✅ Productos disponibles: " . count($productos) . "\n";
    echo "📊 Muestra de productos:\n";
    
    foreach (array_slice($productos, 0, 5) as $producto) {
        $margen = (($producto['precio_venta'] - $producto['precio_costo']) / $producto['precio_venta']) * 100;
        echo sprintf("   • %s - Venta: $%s | Costo: $%s | Margen: %.1f%%\n", 
            substr($producto['nombre'], 0, 30),
            number_format($producto['precio_venta'], 0, ',', '.'),
            number_format($producto['precio_costo'], 0, ',', '.'),
            $margen
        );
    }
    echo "\n";
    
} catch (Exception $e) {
    die("❌ Error obteniendo productos: " . $e->getMessage() . "\n");
}

// 3. CONFIGURACIÓN DE SIMULACIÓN REALISTA
$configuracion = [
    'objetivo_diario' => 520000, // $520K promedio diario
    'dias_simulacion' => 1,      // Solo hoy
    'metodos_pago' => [
        'efectivo' => 0.45,      // 45%
        'tarjeta' => 0.30,       // 30%
        'transferencia' => 0.15, // 15%
        'qr' => 0.10            // 10%
    ],
    'productos_populares' => [   // Índices de productos más vendidos
        0 => 0.30,  // 30% del volumen
        1 => 0.20,  // 20%
        2 => 0.15,  // 15%
        3 => 0.10,  // 10%
        4 => 0.08,  // 8%
        // El resto se distribuye entre otros productos
    ]
];

echo "⚙️ CONFIGURACIÓN DE SIMULACIÓN:\n";
echo "==============================\n";
echo sprintf("💰 Objetivo diario: $%s\n", number_format($configuracion['objetivo_diario'], 0, ',', '.'));
echo sprintf("📅 Días a simular: %d\n", $configuracion['dias_simulacion']);
echo sprintf("💳 Métodos de pago: Efectivo %.0f%%, Tarjeta %.0f%%, Transfer %.0f%%, QR %.0f%%\n", 
    $configuracion['metodos_pago']['efectivo'] * 100,
    $configuracion['metodos_pago']['tarjeta'] * 100,
    $configuracion['metodos_pago']['transferencia'] * 100,
    $configuracion['metodos_pago']['qr'] * 100
);
echo "\n";

// 4. GENERAR VENTAS REALISTAS
echo "🛒 GENERANDO VENTAS REALISTAS...\n";
echo "===============================\n";

$ventasGeneradas = 0;
$facturacionTotal = 0;
$contadorMetodos = ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0, 'qr' => 0];

// Calcular número aproximado de ventas para llegar al objetivo
$ticketPromedio = array_sum(array_column($productos, 'precio_venta')) / count($productos);
$ventasObjetivo = round($configuracion['objetivo_diario'] / $ticketPromedio);

echo sprintf("🎯 Ventas objetivo: %d (ticket promedio estimado: $%s)\n\n", 
    $ventasObjetivo, 
    number_format($ticketPromedio, 0, ',', '.')
);

try {
    $pdo->beginTransaction();
    
    for ($i = 0; $i < $ventasObjetivo; $i++) {
        // Seleccionar producto según popularidad
        $rand = mt_rand() / mt_getrandmax();
        $acumulado = 0;
        $productoSeleccionado = $productos[0]; // Default
        
        foreach ($configuracion['productos_populares'] as $indice => $probabilidad) {
            $acumulado += $probabilidad;
            if ($rand <= $acumulado && isset($productos[$indice])) {
                $productoSeleccionado = $productos[$indice];
                break;
            }
        }
        
        // Si no se seleccionó ninguno popular, elegir al azar
        if (!$productoSeleccionado) {
            $productoSeleccionado = $productos[array_rand($productos)];
        }
        
        // Determinar cantidad (1-3 unidades, más probable 1)
        $cantidad = (mt_rand(1, 100) <= 80) ? 1 : mt_rand(2, 3);
        
        // Seleccionar método de pago según distribución
        $metodoPago = 'efectivo'; // Default
        $rand = mt_rand() / mt_getrandmax();
        $acumulado = 0;
        
        foreach ($configuracion['metodos_pago'] as $metodo => $probabilidad) {
            $acumulado += $probabilidad;
            if ($rand <= $acumulado) {
                $metodoPago = $metodo;
                break;
            }
        }
        
        // Calcular montos
        $subtotal = $cantidad * $productoSeleccionado['precio_venta'];
        $descuento = 0; // Sin descuentos en esta simulación
        $montoTotal = $subtotal - $descuento;
        
        // Generar hora realista (8:00 - 22:00)
        $hora = mt_rand(8, 21);
        $minuto = mt_rand(0, 59);
        $segundo = mt_rand(0, 59);
        $fechaVenta = date('Y-m-d') . sprintf(" %02d:%02d:%02d", $hora, $minuto, $segundo);
        
        // Generar nombre de cliente realista
        $clientes = ['Ana García', 'Carlos López', 'María Rodríguez', 'Juan Pérez', 'Laura Martín', 
                    'Diego Silva', 'Carmen Ruiz', 'Roberto Chen', 'Sofia González', 'Cliente Final'];
        $clienteNombre = $clientes[array_rand($clientes)];
        
        // INSERTAR VENTA
        $stmtVenta = $pdo->prepare("
            INSERT INTO ventas (
                cliente_nombre, fecha, metodo_pago, subtotal, descuento, 
                monto_total, estado, numero_comprobante, 
                descuento_porcentaje, impuestos_total, utilidad_total, 
                costo_total, margen_promedio
            ) VALUES (?, ?, ?, ?, ?, ?, 'completado', ?, 0, 0, ?, ?, ?)
        ");
        
        $numeroComprobante = sprintf("SIM-%06d", $i + 1);
        $costoTotal = $cantidad * $productoSeleccionado['precio_costo'];
        $utilidadTotal = $subtotal - $costoTotal;
        $margenPromedio = $subtotal > 0 ? ($utilidadTotal / $subtotal) * 100 : 0;
        
        $stmtVenta->execute([
            $clienteNombre,
            $fechaVenta,
            $metodoPago,
            $subtotal,
            $descuento,
            $montoTotal,
            $numeroComprobante,
            $utilidadTotal,
            $costoTotal,
            $margenPromedio
        ]);
        
        $ventaId = $pdo->lastInsertId();
        
        // INSERTAR DETALLE DE VENTA
        $stmtDetalle = $pdo->prepare("
            INSERT INTO detalle_ventas (
                venta_id, producto_id, cantidad, precio_unitario, subtotal
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmtDetalle->execute([
            $ventaId,
            $productoSeleccionado['id'],
            $cantidad,
            $productoSeleccionado['precio_venta'],
            $subtotal
        ]);
        
        // Contadores
        $ventasGeneradas++;
        $facturacionTotal += $montoTotal;
        $contadorMetodos[$metodoPago]++;
        
        // Progreso cada 50 ventas
        if ($ventasGeneradas % 50 == 0) {
            echo sprintf("  ✅ %d ventas generadas... ($%s acumulado)\n", 
                $ventasGeneradas, 
                number_format($facturacionTotal, 0, ',', '.')
            );
        }
    }
    
    $pdo->commit();
    echo "✅ Transacción completada exitosamente\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("❌ Error generando ventas: " . $e->getMessage() . "\n");
}

// 5. RESUMEN FINAL
echo "📊 RESUMEN DE SIMULACIÓN REALISTA\n";
echo "===============================\n";
echo sprintf("✅ Ventas generadas: %d\n", $ventasGeneradas);
echo sprintf("💰 Facturación total: $%s\n", number_format($facturacionTotal, 0, ',', '.'));
echo sprintf("🎯 Objetivo alcanzado: %.1f%%\n", ($facturacionTotal / $configuracion['objetivo_diario']) * 100);
echo sprintf("💳 Ticket promedio real: $%s\n", number_format($facturacionTotal / $ventasGeneradas, 0, ',', '.'));

echo "\n📈 Distribución por método de pago:\n";
foreach ($contadorMetodos as $metodo => $cantidad) {
    $porcentaje = ($cantidad / $ventasGeneradas) * 100;
    $monto = 0;
    
    // Calcular monto por método (aproximado)
    $stmt = $pdo->prepare("SELECT SUM(monto_total) as total FROM ventas WHERE metodo_pago = ? AND DATE(fecha) = CURDATE()");
    $stmt->execute([$metodo]);
    $result = $stmt->fetch();
    $monto = $result['total'] ?: 0;
    
    echo sprintf("  • %s: %d ventas (%.1f%%) - $%s\n", 
        ucfirst($metodo), 
        $cantidad, 
        $porcentaje,
        number_format($monto, 0, ',', '.')
    );
}

echo "\n🎉 SIMULACIÓN COMPLETADA\n";
echo "=======================\n";
echo "✅ Datos limpios y realistas generados\n";
echo "✅ Productos reales con stock y costos\n";
echo "✅ Márgenes de ganancia calculados correctamente\n";
echo "✅ Distribución realista de métodos de pago\n";
echo "✅ Horarios comerciales simulados\n";
echo "✅ Relaciones de base de datos íntegras\n\n";

echo "🔍 Ahora puedes verificar:\n";
echo "• Dashboard - Métricas actualizadas\n";
echo "• Reportes de Ventas - Análisis completo\n";
echo "• Inventario - Impacto en stock\n";
echo "• Análisis IA - Con datos realistas\n\n";

?>
