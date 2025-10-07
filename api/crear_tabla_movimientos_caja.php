<?php
require_once 'conexion_simple.php';

try {
    // Crear tabla movimientos_caja si no existe
    $sql = "CREATE TABLE IF NOT EXISTS movimientos_caja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('ingreso', 'salida') NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        descripcion TEXT NOT NULL,
        referencia VARCHAR(255) DEFAULT NULL,
        usuario VARCHAR(100) NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo),
        INDEX idx_fecha (fecha_creacion),
        INDEX idx_usuario (usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Tabla 'movimientos_caja' creada o ya existe.\n";
        
        // Verificar estructura
        $result = $conn->query("DESCRIBE movimientos_caja");
        echo "\n📋 Estructura de la tabla:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
        
    } else {
        echo "❌ Error creando tabla: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
