<?php
/**
 * 🔧 AUTO-VERIFICADOR DE TURNOS
 * Asegura que siempre haya un turno abierto para registrar movimientos
 */

function verificarYAbrirTurnoSiNecesario($pdo, $usuarioId = 1) {
    try {
        // Verificar si hay turno abierto
        $stmt = $pdo->prepare("SELECT id FROM turnos_caja WHERE estado = 'abierto' LIMIT 1");
        $stmt->execute();
        $turnoActivo = $stmt->fetchColumn();
        
        if (!$turnoActivo) {
            // No hay turno abierto, crear uno automáticamente
            $stmt = $pdo->prepare("
                INSERT INTO turnos_caja (
                    usuario_id, monto_apertura, estado, fecha_apertura, 
                    notas, created_at, updated_at
                ) VALUES (?, 1000, 'abierto', NOW(), 'Turno abierto automáticamente', NOW(), NOW())
            ");
            
            $stmt->execute([$usuarioId]);
            $nuevoTurnoId = $pdo->lastInsertId();
            
            error_log("Auto-Turno: Abierto turno #{$nuevoTurnoId} automáticamente para usuario {$usuarioId}");
            
            return $nuevoTurnoId;
        }
        
        return $turnoActivo;
        
    } catch (Exception $e) {
        error_log("Error en auto-verificador de turnos: " . $e->getMessage());
        return false;
    }
}

// Incluir esta función en los endpoints que registran movimientos
?>


















