<?php
require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    
    echo "=== SIMULANDO STOCK BAJO ===\n";
    
    // Productos para simular stock bajo
    $productos_bajo = [
        ['id' => 1087, 'stock' => 5],   // 9 De Oro Bizcochos
        ['id' => 1107, 'stock' => 2],   // 9 De Oro Brigitte
        ['id' => 1248, 'stock' => 8],   // Aceite De Girasol
        ['id' => 1258, 'stock' => 3],   // Agua Cimes
        ['id' => 1363, 'stock' => 1]    // Agua Glaciar
    ];
    
    foreach ($productos_bajo as $item) {
        $stmt = $pdo->prepare('UPDATE productos SET stock = ?, stock_actual = ? WHERE id = ?');
        $stmt->execute([$item['stock'], $item['stock'], $item['id']]);
        echo "✓ Producto ID {$item['id']} actualizado a stock {$item['stock']}\n";
    }
    
    echo "\n✅ Stock bajo simulado exitosamente!\n";
    echo "Ahora el dashboard mostrará productos con stock bajo.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 