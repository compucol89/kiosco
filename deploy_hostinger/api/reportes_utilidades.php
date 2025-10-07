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
    // Set timezone to Argentina to avoid date issues
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    
    $pdo = Conexion::obtenerConexion();
    
    // Get period parameters from frontend
    $periodo = $_GET['periodo'] ?? 'hoy';
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    
    // Calculate date range based on parameters
    switch ($periodo) {
        case 'hoy':
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
            break;
        case 'mes-actual':
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
            break;
        case 'personalizado':
            // Use provided dates or fallback to today
            if (!$fechaInicio || !$fechaFin) {
                $fechaInicio = date('Y-m-d');
                $fechaFin = date('Y-m-d');
            }
            break;
        default:
            // Default to today
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
            break;
    }
    

    
    // Get ventas with complete details using full datetime range
    $stmtVentas = $pdo->prepare("
        SELECT v.*, 
               COALESCE(v.descuento, 0) as descuento_aplicado
        FROM ventas v
        WHERE v.fecha BETWEEN ? AND ? 
        AND v.estado != 'anulada'
        ORDER BY v.fecha DESC
    ");
    $stmtVentas->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    
    // *** CORRECCIÓN CRÍTICA: Separar gastos fijos mensuales de gastos operacionales diarios ***
    
    // Gastos fijos mensuales (alquiler, sueldos, etc.) - Para trazabilidad mensual
    $stmtGastosFijos = $pdo->prepare("
        SELECT * FROM egresos 
        WHERE categoria IN ('gastos_fijos', 'sueldos', 'impuestos')
        AND fecha BETWEEN ? AND ?
        ORDER BY fecha DESC
    ");
    $stmtGastosFijos->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
    $gastosFijos = $stmtGastosFijos->fetchAll(PDO::FETCH_ASSOC);
    
    // Gastos operacionales diarios (compras mercadería, servicios diarios, etc.) - Para arqueo diario
    $stmtGastosOperacionales = $pdo->prepare("
        SELECT * FROM egresos 
        WHERE categoria IN ('compras_mercaderia', 'servicios', 'otros_gastos')
        AND fecha BETWEEN ? AND ?
        ORDER BY fecha DESC
    ");
    $stmtGastosOperacionales->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
    $gastosOperacionales = $stmtGastosOperacionales->fetchAll(PDO::FETCH_ASSOC);
    
    // Todos los egresos para trazabilidad general
    $stmtEgresos = $pdo->prepare("
        SELECT * FROM egresos 
        WHERE DATE(fecha) BETWEEN ? AND ?
        ORDER BY fecha DESC
    ");
    $stmtEgresos->execute([$fechaInicio, $fechaFin]);
    $egresos = $stmtEgresos->fetchAll(PDO::FETCH_ASSOC);
    
    // Get productos from database
    $stmtProductos = $pdo->prepare("SELECT id, nombre, categoria, precio_costo, precio_venta FROM productos");
    $stmtProductos->execute();
    $productosDB = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
    
    // Create productos lookup
    $productosLookup = [];
    foreach ($productosDB as $prod) {
        $productosLookup[$prod['id']] = $prod;
    }
    
    // Process each venta individually with improved naming
    $ventasDetalladas = [];
    $utilidadesPorVenta = [];
    
    // Variables para indicador general de utilidades
    $totalIngresosBrutos = 0;
    $totalDescuentos = 0;
    $totalIngresosNetos = 0;
    $totalCostos = 0;
    $totalUtilidades = 0;
    $ventasConDescuento = 0;
    $ventasSinDescuento = 0;
    
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
                    $precioCosto = floatval($productoDB['precio_costo'] ?? 0);
                    
                    // Calculate costs and profits
                    $costoTotal = $cantidad * $precioCosto;
                    $utilidad = $subtotal - $costoTotal;
                    $margen = $subtotal > 0 ? ($utilidad / $subtotal) * 100 : 0;
                    
                    $producto = [
                        'id' => $productId,
                        'nombre' => $item['name'] ?? 'Producto sin nombre',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioVenta,
                        'precio_costo' => $precioCosto,
                        'subtotal' => $subtotal,
                        'costo_total' => $costoTotal,
                        'utilidad' => $utilidad,
                        'margen' => $margen
                    ];
                    $detalles[] = $producto;
                    
                    // *** IMPROVED NAMING AND CATEGORIZATION ***
                    $ventaId = $venta['id'];
                    $descuentoVenta = floatval($venta['descuento_aplicado']);
                    $montoTotalVenta = floatval($venta['monto_total']);
                    $metodoPago = ucfirst(strtolower($venta['metodo_pago']));
                    
                    // Calculate proportional discount for this product
                    $subtotalVenta = floatval($venta['subtotal']);
                    $proporcionProducto = $subtotalVenta > 0 ? ($subtotal / $subtotalVenta) : 0;
                    $descuentoProducto = $descuentoVenta * $proporcionProducto;
                    
                    // Calculate real net income after proportional discount
                    $ingresoNeto = $subtotal - $descuentoProducto;
                    $utilidadConDescuento = $ingresoNeto - $costoTotal;
                    $margenConDescuento = $ingresoNeto > 0 ? ($utilidadConDescuento / $ingresoNeto) * 100 : 0;
                    
                    // *** NEW FORMAT: "4 - Actron 400mg (ibuprofeno) (Descuento: $55)" ***
                    $nombreVenta = "{$ventaId} - {$producto['nombre']}";
                    if ($descuentoProducto > 0) {
                        $nombreVenta .= " (Descuento: $" . number_format($descuentoProducto, 0) . ")";
                    }
                    
                    // Create individual sale entry with payment method as category
                    $saleEntry = [
                        'id' => $ventaId,
                        'nombre' => $nombreVenta,
                        'categoria' => $metodoPago, // *** PAYMENT METHOD AS CATEGORY ***
                        'cantidad_vendida' => $cantidad,
                        'ingresos' => $ingresoNeto,
                        'costos' => $costoTotal,
                        'utilidad' => $utilidadConDescuento,
                        'margen' => $margenConDescuento,
                        'ventas_count' => 1,
                        'fecha_venta' => $venta['fecha'],
                        'metodo_pago' => $venta['metodo_pago'],
                        'subtotal_bruto' => $subtotal,
                        'descuento_aplicado' => $descuentoProducto,
                        'tiene_descuento' => $descuentoProducto > 0
                    ];
                    
                    $utilidadesPorVenta[] = $saleEntry;
                    
                    // *** ACCUMULATE FOR GENERAL UTILITIES INDICATOR ***
                    $totalIngresosBrutos += $subtotal;
                    $totalDescuentos += $descuentoProducto;
                    $totalIngresosNetos += $ingresoNeto;
                    $totalCostos += $costoTotal;
                    $totalUtilidades += $utilidadConDescuento;
                    
                    if ($descuentoVenta > 0) {
                        $ventasConDescuento++;
                    } else {
                        $ventasSinDescuento++;
                    }
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
            'numero_comprobante' => $venta['numero_comprobante'] ?? '',
            'productos' => $detalles,
            'cantidad_productos' => count($detalles),
            'tiene_descuento' => $venta['descuento_aplicado'] > 0
        ];
        
        $ventasDetalladas[] = $ventaDetalle;
    }
    
    // Sort sales by ID DESC (most recent first)
    usort($utilidadesPorVenta, function($a, $b) {
        return $b['id'] - $a['id'];
    });
    
    // *** CALCULATE CLEAR UTILITIES INDICATOR WITH NET PROFIT ***
    $margenPromedioGeneral = $totalIngresosNetos > 0 ? ($totalUtilidades / $totalIngresosNetos) * 100 : 0;
    $porcentajeDescuentos = $totalIngresosBrutos > 0 ? ($totalDescuentos / $totalIngresosBrutos) * 100 : 0;
    
    // *** CORRECCIÓN CRÍTICA: Calcular utilidad NETA real restando gastos fijos ***
    $totalGastosFijos = array_sum(array_column($gastosFijos, 'monto'));
    $utilidadNeta = $totalUtilidades - $totalGastosFijos;
    $margenNetoReal = $totalIngresosNetos > 0 ? ($utilidadNeta / $totalIngresosNetos) * 100 : 0;
    $estaNegativo = $utilidadNeta < 0;
    
    $indicadorUtilidades = [
        'resumen_general' => [
            'total_ventas' => count($ventas),
            'ingresos_brutos' => $totalIngresosBrutos,
            'descuentos_aplicados' => $totalDescuentos,
            'ingresos_netos' => $totalIngresosNetos,
            'costos_totales' => $totalCostos,
            'utilidades_brutas' => $totalUtilidades, // Renombrado para claridad
            'gastos_fijos_mensuales' => $totalGastosFijos, // *** NUEVO ***
            'utilidad_neta_real' => $utilidadNeta, // *** NUEVO: LA CIFRA MÁS IMPORTANTE ***
            'margen_bruto' => $margenPromedioGeneral, // Renombrado para claridad
            'margen_neto_real' => $margenNetoReal, // *** NUEVO ***
            'porcentaje_descuentos' => $porcentajeDescuentos,
            'negocio_en_perdidas' => $estaNegativo // *** NUEVO: Indicador crítico ***
        ],
        'desglose_descuentos' => [
            'ventas_con_descuento' => $ventasConDescuento,
            'ventas_sin_descuento' => $ventasSinDescuento,
            'descuento_promedio' => $ventasConDescuento > 0 ? ($totalDescuentos / $ventasConDescuento) : 0
        ],
        'rentabilidad' => [
            'utilidad_bruta_por_venta' => count($ventas) > 0 ? ($totalUtilidades / count($ventas)) : 0,
            'utilidad_neta_por_venta' => count($ventas) > 0 ? ($utilidadNeta / count($ventas)) : 0, // *** NUEVO ***
            'roi_bruto_porcentaje' => $totalCostos > 0 ? (($totalUtilidades / $totalCostos) * 100) : 0,
            'roi_neto_porcentaje' => $totalCostos > 0 ? (($utilidadNeta / $totalCostos) * 100) : 0, // *** NUEVO ***
            'eficiencia_descuentos' => $totalDescuentos > 0 ? (($totalUtilidades / $totalDescuentos) * 100) : 0,
            'dias_para_cubrir_gastos_fijos' => $totalUtilidades > 0 ? ($totalGastosFijos / $totalUtilidades) : 0 // *** NUEVO ***
        ]
    ];
    
    // Calculate trazabilidad INCLUDING EGRESOS
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
    
    // Process egresos by category
    foreach ($egresos as $egreso) {
        $monto = floatval($egreso['monto']);
        $categoria = $egreso['categoria'];
        
        switch ($categoria) {
            case 'gastos_fijos':
                $trazabilidad['desglose_egresos']['gastos_fijos'] += $monto;
                break;
            case 'sueldos':
                $trazabilidad['desglose_egresos']['sueldos'] += $monto;
                break;
            case 'compras_mercaderia':
                $trazabilidad['desglose_egresos']['compras_mercaderia'] += $monto;
                break;
            case 'servicios':
                $trazabilidad['desglose_egresos']['servicios'] += $monto;
                break;
            case 'impuestos':
                $trazabilidad['desglose_egresos']['impuestos'] += $monto;
                break;
            default:
                $trazabilidad['desglose_egresos']['otros_gastos'] += $monto;
                break;
        }
    }
    
    $trazabilidad['ingresos_totales'] = array_sum($trazabilidad['desglose_ingresos']);
    $trazabilidad['egresos_totales'] = array_sum($trazabilidad['desglose_egresos']);
    $trazabilidad['flujo_neto'] = $trazabilidad['ingresos_totales'] - $trazabilidad['egresos_totales'];
    
    // *** CORRECCIÓN CRÍTICA: ARQUEO DE CAJA SOLO CON FECHAS DE VENTAS ***
    $fechasVentas = [];
    
    // Get unique dates ONLY from ventas (not egresos to avoid date confusion)
    foreach ($ventas as $venta) {
        // Use DateTime for more reliable date parsing
        try {
            $dateTime = new DateTime($venta['fecha']);
            $fecha = $dateTime->format('Y-m-d');
        } catch (Exception $e) {
            // Fallback to strtotime if DateTime fails
            $fecha = date('Y-m-d', strtotime($venta['fecha']));
        }

        $fechasVentas[$fecha] = true;
    }
    
    $resumenDiario = [];
    
    foreach (array_keys($fechasVentas) as $fecha) {
        // Calculate daily totals ONLY from sales
        $ventasDelDia = array_filter($ventas, function($v) use ($fecha) {
            try {
                $dateTime = new DateTime($v['fecha']);
                $fechaVenta = $dateTime->format('Y-m-d');
            } catch (Exception $e) {
                $fechaVenta = date('Y-m-d', strtotime($v['fecha']));
            }
            return $fechaVenta === $fecha;
        });
        
        // *** SOLO GASTOS OPERACIONALES DEL DÍA (no gastos fijos mensuales) ***
        $gastosOperacionalesDelDia = array_filter($gastosOperacionales, function($e) use ($fecha) {
            try {
                $dateTime = new DateTime($e['fecha']);
                $fechaEgreso = $dateTime->format('Y-m-d');
            } catch (Exception $e) {
                $fechaEgreso = date('Y-m-d', strtotime($e['fecha']));
            }
            return $fechaEgreso === $fecha;
        });
        
        $ingresosEfectivo = 0;
        $ingresosDigitales = 0;
        $numVentas = count($ventasDelDia);
        
        foreach ($ventasDelDia as $venta) {
            $monto = floatval($venta['monto_total']);
            $metodo = strtolower($venta['metodo_pago']);
            
            if ($metodo === 'efectivo') {
                $ingresosEfectivo += $monto;
            } else {
                $ingresosDigitales += $monto;
            }
        }
        
        // Solo gastos operacionales (compras, servicios del día, etc.)
        $egresosEfectivo = 0;
        $egresosDigitales = 0;
        
        foreach ($gastosOperacionalesDelDia as $egreso) {
            $monto = floatval($egreso['monto']);
            $egresosEfectivo += $monto; // Asumimos efectivo por defecto
        }
        
        $saldoEfectivo = $ingresosEfectivo - $egresosEfectivo;
        $saldoDigital = $ingresosDigitales - $egresosDigitales;
        
        $resumenDiario[] = [
            'fecha' => $fecha,
            'num_ventas' => $numVentas,
            'ingresos_efectivo' => $ingresosEfectivo,
            'ingresos_digitales' => $ingresosDigitales,
            'egresos_efectivo' => $egresosEfectivo, // Solo operacionales
            'egresos_digitales' => $egresosDigitales,
            'saldo_efectivo' => $saldoEfectivo,
            'saldo_digital' => $saldoDigital
        ];
    }
    
    // Sort by date descending
    usort($resumenDiario, function($a, $b) {
        return strcmp($b['fecha'], $a['fecha']);
    });
    
    // Calculate period days
    $fechaInicioObj = new DateTime($fechaInicio);
    $fechaFinObj = new DateTime($fechaFin);
    $periodoDias = $fechaFinObj->diff($fechaInicioObj)->days + 1;
    
    // Calculate totals for arqueo
    $totalesArqueo = [
        'diferencia_efectivo' => array_sum(array_column($resumenDiario, 'saldo_efectivo')),
        'diferencia_digital' => array_sum(array_column($resumenDiario, 'saldo_digital')),
        'ingresos_efectivo' => array_sum(array_column($resumenDiario, 'ingresos_efectivo')),
        'ingresos_digitales' => array_sum(array_column($resumenDiario, 'ingresos_digitales')),
        'egresos_efectivo' => array_sum(array_column($resumenDiario, 'egresos_efectivo')),
        'egresos_digitales' => array_sum(array_column($resumenDiario, 'egresos_digitales'))
    ];
    
    // Calculate summary statistics
    $totalDescuentosVentas = array_sum(array_column($ventas, 'descuento_aplicado'));
    $ventasConDescuentoCount = count(array_filter($ventas, function($v) {
        return $v['descuento_aplicado'] > 0;
    }));
    
    // Create rubros (categories) summary by payment method
    $rubrosResumen = [];
    foreach ($utilidadesPorVenta as $sale) {
        $categoria = $sale['categoria']; // Now using payment method
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
        $rubrosResumen[$categoria]['ingresos'] += $sale['ingresos'];
        $rubrosResumen[$categoria]['costos'] += $sale['costos'];
        $rubrosResumen[$categoria]['utilidad'] += $sale['utilidad'];
    }
    
    // Calculate margins for rubros
    foreach ($rubrosResumen as &$rubro) {
        if ($rubro['ingresos'] > 0) {
            $rubro['margen'] = ($rubro['utilidad'] / $rubro['ingresos']) * 100;
        }
    }
    
    $rubrosArray = array_values($rubrosResumen);
    
    // *** NUEVO: Información de gastos fijos mensuales ***
    $gastosFijosMensuales = [
        'total_gastos_fijos' => array_sum(array_column($gastosFijos, 'monto')),
        'gastos_por_categoria' => [
            'gastos_fijos' => array_sum(array_column(array_filter($gastosFijos, function($g) { 
                return $g['categoria'] === 'gastos_fijos'; 
            }), 'monto')),
            'sueldos' => array_sum(array_column(array_filter($gastosFijos, function($g) { 
                return $g['categoria'] === 'sueldos'; 
            }), 'monto')),
            'impuestos' => array_sum(array_column(array_filter($gastosFijos, function($g) { 
                return $g['categoria'] === 'impuestos'; 
            }), 'monto'))
        ],
        'detalles' => $gastosFijos
    ];
    

    
    // Return the response with corrected date logic and separated expenses
    echo json_encode([
        'success' => true,
        'datos' => [
            'ventas' => $ventasDetalladas,
            'productos' => [],
            'ingresos' => [],
            'egresos' => $egresos,
            'trazabilidad_financiera' => $trazabilidad,
            'utilidades_productos' => [
                'productos' => $utilidadesPorVenta,
                'rubros' => $rubrosArray,
                'indicador_general' => $indicadorUtilidades
            ],
            'arqueo' => [
                'resumen_diario' => $resumenDiario,
                'totales' => $totalesArqueo,
                'periodo' => ['dias' => $periodoDias]
            ],
            'gastos_fijos_mensuales' => $gastosFijosMensuales, // *** NUEVO ***
            'alertas' => [],
            'comparativas' => [],
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ],
            'resumen_descuentos' => [
                'total_descuentos' => $totalDescuentosVentas,
                'ventas_con_descuento' => $ventasConDescuentoCount,
                'total_ventas' => count($ventas),
                'porcentaje_ventas_con_descuento' => count($ventas) > 0 
                    ? ($ventasConDescuentoCount / count($ventas)) * 100 
                    : 0
            ]
        ],
        // Also include direct properties for compatibility
        'trazabilidadFinanciera' => $trazabilidad,
        'utilidadesProductos' => [
            'productos' => $utilidadesPorVenta,
            'rubros' => $rubrosArray,
            'indicador_general' => $indicadorUtilidades
        ],
        'arqueo' => [
            'resumen_diario' => $resumenDiario,
            'totales' => $totalesArqueo,
            'periodo' => ['dias' => $periodoDias]
        ],
        'gastosFijosMensuales' => $gastosFijosMensuales,
        'alertas' => [],
        'comparativas' => [],
        'ventas' => $ventasDetalladas,
        'productos' => [],
        'egresos' => $egresos,
        'resumenDescuentos' => [
            'total_descuentos' => $totalDescuentosVentas,
            'ventas_con_descuento' => $ventasConDescuentoCount,
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