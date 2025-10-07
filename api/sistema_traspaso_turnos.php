<?php
/**
 * 🏢 SISTEMA EMPRESARIAL DE TRASPASO ENTRE TURNOS
 * Lógica para manejo profesional de cambio de turno MAÑANA/TARDE
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Origin");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=kiosco_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    case 'verificar_traspaso':
        verificarNecesidadTraspaso($pdo);
        break;
    case 'iniciar_traspaso':
        iniciarTraspaso($pdo);
        break;
    case 'completar_traspaso':
        completarTraspaso($pdo);
        break;
    case 'historial_traspasos':
        historialTraspasos($pdo);
        break;
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Acción no válida'
        ]);
}

/**
 * 🏢 Verificar si es necesario hacer traspaso de turno
 */
function verificarNecesidadTraspaso($pdo) {
    $horaActual = date('H:i:s');
    $fechaActual = date('Y-m-d');
    
    // Determinar turno actual según hora
    if ($horaActual >= '08:00:00' && $horaActual < '16:00:00') {
        $turnoActual = 'MAÑANA';
        $proximoTurno = 'TARDE';
        $horaLimite = '16:00:00';
    } elseif ($horaActual >= '16:00:00' && $horaActual <= '23:59:59') {
        $turnoActual = 'TARDE';
        $proximoTurno = 'MAÑANA';
        $horaLimite = '08:00:00';
    } else {
        $turnoActual = 'TARDE';
        $proximoTurno = 'MAÑANA';
        $horaLimite = '08:00:00';
    }
    
    // Verificar turno abierto
    $stmt = $pdo->query("SELECT * FROM turnos_caja WHERE estado = 'abierto'");
    $turnoAbierto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $necesitaTraspaso = false;
    $razon = '';
    
    if ($turnoAbierto) {
        $tipoTurnoAbierto = $turnoAbierto['tipo_turno'];
        
        if ($tipoTurnoAbierto !== $turnoActual) {
            $necesitaTraspaso = true;
            $razon = "El turno abierto es $tipoTurnoAbierto pero debería ser $turnoActual según la hora actual";
        }
    }
    
    echo json_encode([
        'success' => true,
        'necesita_traspaso' => $necesitaTraspaso,
        'turno_actual_esperado' => $turnoActual,
        'proximo_turno' => $proximoTurno,
        'hora_limite' => $horaLimite,
        'turno_abierto' => $turnoAbierto,
        'razon' => $razon,
        'recomendacion' => $necesitaTraspaso ? 
            "Se recomienda cerrar el turno $tipoTurnoAbierto y abrir turno $turnoActual" : 
            "El turno actual está correcto"
    ]);
}

/**
 * 🔄 Iniciar proceso de traspaso entre turnos
 */
function iniciarTraspaso($pdo) {
    $usuarioId = $_POST['usuario_id'] ?? 1;
    $observaciones = $_POST['observaciones'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // 1. Obtener turno actual
        $stmt = $pdo->prepare("SELECT * FROM turnos_caja WHERE estado = 'abierto' AND usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $turnoActual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$turnoActual) {
            throw new Exception('No hay turno activo para traspasar');
        }
        
        // 2. Calcular efectivo teórico final
        $stmt = $pdo->prepare("
            SELECT 
                monto_apertura,
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) as total_egresos,
                (
                    SELECT COALESCE(SUM(monto_total), 0) 
                    FROM ventas 
                    WHERE metodo_pago = 'efectivo' 
                    AND fecha >= ?
                ) as ventas_efectivo
            FROM movimientos_caja_detallados 
            WHERE turno_id = ?
        ");
        $stmt->execute([$turnoActual['fecha_apertura'], $turnoActual['id']]);
        $totales = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $efectivoTeorico = $totales['monto_apertura'] + $totales['total_ingresos'] + $totales['ventas_efectivo'] - $totales['total_egresos'];
        
        // 3. Marcar como "en_traspaso"
        $stmt = $pdo->prepare("
            UPDATE turnos_caja 
            SET estado = 'en_traspaso',
                efectivo_entrega = ?,
                observaciones_traspaso = ?
            WHERE id = ?
        ");
        $stmt->execute([$efectivoTeorico, $observaciones, $turnoActual['id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Traspaso iniciado exitosamente',
            'turno_id' => $turnoActual['id'],
            'efectivo_teorico' => $efectivoTeorico,
            'datos_turno' => $totales,
            'siguiente_paso' => 'Contar efectivo físico y completar traspaso'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * ✅ Completar traspaso y abrir nuevo turno
 */
function completarTraspaso($pdo) {
    $turnoAnteriorId = $_POST['turno_anterior_id'];
    $efectivoContado = $_POST['efectivo_contado'];
    $montoAperturaNuevo = $_POST['monto_apertura_nuevo'];
    $usuarioId = $_POST['usuario_id'] ?? 1;
    $observacionesCierre = $_POST['observaciones_cierre'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // 1. Cerrar turno anterior
        $diferencia = $efectivoContado - $montoAperturaNuevo;
        
        $stmt = $pdo->prepare("
            UPDATE turnos_caja 
            SET estado = 'cerrado',
                fecha_cierre = NOW(),
                monto_cierre = ?,
                diferencia = ?,
                observaciones_cierre = ?
            WHERE id = ?
        ");
        $stmt->execute([$efectivoContado, $diferencia, $observacionesCierre, $turnoAnteriorId]);
        
        // 2. Determinar tipo de nuevo turno
        $horaActual = date('H:i:s');
        if ($horaActual >= '08:00:00' && $horaActual < '16:00:00') {
            $tipoTurno = 'MAÑANA';
        } else {
            $tipoTurno = 'TARDE';
        }
        
        // 3. Abrir nuevo turno
        $stmt = $pdo->prepare("
            INSERT INTO turnos_caja (
                usuario_id, monto_apertura, fecha_apertura, estado, 
                tipo_turno, efectivo_traspaso, turno_anterior_id
            ) VALUES (?, ?, NOW(), 'abierto', ?, ?, ?)
        ");
        $stmt->execute([$usuarioId, $montoAperturaNuevo, $tipoTurno, $montoAperturaNuevo, $turnoAnteriorId]);
        
        $nuevoTurnoId = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'mensaje' => "Traspaso completado exitosamente. Nuevo turno $tipoTurno iniciado",
            'turno_anterior_id' => $turnoAnteriorId,
            'nuevo_turno_id' => $nuevoTurnoId,
            'tipo_turno' => $tipoTurno,
            'monto_apertura' => $montoAperturaNuevo,
            'diferencia_conteo' => $diferencia
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * 📋 Obtener historial de traspasos
 */
function historialTraspasos($pdo) {
    $limite = $_GET['limite'] ?? 10;
    
    $stmt = $pdo->prepare("
        SELECT 
            t1.id as turno_cerrado_id,
            t1.tipo_turno as tipo_cerrado,
            t1.fecha_apertura as inicio_turno,
            t1.fecha_cierre as fin_turno,
            t1.monto_apertura as apertura,
            t1.monto_cierre as cierre,
            t1.diferencia,
            t1.efectivo_entrega,
            t2.id as siguiente_turno_id,
            t2.tipo_turno as tipo_siguiente,
            t2.efectivo_traspaso,
            TIMESTAMPDIFF(HOUR, t1.fecha_apertura, t1.fecha_cierre) as duracion_horas
        FROM turnos_caja t1
        LEFT JOIN turnos_caja t2 ON t2.turno_anterior_id = t1.id
        WHERE t1.estado = 'cerrado'
        AND t1.turno_anterior_id IS NOT NULL
        ORDER BY t1.fecha_cierre DESC
        LIMIT ?
    ");
    $stmt->execute([$limite]);
    $traspasos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'traspasos' => $traspasos,
        'total' => count($traspasos)
    ]);
}
?>
