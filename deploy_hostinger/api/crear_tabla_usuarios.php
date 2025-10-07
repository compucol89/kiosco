<?php
// Incluir la conexión a la base de datos
require_once 'bd_conexion.php';

try {
    // Crear tabla de usuarios si no existe
    $sql = "CREATE TABLE IF NOT EXISTS `usuarios` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(255) NOT NULL,
      `nombre` varchar(100) NOT NULL,
      `role` enum('admin','vendedor','cajero') NOT NULL DEFAULT 'vendedor',
      `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tabla usuarios creada o ya existente.<br>";
    
    // Verificar si ya hay usuarios
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $usuariosExistentes = $stmt->fetchColumn();
    
    if ($usuariosExistentes == 0) {
        // Insertar usuarios predeterminados
        $insertSql = "INSERT INTO `usuarios` (`username`, `password`, `nombre`, `role`) VALUES
            ('admin', :admin_pass, 'Administrador General', 'admin'),
            ('vendedor', :vendedor_pass, 'Juan Pérez', 'vendedor'),
            ('cajero', :cajero_pass, 'María García', 'cajero')";
        
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            'admin_pass' => password_hash('admin123', PASSWORD_DEFAULT),
            'vendedor_pass' => password_hash('venta123', PASSWORD_DEFAULT),
            'cajero_pass' => password_hash('caja123', PASSWORD_DEFAULT)
        ]);
        
        echo "Usuarios predeterminados insertados.<br>";
    } else {
        echo "Ya existen usuarios en la base de datos.<br>";
    }
    
    echo "Configuración completada exitosamente.";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 