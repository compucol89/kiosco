<?php
/**
 * 💰 API GASTOS MENSUALES - MÓDULO FINANCIERO COMPLETO
 * Gestiona los gastos fijos mensuales para cálculos automáticos
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['accion'] ?? '';
    
    switch ($metodo) {
        
        case 'GET':
            if ($accion === 'obtener') {
                obtenerGastosMensuales($pdo);
            } elseif ($accion === 'calcular_diarios') {
                calcularGastosDiarios($pdo);
            } else {
                throw new Exception('Acción GET no válida');
            }
            break;
            
        case 'POST':
            if ($accion === 'configurar') {
                configurarGastosMensuales($pdo);
            } else {
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
 * 📊 Obtener gastos mensuales actuales
 */
function obtenerGastosMensuales($pdo) {
    $mesActual = date('Y-m');
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            mes_ano,
            gastos_totales,
            descripcion,
            usuario_id,
            created_at,
            updated_at
        FROM gastos_mensuales 
        WHERE mes_ano = ? AND activo = 1
        LIMIT 1
    ");
    $stmt->execute([$mesActual]);
    $gastos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gastos) {
        // Crear gastos por defecto si no existen
        $stmtInsert = $pdo->prepare("
            INSERT INTO gastos_mensuales (mes_ano, gastos_totales, descripcion) 
            VALUES (?, 0, 'Gastos mensuales - Configurar en módulo de finanzas')
        ");
        $stmtInsert->execute([$mesActual]);
        
        $gastos = [
            'id' => $pdo->lastInsertId(),
            'mes_ano' => $mesActual,
            'gastos_totales' => 0,
            'descripcion' => 'Gastos mensuales - Configurar en módulo de finanzas',
            'usuario_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Calcular gastos diarios
    $diasMes = date('t', strtotime($mesActual . '-01'));
    $gastosDiarios = $gastos['gastos_totales'] / $diasMes;
    
    echo json_encode([
        'success' => true,
        'gastos' => $gastos,
        'calculados' => [
            'mes_actual' => $mesActual,
            'dias_mes' => $diasMes,
            'gastos_mensuales' => (float)$gastos['gastos_totales'],
            'gastos_diarios' => round($gastosDiarios, 2),
            'fecha_calculo' => date('Y-m-d H:i:s')
        ]
    ]);
}

/**
 * 🧮 Calcular gastos diarios para período específico
 */
function calcularGastosDiarios($pdo) {
    $periodo = $_GET['periodo'] ?? 'hoy';
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    
    // Mapear período
    switch ($periodo) {
        case 'hoy':
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
            break;
        case 'ayer':
            $fechaInicio = date('Y-m-d', strtotime('-1 day'));
            $fechaFin = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'semana':
            $fechaInicio = date('Y-m-d', strtotime('monday this week'));
            $fechaFin = date('Y-m-d');
            break;
        case 'mes':
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-d');
            break;
    }
    
    // Obtener gastos del mes correspondiente
    $mesCalculo = date('Y-m', strtotime($fechaInicio));
    
    $stmt = $pdo->prepare("
        SELECT gastos_totales 
        FROM gastos_mensuales 
        WHERE mes_ano = ? AND activo = 1
        LIMIT 1
    ");
    $stmt->execute([$mesCalculo]);
    $gastosMensuales = $stmt->fetchColumn() ?: 0;
    
    // Calcular días del período
    $diasPeriodo = (strtotime($fechaFin) - strtotime($fechaInicio)) / (24*60*60) + 1;
    $diasMes = date('t', strtotime($mesCalculo . '-01'));
    $gastosDiarios = $gastosMensuales / $diasMes;
    $gastosPeriodo = $gastosDiarios * $diasPeriodo;
    
    echo json_encode([
        'success' => true,
        'calculo' => [
            'periodo' => $periodo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'dias_periodo' => $diasPeriodo,
            'mes_calculo' => $mesCalculo,
            'gastos_mensuales' => (float)$gastosMensuales,
            'dias_mes' => $diasMes,
            'gastos_diarios' => round($gastosDiarios, 2),
            'gastos_periodo' => round($gastosPeriodo, 2),
            'formula' => "Mensuales ($" . number_format($gastosMensuales, 2) . ") ÷ $diasMes días × $diasPeriodo días = $" . number_format($gastosPeriodo, 2)
        ]
    ]);
}

/**
 * ⚙️ Configurar gastos mensuales
 */
function configurarGastosMensuales($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $gastosTotales = floatval($input['gastos_totales'] ?? 0);
    $descripcion = trim($input['descripcion'] ?? '');
    $mesAno = $input['mes_ano'] ?? date('Y-m');
    $usuarioId = intval($input['usuario_id'] ?? 1);
    
    if ($gastosTotales < 0) {
        throw new Exception('Los gastos no pueden ser negativos');
    }
    
    // Desactivar gastos anteriores del mismo mes
    $stmtDesactivar = $pdo->prepare("
        UPDATE gastos_mensuales 
        SET activo = 0, updated_at = CURRENT_TIMESTAMP 
        WHERE mes_ano = ? AND activo = 1
    ");
    $stmtDesactivar->execute([$mesAno]);
    
    // Insertar nuevos gastos
    $stmtInsertar = $pdo->prepare("
        INSERT INTO gastos_mensuales (
            mes_ano, 
            gastos_totales, 
            descripcion, 
            usuario_id, 
            activo
        ) VALUES (?, ?, ?, ?, 1)
    ");
    $stmtInsertar->execute([$mesAno, $gastosTotales, $descripcion, $usuarioId]);
    
    $gastoId = $pdo->lastInsertId();
    
    // Calcular gastos diarios
    $diasMes = date('t', strtotime($mesAno . '-01'));
    $gastosDiarios = $gastosTotales / $diasMes;
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Gastos mensuales configurados exitosamente',
        'gastos' => [
            'id' => $gastoId,
            'mes_ano' => $mesAno,
            'gastos_totales' => $gastosTotales,
            'descripcion' => $descripcion,
            'usuario_id' => $usuarioId,
            'dias_mes' => $diasMes,
            'gastos_diarios' => round($gastosDiarios, 2)
        ]
    ]);
}
?>
