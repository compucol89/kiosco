<?php
/**
 * scripts/simulacion_simple.php
 * Simulación simple de ventas para testing
 * Propósito: Generar datos de prueba básicos
 */

echo "🏪 SIMULACIÓN SIMPLE DE VENTAS\n";
echo "==============================\n\n";

// Conexión a base de datos
try {
    $pdo = new PDO("mysql:host=localhost;dbname=kiosco;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión establecida\n";
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

// Productos de ejemplo
$productos = [
    ['id' => 1, 'nombre' => 'Coca Cola 600ml', 'precio' => 1800],
    ['id' => 2, 'nombre' => 'Alfajor Havanna', 'precio' => 3500],
    ['id' => 3, 'nombre' => 'Cerveza Quilmes', 'precio' => 2500],
    ['id' => 4, 'nombre' => 'Papas Lays', 'precio' => 2800],
    ['id' => 5, 'nombre' => 'Marlboro Box', 'precio' => 8500]
];

$metodos = ['efectivo', 'tarjeta', 'transferencia', 'qr'];

echo "🚀 Generando 100 ventas de prueba...\n";

$totalVentas = 0;
$totalFacturado = 0;

for ($i = 1; $i <= 100; $i++) {
    // Seleccionar producto aleatorio
    $producto = $productos[array_rand($productos)];
    $cantidad = rand(1, 3);
    $metodo = $metodos[array_rand($metodos)];
    $subtotal = $producto['precio'] * $cantidad;
    
    try {
        // Insertar venta
        $stmt = $pdo->prepare("
            INSERT INTO ventas (total, metodo_pago, observaciones, fecha, estado) 
            VALUES (?, ?, ?, NOW(), 'completada')
        ");
        $stmt->execute([$subtotal, $metodo, "Venta simulada #$i"]);
        
        $ventaId = $pdo->lastInsertId();
        
        // Insertar detalle
        $stmt = $pdo->prepare("
            INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ventaId, $producto['id'], $cantidad, $producto['precio'], $subtotal]);
        
        $totalVentas++;
        $totalFacturado += $subtotal;
        
        if ($i % 20 == 0) {
            echo "  📊 Venta $i procesada - Total acumulado: $" . number_format($totalFacturado, 0, ',', '.') . "\n";
        }
        
    } catch (Exception $e) {
        echo "⚠️ Error en venta $i: " . $e->getMessage() . "\n";
    }
}

echo "\n📋 RESUMEN FINAL:\n";
echo "• Total ventas: $totalVentas\n";
echo "• Total facturado: $" . number_format($totalFacturado, 0, ',', '.') . "\n";
echo "• Ticket promedio: $" . number_format($totalFacturado / $totalVentas, 0, ',', '.') . "\n";

echo "\n✅ Simulación completada\n";
echo "🔍 Verifica los resultados en el sistema\n";
?>














