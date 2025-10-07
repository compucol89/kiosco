<?php
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
    
    // Calculate date range for current month
    $fechaInicio = date('Y-m-01');
    $fechaFin = date('Y-m-t');
    
    // Get ventas with complete details
    $stmtVentas = $pdo->prepare("
        SELECT v.*, 
               COALESCE(v.descuento, 0) as descuento_aplicado,
               CASE 
                   WHEN v.descuento > 0 THEN 'Descuento Aplicado'
                   ELSE 'Sin Descuento'
               END as tipo_descuento
        FROM ventas v
        WHERE DATE(v.fecha) BETWEEN ? AND ? 
        AND v.estado != 'anulada'
        ORDER BY v.fecha DESC
    ");
    $stmtVentas->execute([$fechaInicio, $fechaFin]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each venta to add product details
    $ventasDetalladas = [];
    $productosVendidos = [];
    $resumenProductos = [];
    
    foreach ($ventas as $venta) {
        // Parse detalles_json if exists
        $detalles = [];
        if (!empty($venta['detalles_json'])) {
            $detallesData = json_decode($venta['detalles_json'], true);
            if ($detallesData && isset($detallesData['cart'])) {
                foreach ($detallesData['cart'] as $item) {
                    $producto = [
                        'id' => $item['id'] ?? 0,
                        'nombre' => $item['name'] ?? 'Producto sin nombre',
                        'cantidad' => $item['quantity'] ?? 1,
                        'precio_unitario' => $item['price'] ?? 0,
                        'subtotal' => ($item['quantity'] ?? 1) * ($item['price'] ?? 0)
                    ];
                    $detalles[] = $producto;
                    
                    // Add to productos vendidos summary
                    $productId = $producto['id'];
                    if (!isset($resumenProductos[$productId])) {
                        $resumenProductos[$productId] = [
                            'id' => $productId,
                            'nombre' => $producto['nombre'],
                            'cantidad_total' => 0,
                            'ingresos_totales' => 0,
                            'ventas_count' => 0
                        ];
                    }
                    
                    $resumenProductos[$productId]['cantidad_total'] += $producto['cantidad'];
                    $resumenProductos[$productId]['ingresos_totales'] += $producto['subtotal'];
                    $resumenProductos[$productId]['ventas_count']++;
                }
            }
        }
        
        // Add detailed venta info
        $ventaDetalle = [
            'id' => $venta['id'],
            'fecha' => $venta['fecha'],
            'cliente_nombre' => $venta['cliente_nombre'] ?? 'Consumidor Final',
            'metodo_pago' => $venta['metodo_pago'],
            'subtotal' => floatval($venta['subtotal'] ?? 0),
            'descuento' => floatval($venta['descuento_aplicado']),
            'monto_total' => floatval($venta['monto_total']),
            'tipo_descuento' => $venta['tipo_descuento'],
            'numero_comprobante' => $venta['numero_comprobante'] ?? '',
            'productos' => $detalles,
            'cantidad_productos' => count($detalles),
            'tiene_descuento' => $venta['descuento_aplicado'] > 0
        ];
        
        $ventasDetalladas[] = $ventaDetalle;
        
        // Add to productos vendidos list
        foreach ($detalles as $producto) {
            $productosVendidos[] = [
                'venta_id' => $venta['id'],
                'fecha' => $venta['fecha'],
                'producto_nombre' => $producto['nombre'],
                'cantidad' => $producto['cantidad'],
                'precio_unitario' => $producto['precio_unitario'],
                'subtotal' => $producto['subtotal'],
                'descuento_venta' => $venta['descuento_aplicado'],
                'metodo_pago' => $venta['metodo_pago']
            ];
        }
    }
    
    // Calculate trazabilidad (same as before)
    $trazabilidad = [
        'ingresos_totales' => 0,
        'egresos_totales' => 0,
        'flujo_neto' => 0,
        'desglose_ingresos' => [
            'ventas_efectivo' => 0,
            'ventas_tarjeta' => 0,
            'ventas_transferencia' => 0,
            'ventas_mercadopago' => 0,
            'ventas_otros' => 0,
            'ingresos_extra' => 0
        ],
        'desglose_egresos' => [
            'gastos_fijos' => 0,
            'sueldos' => 0,
            'compras_mercaderia' => 0,
            'servicios' => 0,
            'impuestos' => 0,
            'otros_gastos' => 0
        ]
    ];
    
    // Process ventas by payment method
    foreach ($ventas as $venta) {
        $monto = floatval($venta['monto_total']);
        $metodo = strtolower($venta['metodo_pago']);
        
        switch ($metodo) {
            case 'efectivo':
                $trazabilidad['desglose_ingresos']['ventas_efectivo'] += $monto;
                break;
            case 'tarjeta':
                $trazabilidad['desglose_ingresos']['ventas_tarjeta'] += $monto;
                break;
            case 'transferencia':
                $trazabilidad['desglose_ingresos']['ventas_transferencia'] += $monto;
                break;
            case 'mercadopago':
                $trazabilidad['desglose_ingresos']['ventas_mercadopago'] += $monto;
                break;
            default:
                $trazabilidad['desglose_ingresos']['ventas_otros'] += $monto;
                break;
        }
    }
    
    $trazabilidad['ingresos_totales'] = array_sum($trazabilidad['desglose_ingresos']);
    $trazabilidad['flujo_neto'] = $trazabilidad['ingresos_totales'] - $trazabilidad['egresos_totales'];
    
    // Calculate summary statistics
    $totalDescuentos = array_sum(array_column($ventas, 'descuento_aplicado'));
    $ventasConDescuento = count(array_filter($ventas, function($v) {
        return $v['descuento_aplicado'] > 0;
    }));
    
    // Prepare utilidades productos (top selling products)
    $utilidadesProductos = array_values($resumenProductos);
    usort($utilidadesProductos, function($a, $b) {
        return $b['ingresos_totales'] <=> $a['ingresos_totales'];
    });
    
    // Add ranking
    foreach ($utilidadesProductos as $index => &$producto) {
        $producto['ranking'] = $index + 1;
        $producto['porcentaje_ingresos'] = $trazabilidad['ingresos_totales'] > 0 
            ? ($producto['ingresos_totales'] / $trazabilidad['ingresos_totales']) * 100 
            : 0;
    }
    
    // Return the complete response
    echo json_encode([
        'success' => true,
        'datos' => [
            'ventas' => $ventasDetalladas,
            'productos' => [],
            'ingresos' => [],
            'egresos' => [],
            'trazabilidad_financiera' => $trazabilidad,
            'utilidades_productos' => $utilidadesProductos,
            'productos_vendidos' => $productosVendidos,
            'arqueo' => [],
            'alertas' => [],
            'comparativas' => [],
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ],
            'resumen_descuentos' => [
                'total_descuentos' => $totalDescuentos,
                'ventas_con_descuento' => $ventasConDescuento,
                'total_ventas' => count($ventas),
                'porcentaje_ventas_con_descuento' => count($ventas) > 0 
                    ? ($ventasConDescuento / count($ventas)) * 100 
                    : 0
            ]
        ],
        // Also include direct properties for compatibility
        'trazabilidadFinanciera' => $trazabilidad,
        'utilidadesProductos' => $utilidadesProductos,
        'productosVendidos' => $productosVendidos,
        'arqueo' => [],
        'alertas' => [],
        'comparativas' => [],
        'ventas' => $ventasDetalladas,
        'productos' => [],
        'egresos' => [],
        'resumenDescuentos' => [
            'total_descuentos' => $totalDescuentos,
            'ventas_con_descuento' => $ventasConDescuento,
            'total_ventas' => count($ventas)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 