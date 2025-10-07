<?php
require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    
    $sql = "CREATE TABLE IF NOT EXISTS auditoria_inmutable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        evento_id VARCHAR(64) NOT NULL UNIQUE,
        categoria VARCHAR(50) NOT NULL,
        accion VARCHAR(50) NOT NULL,
        detalles JSON NOT NULL,
        usuario_id INT NULL,
        ip_origen VARCHAR(45) NULL,
        user_agent TEXT NULL,
        fecha_hora DATETIME(6) NOT NULL,
        timestamp_unix DECIMAL(16,6) NOT NULL,
        session_id VARCHAR(128) NULL,
        hash_integridad VARCHAR(64) NOT NULL,
        estado ENUM('ACTIVO', 'ANULADO') DEFAULT 'ACTIVO',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_categoria_accion (categoria, accion),
        INDEX idx_fecha_hora (fecha_hora),
        INDEX idx_usuario_id (usuario_id),
        INDEX idx_hash (hash_integridad),
        INDEX idx_evento_id (evento_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✅ Tabla auditoria_inmutable creada exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

