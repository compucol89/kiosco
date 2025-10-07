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
    
    // Get productos from database to get categoria and precio_costo
    $stmtProductos = $pdo->prepare("SELECT id, nombre, categoria, precio_costo, precio_venta FROM productos");
    $stmtProductos->execute();
    $productosDB = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
    
    // Create productos lookup
    $productosLookup = [];
    foreach ($productosDB as $prod) {
        $productosLookup[$prod['id']] = $prod;
    }
    
    // Process each venta to add product details
    $ventasDetalladas = [];
    $productosResumen = [];
    
    foreach ($ventas as $venta) {
        // Parse detalles_json if exists
        $detalles = [];
        if (!empty($venta['detalles_json'])) {
            $detallesData = json_decode($venta['detalles_json'], true);
            if ($detallesData && isset($detallesData['cart'])) {
                foreach ($detallesData['cart'] as $item) {
                    $productId = $item['id'] ?? 0;
                    $cantidad = $item['quantity'] ?? 1;
                    $precioVenta = $item['price'] ?? 0;
                    $subtotal = $cantidad * $precioVenta;
                    
                    // Get producto info from DB
                    $productoDB = $productosLookup[$productId] ?? null;
                    $categoria = $productoDB['categoria'] ?? 'Sin categoría';
                    $precioCosto = floatval($productoDB['precio_costo'] ?? 0);
                    
                    // Calculate costs and profits
                    $costoTotal = $cantidad * $precioCosto;
                    $utilidad = $subtotal - $costoTotal;
                    $margen = $subtotal > 0 ? ($utilidad / $subtotal) * 100 : 0;
                    
                    $producto = [
                        'id' => $productId,
                        'nombre' => $item['name'] ?? 'Producto sin nombre',
                        'categoria' => $categoria,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioVenta,
                        'precio_costo' => $precioCosto,
                        'subtotal' => $subtotal,
                        'costo_total' => $costoTotal,
                        'utilidad' => $utilidad,
                        'margen' => $margen
                    ];
                    $detalles[] = $producto;
                    
                    // Add to resumen productos
                    if (!isset($productosResumen[$productId])) {
                        $productosResumen[$productId] = [
                            'id' => $productId,
                            'nombre' => $producto['nombre'],
                            'categoria' => $categoria,
                            'cantidad_vendida' => 0,
                            'ingresos' => 0,
                            'costos' => 0,
                            'utilidad' => 0,
                            'margen' => 0,
                            'ventas_count' => 0
                        ];
                    }
                    
                    $productosResumen[$productId]['cantidad_vendida'] += $cantidad;
                    $productosResumen[$productId]['ingresos'] += $subtotal;
                    $productosResumen[$productId]['costos'] += $costoTotal;
                    $productosResumen[$productId]['utilidad'] += $utilidad;
                    $productosResumen[$productId]['ventas_count']++;
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
    }
    
    // Calculate final margins for productos resumen
    foreach ($productosResumen as &$producto) {
        if ($producto['ingresos'] > 0) {
            $producto['margen'] = ($producto['utilidad'] / $producto['ingresos']) * 100;
        }
    }
    
    // Convert to array and sort by ingresos
    $productosArray = array_values($productosResumen);
    usort($productosArray, function($a, $b) {
        return $b['ingresos'] <=> $a['ingresos'];
    });
    
    // Calculate trazabilidad
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
    
    // Create rubros (categories) summary
    $rubrosResumen = [];
    foreach ($productosArray as $producto) {
        $categoria = $producto['categoria'];
        if (!isset($rubrosResumen[$categoria])) {
            $rubrosResumen[$categoria] = [
                'nombre' => $categoria,
                'productos_count' => 0,
                'ingresos' => 0,
                'costos' => 0,
                'utilidad' => 0,
                'margen' => 0
            ];
        }
        
        $rubrosResumen[$categoria]['productos_count']++;
        $rubrosResumen[$categoria]['ingresos'] += $producto['ingresos'];
        $rubrosResumen[$categoria]['costos'] += $producto['costos'];
        $rubrosResumen[$categoria]['utilidad'] += $producto['utilidad'];
    }
    
    // Calculate margins for rubros
    foreach ($rubrosResumen as &$rubro) {
        if ($rubro['ingresos'] > 0) {
            $rubro['margen'] = ($rubro['utilidad'] / $rubro['ingresos']) * 100;
        }
    }
    
    $rubrosArray = array_values($rubrosResumen);
    
    // Return the response in the format expected by frontend
    echo json_encode([
        'success' => true,
        'datos' => [
            'ventas' => $ventasDetalladas,
            'productos' => [],
            'ingresos' => [],
            'egresos' => [],
            'trazabilidad_financiera' => $trazabilidad,
            'utilidades_productos' => [
                'productos' => $productosArray,  // This is what frontend expects
                'rubros' => $rubrosArray         // This is what frontend expects
            ],
            'arqueo' => [
                'resumen_diario' => [],
                'totales' => [
                    'diferencia_efectivo' => 0,
                    'diferencia_digital' => 0,
                    'ingresos_efectivo' => 0,
                    'egresos_efectivo' => 0
                ],
                'periodo' => ['dias' => 0]
            ],
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
        'utilidadesProductos' => [
            'productos' => $productosArray,
            'rubros' => $rubrosArray
        ],
        'arqueo' => [
            'resumen_diario' => [],
            'totales' => [
                'diferencia_efectivo' => 0,
                'diferencia_digital' => 0,
                'ingresos_efectivo' => 0,
                'egresos_efectivo' => 0
            ],
            'periodo' => ['dias' => 0]
        ],
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