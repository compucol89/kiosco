<?php
/**
 * 💼 CREAR TABLA TURNOS DE CAJA - CONTROL COMPLETO DE EFECTIVO
 * Sistema de trazabilidad total del dinero en caja
 */

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "💼 CREANDO SISTEMA DE TURNOS DE CAJA...\n";
    
    // Crear tabla turnos_caja
    $sql = "CREATE TABLE IF NOT EXISTS turnos_caja (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL COMMENT 'Cajero responsable del turno',
        fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_cierre DATETIME NULL COMMENT 'Cuando se cierra el turno',
        monto_apertura DECIMAL(12,2) NOT NULL COMMENT 'Efectivo inicial del turno',
        monto_cierre DECIMAL(12,2) NULL COMMENT 'Efectivo final declarado',
        
        -- Cálculos automáticos del turno MEJORADOS
        total_apertura DECIMAL(12,2) GENERATED ALWAYS AS (monto_apertura + saldo_anterior) STORED COMMENT 'Apertura + saldo anterior',
        entradas_efectivo DECIMAL(12,2) DEFAULT 0 COMMENT 'TODAS las entradas en efectivo (ventas + movimientos)',
        salidas_efectivo DECIMAL(12,2) DEFAULT 0 COMMENT 'TODAS las salidas en efectivo',
        total_entradas DECIMAL(12,2) DEFAULT 0 COMMENT 'Suma de ingresos del turno',
        total_salidas DECIMAL(12,2) DEFAULT 0 COMMENT 'Suma de egresos del turno',
        efectivo_teorico DECIMAL(12,2) GENERATED ALWAYS AS (monto_apertura + entradas_efectivo - salidas_efectivo) STORED,
        saldo_anterior DECIMAL(12,2) DEFAULT 0 COMMENT 'Efectivo del turno anterior',
        diferencia DECIMAL(12,2) NULL COMMENT 'Diferencia entre teórico y real al cierre',
        
        -- Métodos de pago del turno
        ventas_efectivo DECIMAL(12,2) DEFAULT 0 COMMENT 'Total cobrado en efectivo',
        ventas_transferencia DECIMAL(12,2) DEFAULT 0 COMMENT 'Total en transferencias',
        ventas_tarjeta DECIMAL(12,2) DEFAULT 0 COMMENT 'Total en tarjetas',
        ventas_qr DECIMAL(12,2) DEFAULT 0 COMMENT 'Total en QR/MercadoPago',
        
        -- Contadores
        cantidad_ventas INT DEFAULT 0 COMMENT 'Número de ventas realizadas',
        cantidad_movimientos INT DEFAULT 0 COMMENT 'Movimientos manuales registrados',
        
        -- Estado y auditoría
        estado ENUM('abierto', 'cerrado', 'suspendido') DEFAULT 'abierto',
        notas TEXT NULL COMMENT 'Observaciones del turno',
        
        -- Timestamps
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Índices
        INDEX idx_usuario_id (usuario_id),
        INDEX idx_fecha_apertura (fecha_apertura),
        INDEX idx_estado (estado),
        INDEX idx_fecha_cierre (fecha_cierre),
        
        -- Constraints
        UNIQUE KEY unique_turno_abierto (usuario_id, estado),
        CHECK (monto_apertura >= 0),
        CHECK (total_entradas >= 0),
        CHECK (total_salidas >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Control de turnos y efectivo de caja'";
    
    $pdo->exec($sql);
    echo "✅ Tabla turnos_caja creada exitosamente\n";
    
    // Crear tabla detallada de movimientos de caja
    $sqlMovimientos = "CREATE TABLE IF NOT EXISTS movimientos_caja_detallados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        turno_id INT NOT NULL COMMENT 'ID del turno activo',
        tipo ENUM('ingreso', 'egreso', 'venta') NOT NULL,
        categoria VARCHAR(100) NOT NULL COMMENT 'Mercadería, Retiro, Servicios, etc.',
        monto DECIMAL(12,2) NOT NULL,
        descripcion TEXT NOT NULL COMMENT 'Descripción obligatoria del movimiento',
        referencia VARCHAR(200) NULL COMMENT 'Número de factura, comprobante, etc.',
        
        -- Datos de la venta (si aplica)
        venta_id INT NULL COMMENT 'ID de venta si es por venta',
        metodo_pago VARCHAR(50) NULL COMMENT 'efectivo, transferencia, tarjeta, qr',
        
        -- Auditoría
        usuario_id INT NOT NULL COMMENT 'Usuario que registró el movimiento',
        fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_registro VARCHAR(45) NULL,
        
        -- Timestamps
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        -- Índices
        INDEX idx_turno_id (turno_id),
        INDEX idx_tipo (tipo),
        INDEX idx_categoria (categoria),
        INDEX idx_fecha_movimiento (fecha_movimiento),
        INDEX idx_venta_id (venta_id),
        INDEX idx_usuario_id (usuario_id),
        
        -- Foreign key
        FOREIGN KEY (turno_id) REFERENCES turnos_caja(id) ON DELETE RESTRICT,
        
        -- Constraints
        CHECK (monto != 0),
        CHECK (descripcion != '')
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Detalle de todos los movimientos de efectivo'";
    
    $pdo->exec($sqlMovimientos);
    echo "✅ Tabla movimientos_caja_detallados creada exitosamente\n";
    
    // Crear vista para resumen de turnos
    $sqlVista = "CREATE OR REPLACE VIEW vista_resumen_turnos AS
    SELECT 
        t.id,
        t.usuario_id,
        u.nombre as cajero_nombre,
        t.fecha_apertura,
        t.fecha_cierre,
        t.monto_apertura,
        t.monto_cierre,
        t.total_entradas,
        t.total_salidas,
        t.efectivo_teorico,
        t.diferencia,
        t.ventas_efectivo,
        t.ventas_transferencia,
        t.ventas_tarjeta,
        t.ventas_qr,
        t.cantidad_ventas,
        t.cantidad_movimientos,
        t.estado,
        t.notas,
        TIMESTAMPDIFF(HOUR, t.fecha_apertura, COALESCE(t.fecha_cierre, NOW())) as horas_turno
    FROM turnos_caja t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    ORDER BY t.fecha_apertura DESC";
    
    $pdo->exec($sqlVista);
    echo "✅ Vista vista_resumen_turnos creada exitosamente\n";
    
    // Mostrar estructura
    echo "\n📋 ESTRUCTURA TURNOS_CAJA:\n";
    $stmt = $pdo->query("DESCRIBE turnos_caja");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    echo "\n📋 ESTRUCTURA MOVIMIENTOS_CAJA_DETALLADOS:\n";
    $stmt = $pdo->query("DESCRIBE movimientos_caja_detallados");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
