<?php
/**
 * 🔄 LÓGICA DE CONTINUIDAD ENTRE TURNOS
 * Implementa tu propuesta de trazabilidad completa
 */

/**
 * Obtener el monto recomendado para apertura del nuevo turno
 */
function obtenerMontoRecomendadoApertura($pdo, $usuarioId) {
    // Buscar el último turno cerrado
    $stmt = $pdo->prepare("
        SELECT 
            efectivo_teorico,
            monto_cierre,
            diferencia,
            fecha_cierre,
            cajero_nombre
        FROM vista_resumen_turnos 
        WHERE estado = 'cerrado'
        ORDER BY fecha_cierre DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $ultimoTurno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimoTurno) {
        return [
            'monto_recomendado' => floatval($ultimoTurno['monto_cierre']),
            'justificacion' => "Efectivo del último turno cerrado ({$ultimoTurno['cajero_nombre']}) el " . 
                             date('d/m/Y H:i', strtotime($ultimoTurno['fecha_cierre'])),
            'diferencia_anterior' => floatval($ultimoTurno['diferencia']),
            'hay_recomendacion' => true
        ];
    }
    
    return [
        'monto_recomendado' => 0,
        'justificacion' => 'No hay turnos anteriores para referencia',
        'diferencia_anterior' => 0,
        'hay_recomendacion' => false
    ];
}

/**
 * Registrar venta en efectivo en el turno actual
 */
function registrarVentaEnTurno($pdo, $usuarioId, $montoEfectivo, $ventaId = null) {
    // Obtener turno activo
    $stmt = $pdo->prepare("
        SELECT id FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto' 
        LIMIT 1
    ");
    $stmt->execute([$usuarioId]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$turno) {
        throw new Exception('No hay turno activo para registrar la venta');
    }
    
    // Actualizar el turno con la venta en efectivo
    $stmt = $pdo->prepare("
        UPDATE turnos_caja SET
            entradas_efectivo = entradas_efectivo + ?,
            ventas_efectivo = ventas_efectivo + ?,
            cantidad_ventas = cantidad_ventas + 1,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$montoEfectivo, $montoEfectivo, $turno['id']]);
    
    // Registrar en movimientos detallados
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_caja_detallados (
            turno_id, tipo, categoria, monto, descripcion, 
            venta_id, metodo_pago, usuario_id
        ) VALUES (?, 'venta', 'Venta POS', ?, ?, ?, 'efectivo', ?)
    ");
    $stmt->execute([
        $turno['id'], 
        $montoEfectivo, 
        "Venta en efectivo" . ($ventaId ? " #$ventaId" : ""),
        $ventaId,
        $usuarioId
    ]);
    
    return $turno['id'];
}

/**
 * Abrir turno con recomendación de continuidad
 */
function abrirTurnoConContinuidad($pdo, $usuarioId, $montoApertura, $notas = '') {
    // Obtener recomendación
    $recomendacion = obtenerMontoRecomendadoApertura($pdo, $usuarioId);
    
    // Calcular saldo anterior
    $saldoAnterior = $recomendacion['monto_recomendado'];
    
    // Insertar nuevo turno con continuidad
    $stmt = $pdo->prepare("
        INSERT INTO turnos_caja (
            usuario_id, 
            monto_apertura, 
            saldo_anterior,
            notas
        ) VALUES (?, ?, ?, ?)
    ");
    
    $notasCompletas = $notas;
    if ($recomendacion['hay_recomendacion']) {
        $notasCompletas .= "\n[CONTINUIDAD] Recomendado: $" . number_format($saldoAnterior, 2) . 
                          " | Declarado: $" . number_format($montoApertura, 2) . 
                          " | Diferencia: $" . number_format($montoApertura - $saldoAnterior, 2);
    }
    
    $stmt->execute([$usuarioId, $montoApertura, $saldoAnterior, $notasCompletas]);
    
    return [
        'turno_id' => $pdo->lastInsertId(),
        'recomendacion' => $recomendacion,
        'diferencia_inicial' => $montoApertura - $saldoAnterior
    ];
}
?>
