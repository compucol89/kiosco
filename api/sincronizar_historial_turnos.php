<?php
/**
 * api/sincronizar_historial_turnos.php
 * Sincroniza turnos de turnos_caja a historial_turnos_caja automáticamente
 * Ejecutar una vez para migrar datos y verificar que funcione
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar que existan las tablas
    $stmt = $pdo->query("SHOW TABLES LIKE 'turnos_caja'");
    $tieneturnosCaja = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'historial_turnos_caja'");
    $tieneHistorial = $stmt->rowCount() > 0;
    
    if (!$tieneturnosCaja) {
        throw new Exception('Tabla turnos_caja no existe');
    }
    
    if (!$tieneHistorial) {
        throw new Exception('Tabla historial_turnos_caja no existe');
    }
    
    $pdo->beginTransaction();
    
    // Obtener turnos que no están en el historial
    $stmt = $pdo->query("
        SELECT t.*, u.nombre as cajero_nombre
        FROM turnos_caja t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        WHERE NOT EXISTS (
            SELECT 1 FROM historial_turnos_caja h 
            WHERE h.numero_turno = t.id AND h.tipo_evento = 'apertura'
        )
        ORDER BY t.fecha_apertura ASC
    ");
    $turnosSinHistorial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $registrosCreados = 0;
    $eventos = [];
    
    foreach ($turnosSinHistorial as $turno) {
        $turnoId = $turno['id'];
        $usuarioId = $turno['usuario_id'];
        $cajeroNombre = $turno['cajero_nombre'] ?: 'Usuario';
        
        // Crear evento de APERTURA
        $stmtApertura = $pdo->prepare("
            INSERT INTO historial_turnos_caja (
                numero_turno, tipo_evento, cajero_id, cajero_nombre,
                fecha_hora, monto_inicial, efectivo_teorico, 
                efectivo_contado, diferencia, tipo_diferencia
            ) VALUES (?, 'apertura', ?, ?, ?, ?, ?, ?, 0, 'exacto')
        ");
        
        $stmtApertura->execute([
            $turnoId,
            $usuarioId,
            $cajeroNombre,
            $turno['fecha_apertura'],
            $turno['monto_apertura'],
            $turno['monto_apertura'],
            $turno['monto_apertura']
        ]);
        
        $registrosCreados++;
        $eventos[] = "Apertura Turno #{$turnoId}";
        
        // Si está cerrado, crear evento de CIERRE
        if ($turno['estado'] === 'cerrado' && $turno['fecha_cierre']) {
            $duracion = (strtotime($turno['fecha_cierre']) - strtotime($turno['fecha_apertura'])) / 60;
            
            $efectivoTeorico = floatval($turno['efectivo_teorico'] ?? $turno['monto_apertura']);
            $efectivoContado = floatval($turno['monto_cierre'] ?? 0);
            $diferencia = $efectivoContado - $efectivoTeorico;
            
            $tipoDiferencia = $diferencia == 0 ? 'exacto' : ($diferencia > 0 ? 'sobrante' : 'faltante');
            
            $stmtCierre = $pdo->prepare("
                INSERT INTO historial_turnos_caja (
                    numero_turno, tipo_evento, cajero_id, cajero_nombre,
                    fecha_hora, monto_inicial, efectivo_teorico, 
                    efectivo_contado, diferencia, tipo_diferencia,
                    cantidad_transacciones, duracion_turno_minutos
                ) VALUES (?, 'cierre', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmtCierre->execute([
                $turnoId,
                $usuarioId,
                $cajeroNombre,
                $turno['fecha_cierre'],
                $turno['monto_apertura'],
                $efectivoTeorico,
                $efectivoContado,
                $diferencia,
                $tipoDiferencia,
                $turno['cantidad_ventas'] ?? 0,
                $duracion
            ]);
            
            $registrosCreados++;
            $eventos[] = "Cierre Turno #{$turnoId}";
        }
    }
    
    $pdo->commit();
    
    // Contar total de eventos en historial
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM historial_turnos_caja");
    $totalEventos = $stmtTotal->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Sincronización completada',
        'turnos_procesados' => count($turnosSinHistorial),
        'registros_creados' => $registrosCreados,
        'eventos' => $eventos,
        'total_eventos_historial' => (int)$totalEventos,
        'nota' => 'Ahora el módulo Historial de Turnos debería mostrar información'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>

