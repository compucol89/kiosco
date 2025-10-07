<?php
/**
 * 🎯 SISTEMA DE REPORTES FINANCIEROS CORREGIDOS - SPACEX GRADE
 * Implementa la fórmula CORRECTA según especificación del usuario:
 * GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO PRODUCTO
 * 
 * Incluye: GASTOS DIARIOS - GANANCIA NETA
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
require_once 'financial_calculator_corrected.php';

try {
    $pdo = Conexion::obtenerConexion();
    
    // Parámetros de consulta
    $periodo = $_GET['periodo'] ?? 'hoy';
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $gastosDiarios = floatval($_GET['gastos_diarios'] ?? 0); // Nuevo parámetro
    
    // Mapear período
    switch ($periodo) {
        case 'hoy':
            $fechaHoy = date('Y-m-d');
            $fechaInicio = $fechaHoy;
            $fechaFin = $fechaHoy;
            break;
        case 'ayer':
            $fechaAyer = date('Y-m-d', strtotime('-1 day'));
            $fechaInicio = $fechaAyer;
            $fechaFin = $fechaAyer;
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
    
    // =============================================================================
    // 1. OBTENER VENTAS DEL PERÍODO
    // =============================================================================
    
    $stmtVentas = $pdo->prepare("
        SELECT 
            v.id,
            v.fecha,
            v.cliente_nombre,
            v.metodo_pago,
            v.subtotal,
            v.descuento,
            v.monto_total,
            v.numero_comprobante,
            v.detalles_json,
            v.estado
        FROM ventas v
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        AND v.estado IN ('completado', 'completada')
        ORDER BY v.fecha DESC
    ");
    $stmtVentas->execute([$fechaInicio, $fechaFin]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    
    // =============================================================================
    // 2. OBTENER PRODUCTOS PARA LOOKUP
    // =============================================================================
    
    $stmtProductos = $pdo->prepare("
        SELECT 
            id,
            nombre,
            categoria,
            precio_costo,
            precio_venta,
            codigo
        FROM productos
    ");
    $stmtProductos->execute();
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
    
    $productosLookup = [];
    foreach ($productos as $producto) {
        $productosLookup[$producto['id']] = $producto;
    }
    
    // =============================================================================
    // 3. PROCESAR VENTAS CON CALCULADORA CORREGIDA
    // =============================================================================
    
    $calculadora = new CalculadoraFinancieraCorregida($productosLookup);
    $ventasProcesadas = [];
    
    foreach ($ventas as $venta) {
        $ventaProcesada = $calculadora->procesarVentaCorregida($venta);
        $ventasProcesadas[] = $ventaProcesada;
    }
    
    // =============================================================================
    // 4. CALCULAR RESUMEN GENERAL CON GANANCIAS NETAS CORRECTAS
    // =============================================================================
    
    $resumenGeneral = $calculadora->calcularResumenGeneral($ventasProcesadas);
    
    // =============================================================================
    // 5. CALCULAR GASTOS DIARIOS Y RESULTADO FINAL
    // =============================================================================
    
    $diasPeriodo = (strtotime($fechaFin) - strtotime($fechaInicio)) / (24*60*60) + 1;
    $gastosTotalPeriodo = $gastosDiarios * $diasPeriodo;
    
    // 🎯 CÁLCULO SOLICITADO POR EL USUARIO: GASTOS DIARIOS - GANANCIA NETA
    $resultadoOperacional = $resumenGeneral['total_ganancia_neta'] - $gastosTotalPeriodo;
    
    // =============================================================================
    // 6. MÉTODOS DE PAGO
    // =============================================================================
    
    $metodosPago = [
        'efectivo' => 0,
        'tarjeta' => 0,
        'transferencia' => 0,
        'mercadopago' => 0,
        'otros' => 0
    ];
    
    foreach ($ventas as $venta) {
        $metodo = strtolower($venta['metodo_pago']);
        if (isset($metodosPago[$metodo])) {
            $metodosPago[$metodo] += $venta['monto_total'];
        } else {
            $metodosPago['otros'] += $venta['monto_total'];
        }
    }
    
    // =============================================================================
    // 7. ANÁLISIS DE PRODUCTOS
    // =============================================================================
    
    $productosAnalisis = [];
    foreach ($ventasProcesadas as $venta) {
        foreach ($venta['productos'] as $producto) {
            $id = $producto['producto_id'];
            if (!isset($productosAnalisis[$id])) {
                $productosAnalisis[$id] = [
                    'producto_id' => $id,
                    'nombre' => $producto['nombre'],
                    'categoria' => $producto['categoria'],
                    'cantidad_vendida' => 0,
                    'ingresos_brutos' => 0,
                    'descuentos_totales' => 0,
                    'ingresos_netos' => 0,
                    'costos_totales' => 0,
                    'ganancia_neta_total' => 0,
                    'ventas_count' => 0
                ];
            }
            
            $productosAnalisis[$id]['cantidad_vendida'] += $producto['cantidad'];
            $productosAnalisis[$id]['ingresos_brutos'] += $producto['ingreso_bruto_total'];
            $productosAnalisis[$id]['descuentos_totales'] += $producto['descuento_total'];
            $productosAnalisis[$id]['ingresos_netos'] += $producto['ingreso_neto_total'];
            $productosAnalisis[$id]['costos_totales'] += $producto['costo_total'];
            $productosAnalisis[$id]['ganancia_neta_total'] += $producto['ganancia_neta_total'];
            $productosAnalisis[$id]['ventas_count']++;
        }
    }
    
    // Calcular métricas por producto
    foreach ($productosAnalisis as &$producto) {
        $producto['margen_promedio'] = $producto['ingresos_netos'] > 0 ? 
            ($producto['ganancia_neta_total'] / $producto['ingresos_netos']) * 100 : 0;
        $producto['precio_promedio'] = $producto['cantidad_vendida'] > 0 ? 
            $producto['ingresos_netos'] / $producto['cantidad_vendida'] : 0;
        $producto['costo_promedio'] = $producto['cantidad_vendida'] > 0 ? 
            $producto['costos_totales'] / $producto['cantidad_vendida'] : 0;
        $producto['ganancia_unitaria_promedio'] = $producto['cantidad_vendida'] > 0 ? 
            $producto['ganancia_neta_total'] / $producto['cantidad_vendida'] : 0;
    }
    
    // Ordenar por ganancia neta total
    usort($productosAnalisis, function($a, $b) {
        return $b['ganancia_neta_total'] <=> $a['ganancia_neta_total'];
    });
    
    // =============================================================================
    // 8. RESPUESTA FINAL CON FÓRMULAS CORREGIDAS
    // =============================================================================
    
    $response = [
        'success' => true,
        'formula_aplicada' => 'GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO PRODUCTO',
        'calculado_por_usuario' => 'GASTOS DIARIOS - GANANCIA NETA',
        
        'periodo' => [
            'tipo' => $periodo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'dias_periodo' => $diasPeriodo
        ],
        
        'resumen_financiero' => [
            // Ingresos
            'total_ventas' => $resumenGeneral['total_ventas'],
            'total_ingresos_brutos' => round($resumenGeneral['total_ingresos_brutos'], 2),
            'total_descuentos' => round($resumenGeneral['total_descuentos'], 2),
            'total_ingresos_netos' => round($resumenGeneral['total_ingresos_netos'], 2),
            
            // Costos y ganancias
            'total_costos' => round($resumenGeneral['total_costos'], 2),
            'total_ganancia_neta' => round($resumenGeneral['total_ganancia_neta'], 2),
            
            // Gastos operacionales
            'gastos_diarios_configurados' => round($gastosDiarios, 2),
            'gastos_total_periodo' => round($gastosTotalPeriodo, 2),
            
            // 🎯 RESULTADO SOLICITADO POR EL USUARIO
            'resultado_operacional' => round($resultadoOperacional, 2),
            'formula_resultado' => "Ganancia Neta ($" . number_format($resumenGeneral['total_ganancia_neta'], 2) . ") - Gastos Período ($" . number_format($gastosTotalPeriodo, 2) . ") = $" . number_format($resultadoOperacional, 2),
            
            // Métricas
            'margen_ganancia_porcentaje' => round($resumenGeneral['margen_bruto_porcentaje'], 2),
            'roi_porcentaje' => round($resumenGeneral['roi_porcentaje'], 2),
            'ticket_promedio' => round($resumenGeneral['ticket_promedio'], 2),
            'ganancia_por_venta' => round($resumenGeneral['ganancia_por_venta'], 2),
            
            // Estados
            'estado_ganancias' => $resumenGeneral['total_ganancia_neta'] > 0 ? 'RENTABLE' : 'PERDIDAS',
            'estado_operacional' => $resultadoOperacional > 0 ? 'UTILIDAD' : 'PERDIDA_OPERACIONAL',
            'coherencia_general' => $resumenGeneral['diferencias_detectadas'] == 0
        ],
        
        'metodos_pago' => $metodosPago,
        'productos_analisis' => array_slice($productosAnalisis, 0, 20),
        'ventas_detalladas' => $ventasProcesadas,
        
        'validaciones' => [
            'total_ventas_procesadas' => count($ventasProcesadas),
            'diferencias_detectadas' => $resumenGeneral['diferencias_detectadas'],
            'formula_ganancia_neta' => 'GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO PRODUCTO',
            'formula_resultado_operacional' => 'RESULTADO = GANANCIA NETA - GASTOS DIARIOS',
            'precision_calculo' => 'Centavos (2 decimales)',
            'metodo_descuento' => 'Proporcional por ítem',
            'verificacion_matematica' => 'Paso a paso documentado'
        ],
        
        'debug' => $calculadora->getDebug(),
        
        'configuracion_gastos' => [
            'gastos_diarios' => $gastosDiarios,
            'como_configurar' => 'Agregar parámetro gastos_diarios=VALOR a la URL',
            'ejemplo' => "?periodo=hoy&gastos_diarios=50000",
            'nota' => 'Si no se especifica, gastos_diarios = 0'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'archivo' => __FILE__
    ], JSON_PRETTY_PRINT);
}
?>
