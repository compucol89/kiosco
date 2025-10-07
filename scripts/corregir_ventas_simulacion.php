<?php
/**
 * scripts/corregir_ventas_simulacion.php
 * Corregir ventas de simulación agregando productos para que se reflejen en reportes
 * Propósito: Vincular ventas simuladas con productos para cálculos correctos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 CORRIGIENDO VENTAS DE SIMULACIÓN\n";
echo "=================================\n\n";

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=localhost;dbname=kiosco_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión establecida\n\n";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// Obtener un producto genérico para usar en las ventas simuladas
try {
    $stmt = $pdo->query("SELECT id, nombre, precio_venta, precio_costo FROM productos WHERE stock > 0 LIMIT 1");
    $producto = $stmt->fetch();
    
    if (!$producto) {
        die("❌ No se encontró ningún producto disponible\n");
    }
    
    echo "📦 Producto para simulación:\n";
    echo "   ID: {$producto['id']}\n";
    echo "   Nombre: {$producto['nombre']}\n";
    echo "   Precio venta: ${$producto['precio_venta']}\n";
    echo "   Precio costo: ${$producto['precio_costo']}\n\n";
    
} catch (Exception $e) {
    die("❌ Error obteniendo producto: " . $e->getMessage() . "\n");
}

// Verificar tabla de detalles de ventas
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'detalles_ventas'");
    $tablaDetalles = $stmt->fetch();
    
    if (!$tablaDetalles) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'detalle_ventas'");
        $tablaDetalles = $stmt->fetch();
        
        if (!$tablaDetalles) {
            echo "⚠️ No se encontró tabla de detalles, creando estructura mínima...\n";
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS detalle_ventas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    venta_id INT NOT NULL,
                    producto_id INT NOT NULL,
                    cantidad INT DEFAULT 1,
                    precio_unitario DECIMAL(10,2) NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    descuento DECIMAL(10,2) DEFAULT 0,
                    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE
                )
            ");
            echo "✅ Tabla detalle_ventas creada\n\n";
            $nombreTablaDetalles = 'detalle_ventas';
        } else {
            $nombreTablaDetalles = 'detalle_ventas';
        }
    } else {
        $nombreTablaDetalles = 'detalles_ventas';
    }
    
    echo "📋 Usando tabla: $nombreTablaDetalles\n\n";
    
} catch (Exception $e) {
    echo "⚠️ Error verificando tabla detalles: " . $e->getMessage() . "\n";
    $nombreTablaDetalles = 'detalle_ventas'; // Asumir nombre estándar
}

// Buscar ventas simuladas sin detalles
try {
    $stmt = $pdo->query("
        SELECT v.id, v.monto_total, v.subtotal, v.metodo_pago, v.fecha, v.cliente_nombre
        FROM ventas v
        LEFT JOIN $nombreTablaDetalles d ON v.id = d.venta_id
        WHERE v.cliente_nombre = 'Cliente Simulación' 
        AND d.venta_id IS NULL
        ORDER BY v.id
        LIMIT 500
    ");
    
    $ventasSinDetalles = $stmt->fetchAll();
    
    echo "🔍 Ventas simuladas sin detalles encontradas: " . count($ventasSinDetalles) . "\n\n";
    
    if (count($ventasSinDetalles) == 0) {
        echo "✅ Todas las ventas simuladas ya tienen detalles asociados\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "⚠️ Error buscando ventas: " . $e->getMessage() . "\n";
    // Intentar buscar de otra manera
    try {
        $stmt = $pdo->query("
            SELECT id, monto_total, subtotal, metodo_pago, fecha, cliente_nombre
            FROM ventas
            WHERE cliente_nombre = 'Cliente Simulación'
            ORDER BY id
            LIMIT 500
        ");
        $ventasSinDetalles = $stmt->fetchAll();
        echo "🔍 Ventas simuladas encontradas (método alternativo): " . count($ventasSinDetalles) . "\n\n";
    } catch (Exception $e2) {
        die("❌ Error crítico buscando ventas: " . $e2->getMessage() . "\n");
    }
}

// Procesar ventas
$ventasProcesadas = 0;
$errores = 0;

echo "🔧 Procesando ventas simuladas...\n";

foreach ($ventasSinDetalles as $venta) {
    try {
        $montoVenta = floatval($venta['monto_total'] ?: $venta['subtotal']);
        
        if ($montoVenta <= 0) {
            echo "  ⚠️ Venta ID {$venta['id']} sin monto válido\n";
            $errores++;
            continue;
        }
        
        // Calcular cantidad basada en el precio del producto
        $precioUnitario = floatval($producto['precio_venta']);
        $cantidad = max(1, round($montoVenta / $precioUnitario));
        $subtotalCalculado = $cantidad * $precioUnitario;
        
        // Si hay diferencia, ajustar el precio unitario para que coincida exactamente
        if (abs($subtotalCalculado - $montoVenta) > 0.01) {
            $precioUnitario = $montoVenta; // Usar el monto total como precio unitario
            $cantidad = 1;
            $subtotalCalculado = $montoVenta;
        }
        
        // Insertar detalle de venta
        $stmt = $pdo->prepare("
            INSERT INTO $nombreTablaDetalles (
                venta_id, producto_id, cantidad, precio_unitario, subtotal
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $venta['id'],
            $producto['id'],
            $cantidad,
            $precioUnitario,
            $subtotalCalculado
        ]);
        
        $ventasProcesadas++;
        
        if ($ventasProcesadas <= 5 || $ventasProcesadas % 10 == 0) {
            echo sprintf("  ✅ Venta ID %d: $%s (%dx$%s)\n", 
                $venta['id'], 
                number_format($montoVenta, 0, ',', '.'),
                $cantidad,
                number_format($precioUnitario, 0, ',', '.')
            );
        }
        
    } catch (Exception $e) {
        $errores++;
        if ($errores <= 3) {
            echo "  ❌ Error en venta ID {$venta['id']}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n📊 RESULTADO:\n";
echo "============\n";
echo "✅ Ventas corregidas: $ventasProcesadas\n";
echo "❌ Errores: $errores\n\n";

// Verificar el resultado
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_ventas,
            SUM(monto_total) as total_facturacion,
            COUNT(d.venta_id) as ventas_con_detalles
        FROM ventas v
        LEFT JOIN $nombreTablaDetalles d ON v.id = d.venta_id
        WHERE DATE(v.fecha) = CURDATE()
    ");
    
    $resumen = $stmt->fetch();
    
    echo "📈 VERIFICACIÓN FINAL:\n";
    echo "====================\n";
    echo "• Total ventas del día: {$resumen['total_ventas']}\n";
    echo "• Facturación total: $" . number_format($resumen['total_facturacion'], 0, ',', '.') . "\n";
    echo "• Ventas con detalles: {$resumen['ventas_con_detalles']}\n";
    
    $cobertura = $resumen['total_ventas'] > 0 ? 
        round(($resumen['ventas_con_detalles'] / $resumen['total_ventas']) * 100, 1) : 0;
    
    echo "• Cobertura: {$cobertura}%\n\n";
    
    if ($cobertura >= 95) {
        echo "🎉 ¡EXCELENTE! Las ventas simuladas ahora deberían aparecer correctamente en los reportes.\n";
    } elseif ($cobertura >= 80) {
        echo "✅ Buena cobertura. La mayoría de ventas deberían aparecer en reportes.\n";
    } else {
        echo "⚠️ Cobertura baja. Es posible que aún falten detalles por agregar.\n";
    }
    
} catch (Exception $e) {
    echo "⚠️ Error en verificación final: " . $e->getMessage() . "\n";
}

echo "\n💡 PRÓXIMOS PASOS:\n";
echo "================\n";
echo "1. Refrescar el reporte de ventas en el navegador\n";
echo "2. Verificar que los 'Ingresos Netos' ahora muestren ~$5,100,000\n";
echo "3. Los análisis de productos también deberían funcionar correctamente\n";
echo "4. El dashboard debería mostrar las métricas actualizadas\n\n";

?>
