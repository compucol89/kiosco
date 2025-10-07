<?php
// Configuración inicial
require_once 'config.php';

try {
    // Crear la tabla ventas si no existe
    $query = "CREATE TABLE IF NOT EXISTS ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        cliente_nombre VARCHAR(255) NOT NULL DEFAULT 'Consumidor Final',
        cliente_id INT NULL,
        cliente_cuit VARCHAR(20) NULL,
        tipo_comprobante VARCHAR(50) NOT NULL DEFAULT 'Ticket',
        numero_comprobante VARCHAR(50) NULL,
        monto_total DECIMAL(10,2) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL DEFAULT 'efectivo',
        descuento DECIMAL(10,2) DEFAULT 0,
        impuestos DECIMAL(10,2) DEFAULT 0,
        estado VARCHAR(20) NOT NULL DEFAULT 'completado'
    )";
    
    $pdo->exec($query);
    echo "Tabla 'ventas' creada o ya existente.<br>";
    
    // Crear la tabla detalle_ventas si no existe
    $query = "CREATE TABLE IF NOT EXISTS detalle_ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venta_id INT NOT NULL,
        producto_id INT NOT NULL,
        producto_codigo VARCHAR(50) NOT NULL,
        producto_nombre VARCHAR(255) NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
    )";
    
    $pdo->exec($query);
    echo "Tabla 'detalle_ventas' creada o ya existente.<br>";
    
    echo "Inicialización de tablas de ventas completada exitosamente!";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 