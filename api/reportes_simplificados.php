<?php
/**
 * REPORTES FINANCIEROS SIMPLIFICADOS
 * Integra sistema de gastos fijos simplificado con variable global única
 * Elimina sistema complejo de categorización
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';
require_once 'gastos_fijos_simplificado.php';

try {
    $pdo = Conexion::obtenerConexion();
    $gastos_fijos = new GastosFijosSimplificado($pdo);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'obtener_reporte_financiero') {
            
            // Parámetros de período
            $periodo = $_GET['periodo'] ?? 'mes-actual';
            
            // Calcular fechas
            switch ($periodo) {
                case 'hoy':
                    $fechaInicio = date('Y-m-d');
                    $fechaFin = date('Y-m-d');
                    break;
                case 'mes-actual':
                    $fechaInicio = date('Y-m-01');
                    $fechaFin = date('Y-m-t');
                    break;
                case 'mes-pasado':
                    $fechaInicio = date('Y-m-01', strtotime('first day of last month'));
                    $fechaFin = date('Y-m-t', strtotime('last day of last month'));
                    break;
                default:
                    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
                    break;
            }
            
            // ========================================
            // OBTENER VENTAS (CRITERIO UNIFICADO)
            // ========================================
            
            $stmtVentas = $pdo->prepare("
                SELECT * FROM ventas 
                WHERE DATE(fecha) BETWEEN ? AND ? 
                AND estado = 'completado'
                ORDER BY fecha DESC
            ");
            $stmtVentas->execute([$fechaInicio, $fechaFin]);
            $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
            
            // ========================================
            // PROCESAR VENTAS POR DÍA
            // ========================================
            
            $ventasPorDia = [];
            $totalIngresos = 0;
            $totalVentas = count($ventas);
            
            foreach ($ventas as $venta) {
                $fecha = date('Y-m-d', strtotime($venta['fecha']));
                $monto = floatval($venta['monto_total']);
                $metodo = strtolower($venta['metodo_pago']);
                
                if (!isset($ventasPorDia[$fecha])) {
                    $ventasPorDia[$fecha] = [
                        'ingresos_total' => 0,
                        'ventas_efectivo' => 0,
                        'ventas_digitales' => 0,
                        'numero_ventas' => 0,
                        'ventas' => []
                    ];
                }
                
                $ventasPorDia[$fecha]['ingresos_total'] += $monto;
                $ventasPorDia[$fecha]['numero_ventas']++;
                $ventasPorDia[$fecha]['ventas'][] = $venta;
                $totalIngresos += $monto;
                
                if ($metodo === 'efectivo') {
                    $ventasPorDia[$fecha]['ventas_efectivo'] += $monto;
                } else {
                    $ventasPorDia[$fecha]['ventas_digitales'] += $monto;
                }
            }
            
            // ========================================
            // INTEGRAR GASTOS FIJOS SIMPLIFICADOS
            // ========================================
            
            $reporteConGastosFijos = $gastos_fijos->integrarEnReporteFinanciero(
                $ventasPorDia, 
                $fechaInicio, 
                $fechaFin
            );
            
            // ========================================
            // OBTENER GASTOS OPERACIONALES (NO FIJOS)
            // ========================================
            
            $stmtGastosOp = $pdo->prepare("
                SELECT 
                    DATE(fecha) as fecha,
                    SUM(monto) as total_gastosop
                FROM egresos 
                WHERE categoria NOT IN ('gastos_fijos', 'sueldos', 'impuestos')
                AND DATE(fecha) BETWEEN ? AND ?
                GROUP BY DATE(fecha)
            ");
            $stmtGastosOp->execute([$fechaInicio, $fechaFin]);
            $gastosOperacionales = $stmtGastosOp->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // ========================================
            // CALCULAR TOTALES Y MÉTRICAS
            // ========================================
            
            $distribucion_actual = $gastos_fijos->calcularDistribucionDiaria();
            $gastos_fijos_totales = 0;
            $gastos_operacionales_totales = 0;
            $utilidad_neta_total = 0;
            
            // Integrar gastos operacionales en el reporte
            foreach ($reporteConGastosFijos as $fecha => &$datos) {
                $gastos_op_dia = floatval($gastosOperacionales[$fecha] ?? 0);
                $datos['gastos_operacionales'] = $gastos_op_dia;
                $datos['total_egresos_dia'] = $datos['gastos_fijos_diarios'] + $gastos_op_dia;
                $datos['utilidad_final'] = $datos['utilidad_neta'] - $gastos_op_dia;
                
                $gastos_fijos_totales += $datos['gastos_fijos_diarios'];
                $gastos_operacionales_totales += $gastos_op_dia;
                $utilidad_neta_total += $datos['utilidad_final'];
            }
            
            // ========================================
            // RESUMEN FINANCIERO
            // ========================================
            
            $resumen = [
                'periodo' => [
                    'tipo' => $periodo,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'dias_total' => count($reporteConGastosFijos)
                ],
                'ingresos' => [
                    'total_ingresos' => round($totalIngresos, 2),
                    'numero_ventas' => $totalVentas,
                    'promedio_venta' => $totalVentas > 0 ? round($totalIngresos / $totalVentas, 2) : 0,
                    'ingresos_diario_promedio' => count($reporteConGastosFijos) > 0 ? round($totalIngresos / count($reporteConGastosFijos), 2) : 0
                ],
                'gastos' => [
                    'gastos_fijos_diarios_promedio' => $distribucion_actual['gasto_fijo_diario'],
                    'gastos_fijos_totales' => round($gastos_fijos_totales, 2),
                    'gastos_operacionales_totales' => round($gastos_operacionales_totales, 2),
                    'total_egresos' => round($gastos_fijos_totales + $gastos_operacionales_totales, 2)
                ],
                'rentabilidad' => [
                    'utilidad_neta_total' => round($utilidad_neta_total, 2),
                    'margen_neto' => $totalIngresos > 0 ? round(($utilidad_neta_total / $totalIngresos) * 100, 2) : 0,
                    'utilidad_diaria_promedio' => count($reporteConGastosFijos) > 0 ? round($utilidad_neta_total / count($reporteConGastosFijos), 2) : 0
                ],
                'configuracion_gastos_fijos' => $distribucion_actual
            ];
            
            // ========================================
            // MÉTRICAS POR MÉTODO DE PAGO
            // ========================================
            
            $metodosPago = [
                'efectivo' => 0,
                'tarjeta' => 0,
                'transferencia' => 0,
                'otros' => 0
            ];
            
            foreach ($ventas as $venta) {
                $metodo = strtolower($venta['metodo_pago']);
                $monto = floatval($venta['monto_total']);
                
                if (isset($metodosPago[$metodo])) {
                    $metodosPago[$metodo] += $monto;
                } else {
                    $metodosPago['otros'] += $monto;
                }
            }
            
            $resumen['metodos_pago'] = $metodosPago;
            
            // ========================================
            // RESPUESTA FINAL
            // ========================================
            
            $response = [
                'success' => true,
                'reporte_diario' => $reporteConGastosFijos,
                'resumen' => $resumen,
                'mensaje' => 'Reporte generado con sistema simplificado de gastos fijos'
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            
        } else {
            throw new Exception('Acción no válida');
        }
        
    } else {
        throw new Exception('Método no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?> 