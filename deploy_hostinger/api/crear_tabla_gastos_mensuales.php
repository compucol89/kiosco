<?php
/**
 * 🏦 CREAR TABLA GASTOS MENSUALES - MÓDULO FINANCIERO COMPLETO
 * Almacena los gastos fijos mensuales para cálculos automáticos
 */

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🏦 CREANDO TABLA GASTOS MENSUALES...\n";
    
    // Crear tabla gastos_mensuales
    $sql = "CREATE TABLE IF NOT EXISTS gastos_mensuales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mes_ano VARCHAR(7) NOT NULL COMMENT 'Formato: 2025-08',
        gastos_totales DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Total gastos fijos del mes',
        descripcion TEXT NULL COMMENT 'Descripción opcional de los gastos',
        usuario_id INT NULL COMMENT 'Usuario que configuró los gastos',
        activo TINYINT(1) DEFAULT 1 COMMENT 'Si los gastos están activos',
        
        -- Auditoría
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Índices
        INDEX idx_mes_ano (mes_ano),
        INDEX idx_activo (activo),
        INDEX idx_usuario_id (usuario_id),
        
        -- Constraint único por mes
        UNIQUE KEY unique_mes_activo (mes_ano, activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Gastos fijos mensuales para cálculos financieros'";
    
    $pdo->exec($sql);
    echo "✅ Tabla gastos_mensuales creada exitosamente\n";
    
    // Insertar gastos por defecto para el mes actual si no existen
    $mesActual = date('Y-m');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gastos_mensuales WHERE mes_ano = ? AND activo = 1");
    $stmt->execute([$mesActual]);
    
    if ($stmt->fetchColumn() == 0) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO gastos_mensuales (mes_ano, gastos_totales, descripcion, usuario_id) 
            VALUES (?, 0, 'Gastos mensuales por defecto - Configurar en módulo de finanzas', 1)
        ");
        $stmtInsert->execute([$mesActual]);
        echo "✅ Gastos por defecto creados para $mesActual\n";
    } else {
        echo "ℹ️ Ya existen gastos configurados para $mesActual\n";
    }
    
    // Mostrar estructura de la tabla
    echo "\n📋 ESTRUCTURA DE LA TABLA:\n";
    $stmt = $pdo->query("DESCRIBE gastos_mensuales");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']}: {$row['Type']} - {$row['Comment']}\n";
    }
    
    // Mostrar gastos actuales
    echo "\n📊 GASTOS ACTUALES:\n";
    $stmt = $pdo->query("SELECT * FROM gastos_mensuales WHERE activo = 1 ORDER BY mes_ano DESC LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gastosDiarios = $row['gastos_totales'] / date('t', strtotime($row['mes_ano'] . '-01'));
        echo "• {$row['mes_ano']}: $" . number_format($row['gastos_totales'], 2) . 
             " (Diarios: $" . number_format($gastosDiarios, 2) . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
