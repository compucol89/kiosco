<?php
// Configuración inicial
$host = 'localhost';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    // Conectar al servidor MySQL sin especificar una base de datos
    $pdo = new PDO("mysql:host=$host;charset=$charset", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Crear la base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS kiosco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos creada o ya existente.<br>";
    
    // Seleccionar la base de datos
    $pdo->exec("USE kiosco_db");
    
    // Crear la tabla productos si no existe
    $query = "CREATE TABLE IF NOT EXISTS productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        precio_costo DECIMAL(10,2) NOT NULL DEFAULT 0,
        porcentaje_ganancia DECIMAL(10,2) NOT NULL DEFAULT 40,
        precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        categoria VARCHAR(100) DEFAULT 'General',
        barcode VARCHAR(100) DEFAULT '',
        proveedor VARCHAR(100) DEFAULT '',
        impuesto VARCHAR(50) DEFAULT 'IVA 21%',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (codigo)
    )";
    
    $pdo->exec($query);
    echo "Tabla 'productos' creada o ya existente.<br>";
    
    echo "Inicialización de la base de datos completada exitosamente!";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 