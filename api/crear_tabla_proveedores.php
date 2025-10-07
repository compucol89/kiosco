<?php
/*
 * Script para crear tabla de proveedores
 * Necesaria para el inventario inteligente
 */

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    
    // Crear tabla de proveedores si no existe
    $sql = "CREATE TABLE IF NOT EXISTS proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL UNIQUE,
        contacto VARCHAR(255),
        telefono VARCHAR(50),
        email VARCHAR(255),
        direccion TEXT,
        cuit VARCHAR(20),
        tiempo_entrega INT DEFAULT 7 COMMENT 'Días de entrega',
        pedido_minimo DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto mínimo de pedido',
        descuento_volumen DECIMAL(5,2) DEFAULT 0 COMMENT 'Descuento por volumen %',
        forma_pago VARCHAR(100) DEFAULT 'Contado',
        calificacion DECIMAL(3,1) DEFAULT 5.0 COMMENT 'Calificación 1-5',
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        notas TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Tabla 'proveedores' creada exitosamente.\n";
    
    // Verificar si ya hay proveedores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM proveedores");
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        // Insertar proveedores de ejemplo
        $proveedoresEjemplo = [
            [
                'nombre' => 'Distribuidora Central',
                'contacto' => 'Juan Pérez',
                'telefono' => '11-4567-8901',
                'email' => 'ventas@distribuidoracentral.com',
                'direccion' => 'Av. Corrientes 1234, CABA',
                'cuit' => '20-12345678-9',
                'tiempo_entrega' => 3,
                'pedido_minimo' => 5000.00,
                'descuento_volumen' => 5.0,
                'forma_pago' => 'Cuenta corriente 30 días',
                'calificacion' => 4.5
            ],
            [
                'nombre' => 'Mayorista del Norte',
                'contacto' => 'María González',
                'telefono' => '11-2345-6789',
                'email' => 'pedidos@mayoristandelnorte.com',
                'direccion' => 'Ruta 9 Km 45, Pilar',
                'cuit' => '30-98765432-1',
                'tiempo_entrega' => 5,
                'pedido_minimo' => 3000.00,
                'descuento_volumen' => 8.0,
                'forma_pago' => 'Contado',
                'calificacion' => 4.2
            ],
            [
                'nombre' => 'Proveedor Express',
                'contacto' => 'Carlos Rodriguez',
                'telefono' => '11-9876-5432',
                'email' => 'info@proveedorexpress.com',
                'direccion' => 'San Martín 567, San Isidro',
                'cuit' => '27-55544433-2',
                'tiempo_entrega' => 1,
                'pedido_minimo' => 1000.00,
                'descuento_volumen' => 3.0,
                'forma_pago' => 'Contado/Tarjeta',
                'calificacion' => 4.8,
                'notas' => 'Entrega rápida, especializado en productos de alta rotación'
            ],
            [
                'nombre' => 'Importaciones del Sur',
                'contacto' => 'Ana Martínez',
                'telefono' => '11-1122-3344',
                'email' => 'compras@importacionesdelsur.com',
                'direccion' => 'Av. Rivadavia 9876, Morón',
                'cuit' => '33-44455566-7',
                'tiempo_entrega' => 14,
                'pedido_minimo' => 10000.00,
                'descuento_volumen' => 12.0,
                'forma_pago' => 'Cuenta corriente 60 días',
                'calificacion' => 4.0,
                'notas' => 'Productos importados, requiere planificación anticipada'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO proveedores (
                nombre, contacto, telefono, email, direccion, cuit, 
                tiempo_entrega, pedido_minimo, descuento_volumen, 
                forma_pago, calificacion, notas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($proveedoresEjemplo as $proveedor) {
            $stmt->execute([
                $proveedor['nombre'],
                $proveedor['contacto'],
                $proveedor['telefono'],
                $proveedor['email'],
                $proveedor['direccion'],
                $proveedor['cuit'],
                $proveedor['tiempo_entrega'],
                $proveedor['pedido_minimo'],
                $proveedor['descuento_volumen'],
                $proveedor['forma_pago'],
                $proveedor['calificacion'],
                $proveedor['notas'] ?? null
            ]);
        }
        
        echo "✅ Insertados " . count($proveedoresEjemplo) . " proveedores de ejemplo.\n";
    } else {
        echo "✅ Ya existen $count proveedores en la base de datos.\n";
    }
    
    // Actualizar algunos productos con proveedores
    echo "🔄 Actualizando productos con proveedores...\n";
    
    $stmt = $pdo->prepare("UPDATE productos SET proveedor = ? WHERE proveedor = '' OR proveedor IS NULL LIMIT 10");
    $stmt->execute(['Distribuidora Central']);
    
    $stmt = $pdo->prepare("UPDATE productos SET proveedor = ? WHERE id % 3 = 0 AND proveedor = 'Distribuidora Central' LIMIT 5");
    $stmt->execute(['Mayorista del Norte']);
    
    $stmt = $pdo->prepare("UPDATE productos SET proveedor = ? WHERE id % 4 = 0 AND proveedor IN ('Distribuidora Central', 'Mayorista del Norte') LIMIT 3");
    $stmt->execute(['Proveedor Express']);
    
    echo "✅ Productos actualizados con proveedores.\n";
    
    // Crear índices para optimizar consultas
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_proveedor_estado ON proveedores(estado)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_proveedor_tiempo_entrega ON proveedores(tiempo_entrega)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_productos_proveedor ON productos(proveedor)");
        echo "✅ Índices creados para optimización.\n";
    } catch (Exception $e) {
        echo "⚠️  Algunos índices ya existían.\n";
    }
    
    echo "\n🎉 Configuración de proveedores completada exitosamente!\n";
    echo "📊 El inventario inteligente ahora puede utilizar datos de proveedores.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 