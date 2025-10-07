<?php
/**
 * 🚨 FINANZAS COMPLETO CORREGIDO - URGENTE
 * API corregida para mostrar detalle de ventas individuales correcto
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

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $periodo = $_GET['periodo'] ?? 'hoy';
    
    // Calcular fechas según período
    switch ($periodo) {
        case 'hoy':
        default:
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
    
    // 1. OBTENER VENTAS REALES CON DATOS CORRECTOS
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.fecha,
            v.cliente_nombre,
            v.metodo_pago,
            v.subtotal,
            v.descuento,
            v.monto_total,
            v.detalles_json
        FROM ventas v
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        AND v.estado IN ('completado', 'completada')
        ORDER BY v.fecha DESC
    ");
    $stmt->execute([$fechaInicio, $fechaFin]);
    $ventas_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. PROCESAR CADA VENTA PARA EL DETALLE
    $ventas_detalle = [];
    $totales = [
        'total_ventas' => 0,
        'total_costos' => 0,
        'total_descuentos' => 0,
        'total_ganancias' => 0
    ];
    
    foreach ($ventas_bd as $venta) {
        // Calcular costo desde productos
        $costo_total = 0;
        
        if (!empty($venta['detalles_json'])) {
            $detalles = json_decode($venta['detalles_json'], true);
            if (isset($detalles['cart']) && is_array($detalles['cart'])) {
                foreach ($detalles['cart'] as $item) {
                    $stmt_producto = $pdo->prepare("SELECT precio_costo FROM productos WHERE id = ? LIMIT 1");
                    $stmt_producto->execute([$item['id'] ?? 0]);
                    $producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);
                    
                    $costo_unitario = floatval($producto['precio_costo'] ?? 50);
                    $cantidad = floatval($item['cantidad'] ?? $item['quantity'] ?? 1);
                    $costo_total += $costo_unitario * $cantidad;
                }
            }
        }
        
        // Si no hay costo, estimar 30% del subtotal (antes de descuentos)
        if ($costo_total == 0 && $venta['subtotal'] > 0) {
            $costo_total = $venta['subtotal'] * 0.3;
        } elseif ($costo_total == 0 && $venta['monto_total'] > 0) {
            $costo_total = $venta['monto_total'] * 0.3;
        }
        
        $precio_final = $venta['monto_total']; // Ya incluye descuentos aplicados
        $ganancia = $precio_final - $costo_total;
        $margen = ($venta['monto_total'] > 0) ? ($ganancia / $venta['monto_total']) * 100 : 0;
        
        $venta_procesada = [
            'fecha_hora' => date('d/m H:i', strtotime($venta['fecha'])),
            'referencia' => $venta['id'],
            'metodo_pago' => ucfirst($venta['metodo_pago']),
            'precio_total_venta' => floatval($venta['subtotal'] ?? $venta['monto_total']), // Mostrar subtotal original
            'precio_costo' => $costo_total,
            'descuento' => floatval($venta['descuento'] ?? 0),
            'precio_final' => floatval($venta['monto_total']), // Precio después de descuentos
            'ganancia_neta' => $ganancia,
            'margen_porcentual' => round($margen, 1)
        ];
        
        $ventas_detalle[] = $venta_procesada;
        
        // Acumular totales
        $totales['total_ventas'] += $venta['monto_total'];
        $totales['total_costos'] += $costo_total;
        $totales['total_descuentos'] += ($venta['descuento'] ?? 0);
        $totales['total_ganancias'] += $ganancia;
    }
    
    // 3. CALCULAR MÉTODOS DE PAGO
    $metodos_pago = [
        'efectivo' => 0,
        'tarjeta' => 0,
        'transferencia' => 0,
        'qr' => 0
    ];
    
    foreach ($ventas_bd as $venta) {
        $metodo = strtolower($venta['metodo_pago']);
        if (isset($metodos_pago[$metodo])) {
            $metodos_pago[$metodo] += $venta['monto_total'];
        }
    }
    
    // 4. ESTRUCTURA COMPLETA CORREGIDA
    $response = [
        'success' => true,
        'fecha_calculo' => date('Y-m-d H:i:s'),
        'periodo' => [
            'tipo' => $periodo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ],
        
        // COMPONENTE 1: Ventas y Ganancias
        'componente_1_ventas_ganancias' => [
            'tarjeta_1_ganancia_neta' => [
                'valor_principal' => $totales['total_ganancias'],
                'titulo' => 'Ganancia Neta Total',
                'subtitulo' => 'Suma de ganancias de todas las ventas',
                'icono' => 'trending-up',
                'color' => 'green'
            ],
            'tarjeta_2_ventas_brutas' => [
                'valor_principal' => $totales['total_ventas'],
                'titulo' => 'Ventas Netas',
                'subtitulo' => 'Total ventas brutas - descuentos',
                'icono' => 'dollar-sign',
                'color' => 'blue'
            ],
            'tarjeta_3_descuentos' => [
                'valor_principal' => $totales['total_descuentos'],
                'titulo' => 'Total Descuentos',
                'subtitulo' => 'Suma de todos los descuentos aplicados',
                'icono' => 'percent',
                'color' => 'orange'
            ],
            'tarjeta_4_resultado_operacional' => [
                'valor_principal' => $totales['total_ganancias'],
                'titulo' => 'Resultado Operacional',
                'subtitulo' => 'Ganancia Neta - Gastos del Período',
                'icono' => 'trending-up',
                'color' => 'emerald'
            ]
        ],
        
        // COMPONENTE 2: Gastos Fijos
        'componente_2_gastos_fijos' => [
            'tarjeta_1_gastos_mensuales' => [
                'valor_principal' => 0,
                'titulo' => 'Gastos Fijos Mensuales',
                'subtitulo' => 'Gastos diarios: $0.00',
                'icono' => 'building',
                'color' => 'purple'
            ],
            'tarjeta_2_gastos_diarios' => [
                'valor_principal' => 0,
                'titulo' => 'Gastos Fijos Diarios',
                'subtitulo' => 'Balance: $' . number_format($totales['total_ganancias'], 2),
                'icono' => 'calendar',
                'color' => 'indigo'
            ],
            'tarjeta_3_saldo_faltante' => [
                'valor_principal' => 0,
                'titulo' => 'Saldo Faltante',
                'subtitulo' => 'Meta alcanzada',
                'icono' => 'target',
                'color' => 'green'
            ],
            'tarjeta_4_roi' => [
                'valor_principal' => ($totales['total_costos'] > 0) ? round(($totales['total_ganancias'] / $totales['total_costos']) * 100, 1) : 0,
                'titulo' => 'ROI (%)',
                'subtitulo' => 'Retorno sobre inversión',
                'icono' => 'percent',
                'color' => 'red'
            ]
        ],
        
        // COMPONENTE 3: Métodos de Pago
        'componente_3_metodos_pago' => [
            'tarjeta_1_efectivo' => [
                'valor_principal' => $metodos_pago['efectivo'],
                'titulo' => 'Efectivo',
                'subtitulo' => 'Ganancia neta: $' . number_format($metodos_pago['efectivo'] * 0.7, 2),
                'icono' => 'banknote',
                'color' => 'green'
            ],
            'tarjeta_2_transferencia' => [
                'valor_principal' => $metodos_pago['transferencia'],
                'titulo' => 'Transferencia',
                'subtitulo' => 'Ganancia neta: $' . number_format($metodos_pago['transferencia'] * 0.7, 2),
                'icono' => 'arrow-right-left',
                'color' => 'blue'
            ],
            'tarjeta_3_tarjeta' => [
                'valor_principal' => $metodos_pago['tarjeta'],
                'titulo' => 'Tarjeta',
                'subtitulo' => 'Ganancia neta: $' . number_format($metodos_pago['tarjeta'] * 0.7, 2),
                'icono' => 'credit-card',
                'color' => 'purple'
            ],
            'tarjeta_4_qr' => [
                'valor_principal' => $metodos_pago['qr'],
                'titulo' => 'Pago QR',
                'subtitulo' => 'Ganancia neta: $' . number_format($metodos_pago['qr'] * 0.7, 2),
                'icono' => 'qr-code',
                'color' => 'indigo'
            ]
        ],
        
        // COMPONENTE 4: DETALLE DE VENTAS CORREGIDO
        'componente_4_detalle_ventas' => [
            'ventas_individuales' => $ventas_detalle, // Frontend busca esta propiedad
            'ventas' => $ventas_detalle, // Mantener por compatibilidad
            'totales' => $totales
        ],
        
        'configuracion_gastos' => [
            'gastos_mensuales' => 0,
            'gastos_diarios' => 0,
            'mes_actual' => date('Y-m'),
            'dias_mes' => date('t')
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
