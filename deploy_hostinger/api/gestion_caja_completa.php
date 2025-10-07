<?php
/**
 * 💼 API GESTIÓN DE CAJA COMPLETA - CONTROL TOTAL DE EFECTIVO
 * Sistema completo para apertura, control y cierre de turnos de caja
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


try {
    // 🔧 DEBUG: Registrar TODAS las peticiones
    error_log("GESTION_CAJA: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
    error_log("GESTION_CAJA: RAW_INPUT: " . file_get_contents('php://input'));
    error_log("GESTION_CAJA: GET_PARAMS: " . json_encode($_GET));
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['accion'] ?? '';
    
    switch ($metodo) {
        case 'GET':
            switch ($accion) {
                case 'estado_caja':
                    obtenerEstadoCaja($pdo);
                    break;
                case 'estado_completo':
                    obtenerEstadoCompleto($pdo);
                    break;
                case 'turno_activo':
                    obtenerTurnoActivo($pdo);
                    break;
                case 'historial_movimientos':
                    obtenerHistorialMovimientos($pdo);
                    break;
                case 'resumen_metodos_pago':
                    obtenerResumenMetodosPago($pdo);
                    break;
                case 'historial_turnos':
                    obtenerHistorialTurnos($pdo);
                    break;
                case 'ultimo_cierre':
                    obtenerUltimoCierre($pdo);
                    break;
                case 'historial_completo':
                    obtenerHistorialCompleto($pdo);
                    break;
                case 'validar_turno_unico':
                    validarTurnoUnico($pdo);
                    break;
                case 'resumen_movimientos_turno':
                    obtenerResumenMovimientosTurno($pdo);
                    break;
                default:
                    throw new Exception('Acción GET no válida');
            }
            break;
            
        case 'POST':
            switch ($accion) {
                case 'abrir_caja':
                    abrirCaja($pdo);
                    break;
                case 'registrar_movimiento':
                    registrarMovimiento($pdo);
                    break;
                case 'cerrar_caja':
                    cerrarCaja($pdo);
                    break;
                case 'cerrar_turno_emergencia':
                    cerrarTurnoEmergencia($pdo);
                    break;
                default:
                    throw new Exception('Acción POST no válida');
            }
            break;
            
        default:
            throw new Exception('Método HTTP no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * 📋 Obtener último cierre para referencia en apertura
 */
function obtenerUltimoCierre($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? 1;
    
    try {
        // Obtener el último turno cerrado
        $stmt = $pdo->prepare("
            SELECT 
                id,
                usuario_id,
                fecha_apertura,
                fecha_cierre,
                monto_apertura,
                monto_cierre,
                diferencia,
                efectivo_teorico,
                tipo_turno,
                notas
            FROM turnos_caja 
            WHERE estado = 'cerrado' 
            AND usuario_id = ?
            ORDER BY fecha_cierre DESC 
            LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        $ultimoCierre = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ultimoCierre) {
            // Formatear fechas para mejor presentación
            $ultimoCierre['fecha_cierre_formateada'] = date('d/m/Y H:i', strtotime($ultimoCierre['fecha_cierre']));
            $ultimoCierre['fecha_apertura_formateada'] = date('d/m/Y H:i', strtotime($ultimoCierre['fecha_apertura']));
        }
        
        echo json_encode([
            'success' => true,
            'ultimo_cierre' => $ultimoCierre,
            'mensaje' => $ultimoCierre ? 'Último cierre encontrado' : 'No hay cierres previos'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * 📊 Obtener historial completo de turnos con filtros
 */
function obtenerHistorialCompleto($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? null;
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $tipoEvento = $_GET['tipo_evento'] ?? null; // 'apertura', 'cierre', 'todos'
    $cajeroId = $_GET['cajero_id'] ?? null;
    $limite = (int)($_GET['limite'] ?? 50);
    $pagina = (int)($_GET['pagina'] ?? 1);
    $offset = ($pagina - 1) * $limite;
    
    try {
        // Construir query dinámicamente según filtros
        $conditions = [];
        $params = [];
        
        if ($usuarioId) {
            $conditions[] = "h.cajero_id = ?";
            $params[] = $usuarioId;
        }
        
        if ($cajeroId && $cajeroId !== 'todos') {
            $conditions[] = "h.cajero_id = ?";
            $params[] = $cajeroId;
        }
        
        if ($fechaInicio) {
            $conditions[] = "DATE(h.fecha_hora) >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $conditions[] = "DATE(h.fecha_hora) <= ?";
            $params[] = $fechaFin;
        }
        
        if ($tipoEvento && $tipoEvento !== 'todos') {
            $conditions[] = "h.tipo_evento = ?";
            $params[] = $tipoEvento;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        // Query principal con información enriquecida
        $sql = "
            SELECT 
                h.*,
                u.nombre as cajero_nombre_completo,
                DATE_FORMAT(h.fecha_hora, '%d/%m/%Y %H:%i') as fecha_formateada,
                DATE_FORMAT(h.fecha_hora, '%Y-%m-%d') as fecha_solo,
                TIME_FORMAT(h.fecha_hora, '%H:%i') as hora_solo,
                CASE h.tipo_evento
                    WHEN 'apertura' THEN '🔓'
                    WHEN 'cierre' THEN '🔒'
                    ELSE '❓'
                END as icono_evento,
                CASE h.tipo_diferencia
                    WHEN 'exacto' THEN '✅'
                    WHEN 'sobrante' THEN '📈'
                    WHEN 'faltante' THEN '📉'
                    ELSE '➖'
                END as icono_diferencia,
                CASE
                    WHEN h.duracion_turno_minutos IS NULL THEN NULL
                    WHEN h.duracion_turno_minutos > 720 THEN 'Muy largo'
                    WHEN h.duracion_turno_minutos > 480 THEN 'Largo'
                    WHEN h.duracion_turno_minutos > 240 THEN 'Normal'
                    ELSE 'Corto'
                END as duracion_categoria,
                CASE
                    WHEN ABS(h.diferencia) = 0 THEN 'Perfecto'
                    WHEN ABS(h.diferencia) <= 100 THEN 'Aceptable'
                    WHEN ABS(h.diferencia) <= 500 THEN 'Alto'
                    ELSE 'Crítico'
                END as nivel_diferencia
            FROM historial_turnos_caja h
            LEFT JOIN usuarios u ON h.cajero_id = u.id
            $whereClause
            ORDER BY h.fecha_hora DESC, h.numero_turno DESC, h.tipo_evento ASC
            LIMIT $limite OFFSET $offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar total de registros para paginación
        $sqlCount = "
            SELECT COUNT(*) as total
            FROM historial_turnos_caja h
            $whereClause
        ";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Estadísticas del período filtrado
        $sqlStats = "
            SELECT 
                COUNT(*) as total_eventos,
                COUNT(CASE WHEN tipo_evento = 'apertura' THEN 1 END) as total_aperturas,
                COUNT(CASE WHEN tipo_evento = 'cierre' THEN 1 END) as total_cierres,
                COUNT(DISTINCT cajero_id) as cajeros_unicos,
                COUNT(DISTINCT numero_turno) as turnos_unicos,
                AVG(CASE WHEN tipo_evento = 'cierre' THEN ABS(diferencia) END) as diferencia_promedio,
                SUM(CASE WHEN tipo_evento = 'cierre' THEN diferencia END) as diferencia_total,
                COUNT(CASE WHEN tipo_evento = 'cierre' AND diferencia = 0 THEN 1 END) as cierres_exactos,
                COUNT(CASE WHEN tipo_evento = 'cierre' AND diferencia > 0 THEN 1 END) as cierres_sobrantes,
                COUNT(CASE WHEN tipo_evento = 'cierre' AND diferencia < 0 THEN 1 END) as cierres_faltantes,
                AVG(CASE WHEN duracion_turno_minutos IS NOT NULL THEN duracion_turno_minutos END) as duracion_promedio,
                MAX(CASE WHEN duracion_turno_minutos IS NOT NULL THEN duracion_turno_minutos END) as duracion_maxima,
                MIN(fecha_hora) as fecha_mas_antigua,
                MAX(fecha_hora) as fecha_mas_reciente
            FROM historial_turnos_caja h
            $whereClause
        ";
        $stmtStats = $pdo->prepare($sqlStats);
        $stmtStats->execute($params);
        $estadisticas = $stmtStats->fetch(PDO::FETCH_ASSOC);
        
        // Lista de cajeros para el filtro
        $sqlCajeros = "
            SELECT DISTINCT h.cajero_id, h.cajero_nombre, COUNT(*) as cantidad_eventos
            FROM historial_turnos_caja h
            GROUP BY h.cajero_id, h.cajero_nombre
            ORDER BY h.cajero_nombre
        ";
        $cajeros = $pdo->query($sqlCajeros)->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'historial' => $historial,
            'paginacion' => [
                'total_registros' => (int)$total,
                'pagina_actual' => $pagina,
                'limite_por_pagina' => $limite,
                'total_paginas' => (int)ceil($total / $limite),
                'tiene_siguiente' => ($pagina * $limite) < $total,
                'tiene_anterior' => $pagina > 1
            ],
            'estadisticas' => $estadisticas,
            'filtros_disponibles' => [
                'cajeros' => $cajeros,
                'tipos_evento' => [
                    ['value' => 'todos', 'label' => 'Todos los eventos'],
                    ['value' => 'apertura', 'label' => 'Solo aperturas'],
                    ['value' => 'cierre', 'label' => 'Solo cierres']
                ]
            ],
            'filtros_aplicados' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipo_evento' => $tipoEvento,
                'cajero_id' => $cajeroId,
                'limite' => $limite,
                'pagina' => $pagina
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * 🔍 Obtener estado actual de la caja
 */
function obtenerEstadoCaja($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? 1;
    
    // Obtener turno activo con datos completos (igual que obtenerTurnoActivo)
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            u.nombre as cajero_nombre,
            -- Cálculos de movimientos manuales
            COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.monto ELSE 0 END), 0) as entradas_efectivo,
            COALESCE(SUM(CASE WHEN m.tipo = 'egreso' THEN ABS(m.monto) ELSE 0 END), 0) as salidas_efectivo,
            COUNT(CASE WHEN m.tipo IN ('ingreso', 'egreso') THEN 1 END) as movimientos_manuales,
            -- 🔧 CALCULAR VENTAS EN EFECTIVO REALES DEL TURNO
            COALESCE((
                SELECT SUM(monto_total) 
                FROM ventas v 
                WHERE v.metodo_pago = 'efectivo'
                AND v.fecha >= t.fecha_apertura 
                AND (t.fecha_cierre IS NULL OR v.fecha <= t.fecha_cierre)
                AND v.estado IN ('completado', 'completada')
            ), 0) as ventas_efectivo_reales,
            COALESCE((
                SELECT COUNT(*) 
                FROM ventas v 
                WHERE v.fecha >= t.fecha_apertura 
                AND (t.fecha_cierre IS NULL OR v.fecha <= t.fecha_cierre)
                AND v.estado IN ('completado', 'completada')
            ), 0) as ventas_realizadas
        FROM turnos_caja t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN movimientos_caja_detallados m ON t.id = m.turno_id
        WHERE t.usuario_id = ? AND t.estado = 'abierto'
        GROUP BY t.id
        LIMIT 1
    ");
    $stmt->execute([$usuarioId]);
    $turnoActivo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$turnoActivo) {
        echo json_encode([
            'success' => true,
            'caja_abierta' => false,
            'mensaje' => 'No hay turno activo. Debe abrir la caja para comenzar.',
            'turno' => null
        ]);
        return;
    }
    
    // 🔧 CALCULAR EFECTIVO TEÓRICO CORRECTO (apertura + entradas + ventas_efectivo - salidas)
    $efectivoTeorico = $turnoActivo['monto_apertura'] + $turnoActivo['entradas_efectivo'] + $turnoActivo['ventas_efectivo_reales'] - $turnoActivo['salidas_efectivo'];
    $turnoActivo['efectivo_teorico'] = $efectivoTeorico;
    
    // 🏦 TOTAL DE ENTRADAS EN EFECTIVO (para compatibilidad con frontend)
    $turnoActivo['total_entradas_efectivo'] = $turnoActivo['ventas_efectivo_reales'] + $turnoActivo['entradas_efectivo'];
    
    // 🚨 DEBUG - Log del cálculo
    error_log("🔍 CÁLCULO EFECTIVO TEÓRICO: apertura={$turnoActivo['monto_apertura']} + entradas={$turnoActivo['entradas_efectivo']} + ventas={$turnoActivo['ventas_efectivo_reales']} - salidas={$turnoActivo['salidas_efectivo']} = {$efectivoTeorico}");
    
    // Calcular totales del turno
    $stmtTotales = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
            SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as total_egresos,
            COUNT(CASE WHEN tipo = 'venta' THEN 1 END) as total_ventas,
            COUNT(*) as total_movimientos
        FROM movimientos_caja_detallados 
        WHERE turno_id = ?
    ");
    $stmtTotales->execute([$turnoActivo['id']]);
    $totales = $stmtTotales->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'caja_abierta' => true,
        'turno' => $turnoActivo,
        'totales' => $totales,
        'efectivo_disponible' => $efectivoTeorico
    ]);
}

/**
 * 📊 Obtener turno activo con datos completos
 */
function obtenerTurnoActivo($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? 1;
    
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            u.nombre as cajero_nombre,
            -- Cálculos de movimientos manuales
            COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.monto ELSE 0 END), 0) as entradas_efectivo,
            COALESCE(SUM(CASE WHEN m.tipo = 'egreso' THEN ABS(m.monto) ELSE 0 END), 0) as salidas_efectivo,
            COUNT(CASE WHEN m.tipo IN ('ingreso', 'egreso') THEN 1 END) as movimientos_manuales,
            -- 🔧 CALCULAR VENTAS EN EFECTIVO REALES DEL TURNO
            COALESCE((
                SELECT SUM(monto_total) 
                FROM ventas v 
                WHERE v.metodo_pago = 'efectivo'
                AND v.fecha >= t.fecha_apertura 
                AND (t.fecha_cierre IS NULL OR v.fecha <= t.fecha_cierre)
                AND v.estado IN ('completado', 'completada')
            ), 0) as ventas_efectivo_reales,
            COALESCE((
                SELECT COUNT(*) 
                FROM ventas v 
                WHERE v.fecha >= t.fecha_apertura 
                AND (t.fecha_cierre IS NULL OR v.fecha <= t.fecha_cierre)
                AND v.estado IN ('completado', 'completada')
            ), 0) as ventas_realizadas
        FROM turnos_caja t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN movimientos_caja_detallados m ON t.id = m.turno_id
        WHERE t.usuario_id = ? AND t.estado = 'abierto'
        GROUP BY t.id
        LIMIT 1
    ");
    $stmt->execute([$usuarioId]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$turno) {
        echo json_encode([
            'success' => false,
            'error' => 'No hay turno activo'
        ]);
        return;
    }
    
    // 🔧 CORRECCIÓN: Calcular efectivo disponible INCLUYENDO ventas en efectivo
    $efectivoDisponible = $turno['monto_apertura'] + $turno['entradas_efectivo'] + $turno['ventas_efectivo_reales'] - $turno['salidas_efectivo'];
    
    // 🎯 EFECTIVO TEÓRICO CORRECTO para el modal de cierre (INCLUYENDO ventas)
    $efectivoTeorico = $turno['monto_apertura'] + $turno['entradas_efectivo'] + $turno['ventas_efectivo_reales'] - $turno['salidas_efectivo'];
    
    // 🔧 Agregar el turno corregido con efectivo_teorico calculado correctamente
    $turno['efectivo_teorico'] = $efectivoTeorico;
    
    // 🏦 TOTAL DE ENTRADAS EN EFECTIVO (para compatibilidad con frontend)
    $turno['total_entradas_efectivo'] = $turno['ventas_efectivo_reales'] + $turno['entradas_efectivo'];
    
    echo json_encode([
        'success' => true,
        'turno' => $turno,
        'efectivo_disponible' => $efectivoDisponible,
        'datos_calculados' => [
            'entradas_efectivo' => (float)$turno['entradas_efectivo'],
            'salidas_efectivo' => (float)$turno['salidas_efectivo'],
            'ventas_realizadas' => (int)$turno['ventas_realizadas'],
            'movimientos_manuales' => (int)$turno['movimientos_manuales'],
            'efectivo_teorico_corregido' => $efectivoTeorico
        ]
    ]);
}

/**
 * 📋 Obtener historial de movimientos del turno activo
 */
function obtenerHistorialMovimientos($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? 1;
    $limite = $_GET['limite'] ?? 50;
    
    // 🏢 LÓGICA EMPRESARIAL: Obtener turno activo CON información de tipo
    $stmtTurno = $pdo->prepare("
        SELECT id, tipo_turno, fecha_apertura, monto_apertura
        FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
        LIMIT 1
    ");
    $stmtTurno->execute([$usuarioId]);
    $turnoActivo = $stmtTurno->fetch(PDO::FETCH_ASSOC);
    
    if (!$turnoActivo) {
        echo json_encode([
            'success' => false,
            'error' => 'No hay turno activo'
        ]);
        return;
    }
    
    $turnoId = $turnoActivo['id'];
    $tipoTurno = $turnoActivo['tipo_turno'];
    $fechaApertura = $turnoActivo['fecha_apertura'];
    
    // 🎯 LÓGICA EMPRESARIAL MEJORADA: Determinar rango de fechas para mostrar
    $fechaInicio = date('Y-m-d', strtotime($fechaApertura));
    
    // Si es turno MAÑANA: mostrar desde 8:00 AM del día
    // Si es turno TARDE: mostrar desde 4:00 PM del día (incluye ventas del turno mañana del mismo día)
    if ($tipoTurno === 'MAÑANA') {
        $fechaFiltro = $fechaInicio . ' 08:00:00';
    } elseif ($tipoTurno === 'TARDE') {
        $fechaFiltro = $fechaInicio . ' 08:00:00'; // Mostrar TODO el día para trazabilidad completa
    } else {
        // Si no tiene tipo definido, usar fecha de apertura
        $fechaFiltro = $fechaApertura;
    }
    
    // Obtener movimientos paso a paso para evitar problemas de UNION
    $movimientos = [];
    
    // 1. Apertura de caja
    $stmtApertura = $pdo->prepare("
        SELECT 
            'apertura' as tipo,
            'Apertura de Caja' as categoria,
            monto_apertura as monto,
            CONCAT('Apertura de turno - $', FORMAT(monto_apertura, 2)) as descripcion,
            CONCAT('TURNO-', id) as referencia,
            usuario_id,
            fecha_apertura as fecha_movimiento,
            DATE_FORMAT(fecha_apertura, '%d/%m %H:%i') as fecha_formateada,
            'Sistema' as usuario_nombre
        FROM turnos_caja 
        WHERE id = ?
    ");
    $stmtApertura->execute([$turnoId]);
    $apertura = $stmtApertura->fetchAll(PDO::FETCH_ASSOC);
    $movimientos = array_merge($movimientos, $apertura);
    
    // 2. 🏢 VENTAS EN EFECTIVO CON LÓGICA EMPRESARIAL MEJORADA
    $stmtVentas = $pdo->prepare("
        SELECT 
            'venta' as tipo,
            'Venta Efectivo' as categoria,
            monto_total as monto,
            CONCAT('Venta #', id, ' - EFECTIVO') as descripcion,
            CONCAT('VENTA-', id) as referencia,
            COALESCE(usuario_id, 1) as usuario_id,
            fecha as fecha_movimiento,
            DATE_FORMAT(fecha, '%d/%m %H:%i') as fecha_formateada,
            'Sistema POS' as usuario_nombre
        FROM ventas 
        WHERE metodo_pago = 'efectivo'
        AND fecha >= ?
        ORDER BY fecha DESC
    ");
    $stmtVentas->execute([$fechaFiltro]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    $movimientos = array_merge($movimientos, $ventas);
    
    // 3. Movimientos manuales
    $stmtManuales = $pdo->prepare("
        SELECT 
            m.tipo,
            m.categoria,
            m.monto,
            m.descripcion,
            COALESCE(m.referencia, '') as referencia,
            m.usuario_id,
            m.fecha_movimiento,
            DATE_FORMAT(m.fecha_movimiento, '%d/%m %H:%i') as fecha_formateada,
            COALESCE(u.nombre, 'Usuario') as usuario_nombre
        FROM movimientos_caja_detallados m
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.turno_id = ?
        ORDER BY m.fecha_movimiento DESC
    ");
    $stmtManuales->execute([$turnoId]);
    $manuales = $stmtManuales->fetchAll(PDO::FETCH_ASSOC);
    $movimientos = array_merge($movimientos, $manuales);
    
    // Ordenar todos los movimientos por fecha descendente
    usort($movimientos, function($a, $b) {
        return strtotime($b['fecha_movimiento']) - strtotime($a['fecha_movimiento']);
    });
    
    // Limitar resultados
    $movimientos = array_slice($movimientos, 0, $limite);
    
    echo json_encode([
        'success' => true,
        'movimientos' => $movimientos,
        'turno_id' => $turnoId,
        'info_turno' => [
            'id' => $turnoId,
            'tipo' => $tipoTurno,
            'fecha_apertura' => $fechaApertura,
            'fecha_filtro_aplicado' => $fechaFiltro,
            'logica_aplicada' => $tipoTurno === 'TARDE' ? 'Mostrando todo el día para trazabilidad completa' : 'Mostrando desde inicio del turno'
        ]
    ]);
}

/**
 * 💳 Obtener resumen de métodos de pago del turno
 */
function obtenerResumenMetodosPago($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? 1;
    
    // Obtener turno activo
    $stmtTurno = $pdo->prepare("
        SELECT id FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
        LIMIT 1
    ");
    $stmtTurno->execute([$usuarioId]);
    $turnoId = $stmtTurno->fetchColumn();
    
    if (!$turnoId) {
        echo json_encode([
            'success' => false,
            'error' => 'No hay turno activo'
        ]);
        return;
    }
    
    // Obtener resumen por método de pago (incluyendo ventas reales del sistema)
    $stmt = $pdo->prepare("
        SELECT 
            v.metodo_pago,
            SUM(v.monto_total) as total_monto,
            COUNT(*) as cantidad_transacciones
        FROM ventas v
        INNER JOIN turnos_caja t ON DATE(v.fecha) = DATE(t.fecha_apertura)
        WHERE t.id = ? AND t.estado = 'abierto' 
        AND v.estado IN ('completado', 'completada')
        AND v.metodo_pago IS NOT NULL
        GROUP BY v.metodo_pago
    ");
    $stmt->execute([$turnoId]);
    $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    $resumen = [
        'efectivo' => ['total' => 0, 'transacciones' => 0],
        'transferencia' => ['total' => 0, 'transacciones' => 0],
        'tarjeta' => ['total' => 0, 'transacciones' => 0],
        'qr' => ['total' => 0, 'transacciones' => 0]
    ];
    
    foreach ($metodos as $metodo) {
        $tipo = strtolower($metodo['metodo_pago']);
        if (isset($resumen[$tipo])) {
            $resumen[$tipo]['total'] = (float)$metodo['total_monto'];
            $resumen[$tipo]['transacciones'] = (int)$metodo['cantidad_transacciones'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'resumen_metodos' => $resumen,
        'turno_id' => $turnoId
    ]);
}

/**
 * 🔓 Abrir caja para nuevo turno
 */
function abrirCaja($pdo) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos JSON inválidos: ' . $rawInput . ' Error: ' . json_last_error_msg());
    }
    
    $usuarioId = intval($input['usuario_id'] ?? 1);
    $efectivoContado = $input['efectivo_contado'] ?? null; // 🔥 NUEVO: Efectivo físico contado por el cajero
    $montoApertura = $input['monto_apertura'] ?? null; // 🔥 NUEVO: Monto de apertura del frontend
    $notas = trim($input['notas'] ?? '');
    
    // 🔥 OBTENER EFECTIVO ESPERADO DEL ÚLTIMO CIERRE
    $stmtUltimoCierre = $pdo->prepare("
        SELECT id, efectivo_teorico, fecha_cierre
        FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'cerrado' AND efectivo_teorico > 0
        ORDER BY fecha_cierre DESC 
        LIMIT 1
    ");
    $stmtUltimoCierre->execute([$usuarioId]);
    $ultimoCierre = $stmtUltimoCierre->fetch(PDO::FETCH_ASSOC);
    
    $efectivoEsperado = $ultimoCierre ? floatval($ultimoCierre['efectivo_teorico']) : 0;
    
    // 🔥 VERIFICACIÓN MANUAL OBLIGATORIA
    if ($efectivoEsperado > 0 && $efectivoContado === null) {
        // Retornar información para que el frontend pida la verificación
        echo json_encode([
            'success' => false,
            'requiere_verificacion' => true,
            'efectivo_esperado' => $efectivoEsperado,
            'ultimo_cierre' => [
                'id' => $ultimoCierre['id'],
                'fecha_cierre' => $ultimoCierre['fecha_cierre'],
                'efectivo_teorico' => $ultimoCierre['efectivo_teorico']
            ],
            'mensaje' => 'Se requiere verificación manual del efectivo físico'
        ]);
        return;
    }
    
    // Si no hay cierre anterior o ya se verificó el efectivo
    $efectivoContadoFloat = $efectivoContado !== null ? floatval($efectivoContado) : $efectivoEsperado;
    
    // 🔥 CORRECCIÓN: Si no hay cierre anterior, usar el monto_apertura del frontend
    if ($efectivoEsperado == 0 && $montoApertura !== null) {
        $efectivoContadoFloat = floatval($montoApertura);
        $efectivoEsperado = 0; // Primera apertura, no hay efectivo esperado
    }
    
    $diferenciApertura = $efectivoContadoFloat - $efectivoEsperado;
    
    if ($efectivoContadoFloat < 0) {
        throw new Exception('El efectivo contado no puede ser negativo');
    }
    
    // El monto de apertura es el efectivo físico real contado
    $montoAperturaFinal = $efectivoContadoFloat;
    
    // Log para debugging
    error_log("APERTURA_CAJA: Efectivo esperado: $efectivoEsperado");
    error_log("APERTURA_CAJA: Efectivo contado: $efectivoContadoFloat");
    error_log("APERTURA_CAJA: Monto apertura frontend: " . ($montoApertura ?? 'null'));
    error_log("APERTURA_CAJA: Monto apertura final: $montoAperturaFinal");
    error_log("APERTURA_CAJA: Diferencia: $diferenciApertura");
    
    // Verificar que no hay turno abierto
    $stmtVerificar = $pdo->prepare("
        SELECT id FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
        LIMIT 1
    ");
    $stmtVerificar->execute([$usuarioId]);
    
    if ($stmtVerificar->fetchColumn()) {
        throw new Exception('Ya existe un turno abierto para este usuario');
    }
    
    // 🔥 CONSTRUIR NOTAS CON INFORMACIÓN DE VERIFICACIÓN
    $notasCompletas = $notas;
    if ($efectivoEsperado > 0) {
        $tipoDiferencia = $diferenciApertura == 0 ? 'exacto' : ($diferenciApertura > 0 ? 'sobrante' : 'faltante');
        $notasVerificacion = "\n[VERIFICACIÓN APERTURA] " . date('Y-m-d H:i:s') . ":\n";
        $notasVerificacion .= "Efectivo esperado: $" . number_format($efectivoEsperado, 2) . "\n";
        $notasVerificacion .= "Efectivo contado: $" . number_format($efectivoContadoFloat, 2) . "\n";
        $notasVerificacion .= "Diferencia: " . ($diferenciApertura >= 0 ? '+' : '') . '$' . number_format($diferenciApertura, 2) . " ($tipoDiferencia)";
        $notasCompletas .= $notasVerificacion;
    }
    
    // Crear nuevo turno con verificación
    $stmtInsertar = $pdo->prepare("
        INSERT INTO turnos_caja (
            usuario_id, 
            monto_apertura, 
            notas
        ) VALUES (?, ?, ?)
    ");
    $stmtInsertar->execute([$usuarioId, $montoAperturaFinal, $notasCompletas]);
    
    $turnoId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Caja abierta exitosamente con verificación',
        'turno_id' => $turnoId,
        'monto_apertura' => $montoApertura,
        'efectivo_esperado' => $efectivoEsperado,
        'efectivo_contado' => $efectivoContadoFloat,
        'diferencia_apertura' => $diferenciApertura,
        'tipo_diferencia' => $efectivoEsperado > 0 ? ($diferenciApertura == 0 ? 'exacto' : ($diferenciApertura > 0 ? 'sobrante' : 'faltante')) : 'sin_verificacion',
        'verificacion_aplicada' => $efectivoEsperado > 0,
        'ultimo_cierre' => $ultimoCierre,
        'fecha_apertura' => date('Y-m-d H:i:s')
    ]);
}

/**
 * 📝 Registrar movimiento de efectivo
 */
function registrarMovimiento($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $usuarioId = intval($input['usuario_id'] ?? 1);
    $tipo = $input['tipo'] ?? ''; // ingreso, egreso
    $categoria = trim($input['categoria'] ?? '');
    $monto = floatval($input['monto'] ?? 0); // SIN redondeo automático para preservar exactitud
    $descripcion = trim($input['descripcion'] ?? '');
    $referencia = trim($input['referencia'] ?? '');
    
    // Validaciones
    if (!in_array($tipo, ['ingreso', 'egreso'])) {
        throw new Exception('Tipo de movimiento inválido');
    }
    
    if ($monto <= 0) {
        throw new Exception('El monto debe ser mayor a cero');
    }
    
    if (empty($descripcion)) {
        throw new Exception('La descripción es obligatoria');
    }
    
    if (empty($categoria)) {
        throw new Exception('La categoría es obligatoria');
    }
    
    // Obtener turno activo
    $stmtTurno = $pdo->prepare("
        SELECT id FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
        LIMIT 1
    ");
    $stmtTurno->execute([$usuarioId]);
    $turnoId = $stmtTurno->fetchColumn();
    
    if (!$turnoId) {
        throw new Exception('No hay turno activo. Debe abrir la caja primero.');
    }
    
    // Ajustar monto según tipo
    $montoFinal = ($tipo === 'egreso') ? -abs($monto) : abs($monto);
    
    // Insertar movimiento
    $stmtMovimiento = $pdo->prepare("
        INSERT INTO movimientos_caja_detallados (
            turno_id,
            tipo,
            categoria,
            monto,
            descripcion,
            referencia,
            usuario_id,
            ip_registro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmtMovimiento->execute([
        $turnoId,
        $tipo,
        $categoria,
        $montoFinal,
        $descripcion,
        $referencia,
        $usuarioId,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    // Actualizar totales del turno (efectivo_teorico se calcula automáticamente)
    $stmtActualizar = $pdo->prepare("
        UPDATE turnos_caja SET
            total_entradas = (
                SELECT COALESCE(SUM(monto), 0) 
                FROM movimientos_caja_detallados 
                WHERE turno_id = ? AND tipo = 'ingreso'
            ),
            total_salidas = (
                SELECT COALESCE(SUM(ABS(monto)), 0) 
                FROM movimientos_caja_detallados 
                WHERE turno_id = ? AND tipo = 'egreso'
            ),
            cantidad_movimientos = (
                SELECT COUNT(*) 
                FROM movimientos_caja_detallados 
                WHERE turno_id = ? AND tipo IN ('ingreso', 'egreso')
            )
        WHERE id = ?
    ");
    $stmtActualizar->execute([$turnoId, $turnoId, $turnoId, $turnoId]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Movimiento registrado exitosamente',
        'movimiento_id' => $pdo->lastInsertId(),
        'tipo' => $tipo,
        'monto' => $montoFinal,
        'descripcion' => $descripcion
    ]);
}

/**
 * 🔒 Cerrar caja del turno activo
 */
function cerrarCaja($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $usuarioId = intval($input['usuario_id'] ?? 1);
    $montoCierre = $input['monto_cierre'] ?? 0; // MANTENER EXACTITUD DECIMAL - Sin floatval() que causa redondeo
    $notas = trim($input['notas'] ?? '');
    
    // Validar que el monto sea numérico sin alterar la precisión
    if (!is_numeric($montoCierre) || $montoCierre < 0) {
        throw new Exception('El monto de cierre debe ser un número válido mayor o igual a 0');
    }
    
    // Obtener turno activo
    $stmtTurno = $pdo->prepare("
        SELECT * FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
        LIMIT 1
    ");
    $stmtTurno->execute([$usuarioId]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
    
    if (!$turno) {
        throw new Exception('No hay turno activo para cerrar');
    }
    
    // Calcular diferencia
    $diferencia = $montoCierre - $turno['efectivo_teorico'];
    
    // Cerrar turno
    $stmtCerrar = $pdo->prepare("
        UPDATE turnos_caja SET
            fecha_cierre = NOW(),
            monto_cierre = ?,
            diferencia = ?,
            estado = 'cerrado',
            notas = CONCAT(COALESCE(notas, ''), ?)
        WHERE id = ?
    ");
    
    $notasCierre = "\n[CIERRE] " . date('Y-m-d H:i:s') . ": " . $notas;
    $stmtCerrar->execute([$montoCierre, $diferencia, $notasCierre, $turno['id']]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Caja cerrada exitosamente',
        'turno_id' => $turno['id'],
        'monto_teorico' => $turno['efectivo_teorico'],
        'monto_real' => $montoCierre,
        'diferencia' => $diferencia,
        'fecha_cierre' => date('Y-m-d H:i:s')
    ]);
}

/**
 * 🚨 Cerrar turno de emergencia (sin validaciones estrictas)
 */
function cerrarTurnoEmergencia($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $usuarioId = intval($input['usuario_id'] ?? 1);
    
    // Obtener turno activo
    $stmtTurno = $pdo->prepare("
        SELECT * FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
        LIMIT 1
    ");
    $stmtTurno->execute([$usuarioId]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
    
    if (!$turno) {
        throw new Exception('No hay turno activo para cerrar');
    }
    
    // Cerrar turno de emergencia (sin monto de cierre)
    $stmtCerrar = $pdo->prepare("
        UPDATE turnos_caja SET
            fecha_cierre = NOW(),
            monto_cierre = efectivo_teorico,
            diferencia = 0,
            estado = 'cerrado',
            notas = CONCAT(COALESCE(notas, ''), ?)
        WHERE id = ?
    ");
    
    $notasCierre = "\n[CIERRE EMERGENCIA] " . date('Y-m-d H:i:s') . ": Cerrado por administrador";
    $stmtCerrar->execute([$notasCierre, $turno['id']]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Turno cerrado de emergencia exitosamente',
        'turno_id' => $turno['id'],
        'fecha_cierre' => date('Y-m-d H:i:s'),
        'tipo_cierre' => 'emergencia'
    ]);
}

/**
 * 📋 Obtener historial de turnos para trazabilidad
 */
function obtenerHistorialTurnos($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? null;
    $limite = intval($_GET['limite'] ?? 10);
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    
    $whereClause = "WHERE DATE(fecha_apertura) BETWEEN ? AND ?";
    $params = [$fechaDesde, $fechaHasta];
    
    if ($usuarioId) {
        $whereClause .= " AND usuario_id = ?";
        $params[] = $usuarioId;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            usuario_id,
            cajero_nombre,
            fecha_apertura,
            fecha_cierre,
            monto_apertura,
            monto_cierre,
            efectivo_teorico,
            diferencia,
            ventas_efectivo + ventas_transferencia + ventas_tarjeta + ventas_qr as total_ventas,
            cantidad_ventas,
            cantidad_movimientos,
            estado,
            horas_turno,
            CASE 
                WHEN diferencia IS NULL THEN 'Sin cerrar'
                WHEN ABS(diferencia) <= 10 THEN 'Exacto'
                WHEN diferencia > 10 THEN 'Sobrante'
                ELSE 'Faltante'
            END as tipo_diferencia,
            notas
        FROM vista_resumen_turnos 
        {$whereClause}
        ORDER BY fecha_apertura DESC 
        LIMIT ?
    ");
    
    $params[] = $limite;
    $stmt->execute($params);
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para mejor legibilidad
    foreach ($turnos as &$turno) {
        $turno['fecha_apertura_formateada'] = date('d/m/Y H:i', strtotime($turno['fecha_apertura']));
        $turno['fecha_cierre_formateada'] = $turno['fecha_cierre'] ? date('d/m/Y H:i', strtotime($turno['fecha_cierre'])) : null;
        $turno['monto_apertura'] = floatval($turno['monto_apertura']);
        $turno['monto_cierre'] = $turno['monto_cierre'] ? floatval($turno['monto_cierre']) : null;
        $turno['efectivo_teorico'] = floatval($turno['efectivo_teorico']);
        $turno['diferencia'] = $turno['diferencia'] ? floatval($turno['diferencia']) : null;
        $turno['total_ventas'] = floatval($turno['total_ventas']);
    }
    
    echo json_encode([
        'success' => true,
        'turnos' => $turnos,
        'total_turnos' => count($turnos),
        'periodo' => [
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta
        ]
    ]);
}

/**
 * ✅ Validar que no exista turno activo para evitar duplicados
 */
function validarTurnoUnico($pdo) {
    $usuarioId = $_GET['usuario_id'] ?? 1;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, 
               GROUP_CONCAT(id) as turnos_activos
        FROM turnos_caja 
        WHERE usuario_id = ? AND estado = 'abierto'
    ");
    $stmt->execute([$usuarioId]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $turnoUnico = intval($resultado['count']) === 0;
    
    echo json_encode([
        'success' => true,
        'turno_unico' => $turnoUnico,
        'turnos_activos' => $resultado['turnos_activos'],
        'mensaje' => $turnoUnico 
            ? 'No hay turnos activos. Puede abrir caja.' 
            : 'Ya existe un turno activo. Debe cerrar antes de abrir otro.'
    ]);
}

/**
 * 📊 Obtener resumen de movimientos de un turno específico
 */
function obtenerResumenMovimientosTurno($pdo) {
    $numeroTurno = $_GET['numero_turno'] ?? null;
    
    if (!$numeroTurno) {
        throw new Exception('Número de turno requerido');
    }
    
    try {
        // Obtener información básica del turno
        $stmtTurno = $pdo->prepare("
            SELECT t.*, u.nombre as cajero_nombre
            FROM turnos_caja t
            LEFT JOIN usuarios u ON t.usuario_id = u.id
            WHERE t.id = ?
        ");
        $stmtTurno->execute([$numeroTurno]);
        $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
        
        if (!$turno) {
            throw new Exception('Turno no encontrado');
        }
        
        // Obtener resumen de movimientos manuales (ingresos y egresos)
        $stmtMovimientos = $pdo->prepare("
            SELECT 
                tipo,
                categoria,
                COUNT(*) as cantidad,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE ABS(monto) END) as total
            FROM movimientos_caja_detallados
            WHERE turno_id = ? AND tipo IN ('ingreso', 'egreso')
            GROUP BY tipo, categoria
            ORDER BY tipo DESC, categoria
        ");
        $stmtMovimientos->execute([$numeroTurno]);
        $movimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);

        // Obtener movimientos detallados individuales (para mostrar cada concepto)
        $stmtDetalle = $pdo->prepare("
            SELECT 
                tipo,
                categoria,
                monto,
                descripcion,
                referencia,
                fecha_movimiento,
                DATE_FORMAT(fecha_movimiento, '%d/%m %H:%i') as fecha_formateada
            FROM movimientos_caja_detallados
            WHERE turno_id = ? AND tipo IN ('ingreso', 'egreso')
            ORDER BY fecha_movimiento DESC, tipo DESC
        ");
        $stmtDetalle->execute([$numeroTurno]);
        $movimientosDetallados = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular totales
        $totalIngresos = 0;
        $totalEgresos = 0;
        $cantidadIngresos = 0;
        $cantidadEgresos = 0;
        
        foreach ($movimientos as $mov) {
            if ($mov['tipo'] === 'ingreso') {
                $totalIngresos += $mov['total'];
                $cantidadIngresos += $mov['cantidad'];
            } else {
                $totalEgresos += $mov['total'];
                $cantidadEgresos += $mov['cantidad'];
            }
        }
        
        // Obtener ventas en efectivo del turno
        $ventasEfectivo = floatval($turno['ventas_efectivo'] ?? 0);
        $cantidadVentasEfectivo = 0;
        
        // Contar cantidad de ventas en efectivo
        $stmtContarVentas = $pdo->prepare("
            SELECT COUNT(*) as cantidad
            FROM ventas
            WHERE DATE(fecha) = DATE(?) AND metodo_pago = 'efectivo'
        ");
        $stmtContarVentas->execute([$turno['fecha_apertura']]);
        $resultadoVentas = $stmtContarVentas->fetch(PDO::FETCH_ASSOC);
        $cantidadVentasEfectivo = intval($resultadoVentas['cantidad'] ?? 0);
        
        // Separar movimientos por tipo para mejor visualización
        $ingresosDetallados = array_filter($movimientosDetallados, function($mov) {
            return $mov['tipo'] === 'ingreso';
        });
        
        $egresosDetallados = array_filter($movimientosDetallados, function($mov) {
            return $mov['tipo'] === 'egreso';
        });

        // Preparar respuesta
        $resumen = [
            'turno' => $turno,
            'movimientos_detallados' => $movimientos, // Resumen por categoría
            'ingresos_detallados' => array_values($ingresosDetallados), // Lista completa de ingresos
            'egresos_detallados' => array_values($egresosDetallados), // Lista completa de egresos
            'totales' => [
                'ingresos_manuales' => $totalIngresos,
                'egresos_totales' => $totalEgresos,
                'ventas_efectivo' => $ventasEfectivo,
                'cantidad_ingresos' => $cantidadIngresos,
                'cantidad_egresos' => $cantidadEgresos,
                'cantidad_ventas_efectivo' => $cantidadVentasEfectivo
            ],
            'flujo_neto' => ($turno['monto_apertura'] + $totalIngresos + $ventasEfectivo) - $totalEgresos
        ];
        
        echo json_encode([
            'success' => true,
            'resumen' => $resumen,
            'mensaje' => 'Resumen de movimientos obtenido exitosamente'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// 🚀 NUEVA FUNCIÓN OPTIMIZADA: Estado completo en una sola llamada
function obtenerEstadoCompleto($pdo) {
    try {
        $usuarioId = $_GET['usuario_id'] ?? 1;
        
        // 📊 PASO 1: Verificar si hay turno activo
        $stmt = $pdo->prepare("
            SELECT tc.*, u.nombre as cajero_nombre 
            FROM turnos_caja tc 
            LEFT JOIN usuarios u ON tc.usuario_id = u.id 
            WHERE tc.usuario_id = ? AND tc.fecha_cierre IS NULL 
            ORDER BY tc.fecha_apertura DESC 
            LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cajaAbierta = !empty($turno);
        
        if (!$cajaAbierta) {
            echo json_encode([
                'success' => true,
                'caja_abierta' => false,
                'turno' => null,
                'movimientos' => [],
                'ventas_por_metodo' => [],
                'estadisticas' => []
            ]);
            return;
        }
        
        // 📊 PASO 2: Obtener movimientos del turno activo
        $stmt = $pdo->prepare("
            SELECT 
                mcd.*,
                u.nombre as usuario_nombre,
                DATE_FORMAT(mcd.fecha_movimiento, '%d/%m/%Y %H:%i') as fecha_formateada
            FROM movimientos_caja_detallados mcd
            LEFT JOIN usuarios u ON mcd.usuario_id = u.id
            WHERE mcd.turno_id = ?
            ORDER BY mcd.fecha_movimiento DESC
            LIMIT 50
        ");
        $stmt->execute([$turno['id']]);
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 📊 PASO 3: Calcular ventas por método de pago desde el turno
        $ventasPorMetodo = [
            'efectivo' => floatval($turno['ventas_efectivo'] ?? 0),
            'transferencia' => floatval($turno['ventas_transferencia'] ?? 0),
            'tarjeta' => floatval($turno['ventas_tarjeta'] ?? 0),
            'qr' => floatval($turno['ventas_qr'] ?? 0)
        ];
        
        // 📊 PASO 4: Calcular estadísticas adicionales
        $totalIngresos = 0;
        $totalEgresos = 0;
        $cantidadMovimientos = 0;
        
        foreach ($movimientos as $mov) {
            $monto = floatval($mov['monto']);
            if ($mov['tipo'] === 'ingreso') {
                $totalIngresos += $monto;
            } elseif ($mov['tipo'] === 'egreso') {
                $totalEgresos += abs($monto);
            }
            $cantidadMovimientos++;
        }
        
        $estadisticas = [
            'total_ingresos_manuales' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'cantidad_movimientos' => $cantidadMovimientos,
            'total_ventas' => array_sum($ventasPorMetodo),
            'efectivo_esperado' => floatval($turno['monto_inicial']) + $ventasPorMetodo['efectivo'] + $totalIngresos - $totalEgresos
        ];
        
        // 🎯 RESPUESTA UNIFICADA
        echo json_encode([
            'success' => true,
            'caja_abierta' => true,
            'turno' => $turno,
            'movimientos' => $movimientos,
            'ventas_por_metodo' => $ventasPorMetodo,
            'estadisticas' => $estadisticas,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log("Error en obtenerEstadoCompleto: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error obteniendo estado completo: ' . $e->getMessage()
        ]);
    }
}
?>
