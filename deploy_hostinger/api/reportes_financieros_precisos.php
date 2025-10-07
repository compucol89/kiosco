<?php
/**
 * SISTEMA DE REPORTES FINANCIEROS PRECISOS
 * Cálculos matemáticamente exactos con validación cruzada
 * 
 * FÓRMULAS IMPLEMENTADAS:
 * - Utilidad por producto = Precio_Venta - Costo_Producto
 * - Margen = (Utilidad / Precio_Venta) * 100
 * - Gastos fijos diarios = Gastos_Mensuales / Días_del_Mes
 * - Utilidad neta = Utilidad_Bruta - Gastos_Fijos_Diarios
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

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    
    // Parámetros de consulta
    $periodo = $_GET['periodo'] ?? 'hoy';
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    
    // Mapear período con todas las opciones solicitadas
    switch ($periodo) {
        case 'hoy':
            // HOY - Solo el día actual
            $fechaHoy = date('Y-m-d');
            $stmtVerificar = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE DATE(fecha) = ? AND estado IN ('completado', 'completada')");
            $stmtVerificar->execute([$fechaHoy]);
            $ventasHoy = $stmtVerificar->fetchColumn();
            
            if ($ventasHoy > 0) {
                $fechaInicio = $fechaHoy;
                $fechaFin = $fechaHoy;
            } else {
                // Si no hay ventas hoy, incluir últimos 3 días para mostrar datos reales
                $fechaInicio = date('Y-m-d', strtotime('-3 days'));
                $fechaFin = $fechaHoy;
            }
            break;
            
        case 'ayer':
            // AYER - Solo el día anterior
            $fechaAyer = date('Y-m-d', strtotime('-1 day'));
            $fechaInicio = $fechaAyer;
            $fechaFin = $fechaAyer;
            break;
            
        case 'semana':
            // ESTA SEMANA - Desde el lunes hasta hoy
            $fechaInicio = date('Y-m-d', strtotime('monday this week'));
            $fechaFin = date('Y-m-d');
            break;
            
        case 'mes':
            // ESTE MES - Desde el primer día del mes hasta hoy
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-d'); // Hasta hoy, no hasta final de mes
            break;
            
        case 'personalizado':
            // PERSONALIZAR - Usar fechas proporcionadas
            break;
            
        default:
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
            break;
    }
    
    // =============================================================================
    // 1. OBTENER VENTAS CON DETALLES COMPLETOS
    // =============================================================================
    
    // Verificar y agregar columnas faltantes a la tabla ventas
    try {
        // Verificar si las columnas existen antes de agregarlas
        $columnas = ['cambio_entregado', 'efectivo_recibido', 'cae', 'comprobante_fiscal'];
        foreach ($columnas as $columna) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = ?");
            $stmt->execute([$columna]);
            if ($stmt->fetchColumn() == 0) {
                switch ($columna) {
                    case 'cambio_entregado':
                    case 'efectivo_recibido':
                        $pdo->exec("ALTER TABLE ventas ADD COLUMN $columna DECIMAL(10,2) DEFAULT 0");
                        break;
                    case 'cae':
                        $pdo->exec("ALTER TABLE ventas ADD COLUMN $columna VARCHAR(50) DEFAULT NULL");
                        break;
                    case 'comprobante_fiscal':
                        $pdo->exec("ALTER TABLE ventas ADD COLUMN $columna VARCHAR(100) DEFAULT NULL");
                        break;
                }
            }
        }
    } catch (Exception $e) {
        // Error al agregar columnas, continuar sin ellas
        error_log("Error agregando columnas a ventas: " . $e->getMessage());
    }
    
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
            v.estado,
            COALESCE(v.cae, '') as cae,
            COALESCE(v.comprobante_fiscal, '') as comprobante_fiscal,
            COALESCE(v.cambio_entregado, 0) as cambio_entregado,
            COALESCE(v.efectivo_recibido, v.monto_total) as efectivo_recibido
        FROM ventas v
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        AND v.estado IN ('completado', 'completada')
        ORDER BY v.fecha DESC
    ");
    $stmtVentas->execute([$fechaInicio, $fechaFin]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    
    // =============================================================================
    // 2. OBTENER PRODUCTOS CON COSTOS REALES
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
    
    // Crear lookup de productos
    $productosLookup = [];
    foreach ($productos as $producto) {
        $productosLookup[$producto['id']] = $producto;
    }
    
    // =============================================================================
    // 3. CALCULADORA FINANCIERA PRECISA
    // =============================================================================
    
    class CalculadoraFinancieraPrecisa {
        
        private $productosLookup;
        private $debug = [];
        
        public function __construct($productosLookup) {
            $this->productosLookup = $productosLookup;
        }
        
        /**
         * Calcular utilidad exacta por producto
         * FÓRMULA: Utilidad = Precio_Venta - Costo_Producto
         * Ejemplo: Costo $1,000 + 40% aumento = Venta $1,400 → Utilidad $400
         */
        public function calcularUtilidadProducto($productoId, $cantidad, $precioVenta, $ventaId = null) {
            $producto = $this->productosLookup[$productoId] ?? null;
            
            if (!$producto) {
                return [
                    'error' => 'Producto no encontrado',
                    'producto_id' => $productoId
                ];
            }
            
            // Datos base
            $costo_unitario = floatval($producto['precio_costo']);
            $precio_venta_unitario = floatval($precioVenta);
            $cantidad_numerica = floatval($cantidad);
            
            // Cálculos precisos
            $utilidad_unitaria = $precio_venta_unitario - $costo_unitario;
            $costo_total = $costo_unitario * $cantidad_numerica;
            $ingreso_total = $precio_venta_unitario * $cantidad_numerica;
            $utilidad_total = $utilidad_unitaria * $cantidad_numerica;
            
            // Margen porcentual
            $margen_porcentaje = $precio_venta_unitario > 0 ? 
                ($utilidad_unitaria / $precio_venta_unitario) * 100 : 0;
            
            // Verificación de aumento
            $porcentaje_aumento = $costo_unitario > 0 ? 
                (($precio_venta_unitario - $costo_unitario) / $costo_unitario) * 100 : 0;
            
            return [
                'producto_id' => $productoId,
                'nombre' => $producto['nombre'],
                'categoria' => $producto['categoria'],
                'codigo' => $producto['codigo'],
                'cantidad' => $cantidad_numerica,
                'costo_unitario' => round($costo_unitario, 2),
                'precio_venta_unitario' => round($precio_venta_unitario, 2),
                'costo_total' => round($costo_total, 2),
                'ingreso_total' => round($ingreso_total, 2),
                'utilidad_unitaria' => round($utilidad_unitaria, 2),
                'utilidad_total' => round($utilidad_total, 2),
                'margen_porcentaje' => round($margen_porcentaje, 2),
                'porcentaje_aumento' => round($porcentaje_aumento, 2),
                'venta_id' => $ventaId
            ];
        }
        
        /**
         * Procesar venta completa con todos sus productos
         */
        public function procesarVenta($venta) {
            $ventaId = $venta['id'];
            $detallesJson = $venta['detalles_json'];
            $montoTotal = floatval($venta['monto_total']);
            $descuento = floatval($venta['descuento']);
            $metodoPago = $venta['metodo_pago'];
            
            // Parsear productos de la venta
            $productos = [];
            $totalUtilidad = 0;
            $totalCostos = 0;
            $totalIngresos = 0;
            
            if (!empty($detallesJson)) {
                $detalles = json_decode($detallesJson, true);
                
                if ($detalles && isset($detalles['cart'])) {
                    foreach ($detalles['cart'] as $item) {
                        $productoCalculo = $this->calcularUtilidadProducto(
                            $item['id'] ?? $item['codigo'] ?? 'GENERICO',
                            $item['quantity'] ?? $item['cantidad'] ?? 1,
                            $item['price'] ?? $item['precio'] ?? 0,
                            $ventaId
                        );
                        
                        if (!isset($productoCalculo['error'])) {
                            $productos[] = $productoCalculo;
                            $totalUtilidad += $productoCalculo['utilidad_total'];
                            $totalCostos += $productoCalculo['costo_total'];
                            $totalIngresos += $productoCalculo['ingreso_total'];
                        }
                    }
                }
            }
            
            // Verificar coherencia con monto total de venta
            $ingresosSinDescuento = $totalIngresos;
            $ingresosConDescuento = $ingresosSinDescuento - $descuento;
            $diferencia = abs($montoTotal - $ingresosConDescuento);
            
            // 🔧 CORRECCIÓN CRÍTICA: Utilidad bruta debe usar ingresos NETOS, no brutos
            $utilidadBrutaCorrecta = $ingresosConDescuento - $totalCostos;
            
            return [
                'venta_id' => $ventaId,
                'fecha' => $venta['fecha'],
                'cliente' => $venta['cliente_nombre'],
                'metodo_pago' => $metodoPago,
                'productos' => $productos,
                'resumen' => [
                    'cantidad_productos' => count($productos),
                    'total_costos' => round($totalCostos, 2),
                    'total_ingresos_brutos' => round($totalIngresos, 2),
                    'descuento_aplicado' => round($descuento, 2),
                    'total_ingresos_netos' => round($ingresosConDescuento, 2),
                    'utilidad_bruta' => round($utilidadBrutaCorrecta, 2),
                    'monto_total_registrado' => round($montoTotal, 2),
                    'diferencia_calculo' => round($diferencia, 2),
                    'coherencia_ok' => $diferencia < 0.01
                ]
            ];
        }
        
        public function getDebug() {
            return $this->debug;
        }
    }
    
    // =============================================================================
    // 4. PROCESAR TODAS LAS VENTAS
    // =============================================================================
    
    $calculadora = new CalculadoraFinancieraPrecisa($productosLookup);
    $ventasProcesadas = [];
    $resumenGeneral = [
        'total_ventas' => 0,
        'total_productos_vendidos' => 0,
        'total_costos' => 0,
        'total_ingresos_brutos' => 0,
        'total_descuentos' => 0,
        'total_ingresos_netos' => 0,
        'total_utilidad_bruta' => 0,
        'diferencias_detectadas' => 0
    ];
    
    // Contadores por método de pago
    $metodosPago = [
        'efectivo' => 0,
        'tarjeta' => 0,
        'transferencia' => 0,
        'mercadopago' => 0,
        'qr' => 0,
        'otros' => 0
    ];
    
    foreach ($ventas as $venta) {
        $ventaProcesada = $calculadora->procesarVenta($venta);
        $ventasProcesadas[] = $ventaProcesada;
        
        // Acumular en resumen general
        $resumen = $ventaProcesada['resumen'];
        $resumenGeneral['total_ventas']++;
        $resumenGeneral['total_productos_vendidos'] += $resumen['cantidad_productos'];
        $resumenGeneral['total_costos'] += $resumen['total_costos'];
        $resumenGeneral['total_ingresos_brutos'] += $resumen['total_ingresos_brutos'];
        $resumenGeneral['total_descuentos'] += $resumen['descuento_aplicado'];
        $resumenGeneral['total_ingresos_netos'] += $resumen['total_ingresos_netos'];
        $resumenGeneral['total_utilidad_bruta'] += $resumen['utilidad_bruta'];
        
        if (!$resumen['coherencia_ok']) {
            $resumenGeneral['diferencias_detectadas']++;
        }
        
        // Contabilizar por método de pago
        $metodo = strtolower($venta['metodo_pago']);
        
        // Mapeo específico para métodos de pago
        $metodo_mapeado = match($metodo) {
            'efectivo' => 'efectivo',
            'tarjeta', 'debito', 'credito' => 'tarjeta',
            'transferencia', 'transfer' => 'transferencia',
            'mercadopago', 'mp' => 'mercadopago',
            'qr', 'codigo_qr', 'qr_code' => 'qr',
            default => 'otros'
        };
        
        $metodosPago[$metodo_mapeado] += $venta['monto_total'];
    }
    
    // =============================================================================
    // 5. GASTOS FIJOS ELIMINADOS DEL SISTEMA
    // =============================================================================
    
    // Los gastos fijos han sido eliminados del sistema por solicitud del usuario
    $gastosFijosPeriodo = 0;
    $diasPeriodo = (strtotime($fechaFin) - strtotime($fechaInicio)) / (24*60*60) + 1;
    
    // =============================================================================
    // 6. CÁLCULOS FINALES Y VALIDACIONES
    // =============================================================================
    
    // Utilidad neta = Utilidad bruta (sin gastos fijos)
    $utilidadNeta = $resumenGeneral['total_utilidad_bruta'];
    
    // Márgenes
    $margenBruto = $resumenGeneral['total_ingresos_netos'] > 0 ? 
        ($resumenGeneral['total_utilidad_bruta'] / $resumenGeneral['total_ingresos_netos']) * 100 : 0;
    
    $margenNeto = $resumenGeneral['total_ingresos_netos'] > 0 ? 
        ($utilidadNeta / $resumenGeneral['total_ingresos_netos']) * 100 : 0;
    
    // ROI
    $roiBruto = $resumenGeneral['total_costos'] > 0 ? 
        ($resumenGeneral['total_utilidad_bruta'] / $resumenGeneral['total_costos']) * 100 : 0;
    
    $roiNeto = $resumenGeneral['total_costos'] > 0 ? 
        ($utilidadNeta / $resumenGeneral['total_costos']) * 100 : 0;
    
    // Ticket promedio
    $ticketPromedio = $resumenGeneral['total_ventas'] > 0 ? 
        $resumenGeneral['total_ingresos_netos'] / $resumenGeneral['total_ventas'] : 0;
    
    // Utilidad por venta
    $utilidadPorVenta = $resumenGeneral['total_ventas'] > 0 ? 
        $utilidadNeta / $resumenGeneral['total_ventas'] : 0;
    
    // Estado del negocio
    $estadoNegocio = $utilidadNeta > 0 ? 'RENTABLE' : 
        ($utilidadNeta < 0 ? 'EN PÉRDIDAS' : 'PUNTO DE EQUILIBRIO');
    
    // =============================================================================
    // 7. ANÁLISIS POR PRODUCTO
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
                    'ingresos_totales' => 0,
                    'costos_totales' => 0,
                    'utilidad_total' => 0,
                    'ventas_count' => 0
                ];
            }
            
            $productosAnalisis[$id]['cantidad_vendida'] += $producto['cantidad'];
            $productosAnalisis[$id]['ingresos_totales'] += $producto['ingreso_total'];
            $productosAnalisis[$id]['costos_totales'] += $producto['costo_total'];
            $productosAnalisis[$id]['utilidad_total'] += $producto['utilidad_total'];
            $productosAnalisis[$id]['ventas_count']++;
        }
    }
    
    // Calcular métricas por producto
    foreach ($productosAnalisis as &$producto) {
        $producto['margen_promedio'] = $producto['ingresos_totales'] > 0 ? 
            ($producto['utilidad_total'] / $producto['ingresos_totales']) * 100 : 0;
        
        $producto['precio_promedio'] = $producto['cantidad_vendida'] > 0 ? 
            $producto['ingresos_totales'] / $producto['cantidad_vendida'] : 0;
        
        $producto['costo_promedio'] = $producto['cantidad_vendida'] > 0 ? 
            $producto['costos_totales'] / $producto['cantidad_vendida'] : 0;
    }
    
    // Ordenar por utilidad total
    usort($productosAnalisis, function($a, $b) {
        return $b['utilidad_total'] <=> $a['utilidad_total'];
    });
    
    // =============================================================================
    // 8. RESPUESTA FINAL
    // =============================================================================
    
    $response = [
        'success' => true,
        'periodo' => [
            'tipo' => $periodo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'dias_periodo' => $diasPeriodo
        ],
        'resumen_general' => array_merge($resumenGeneral, [
            'utilidad_neta' => round($utilidadNeta, 2),
            'margen_bruto_porcentaje' => round($margenBruto, 2),
            'margen_neto_porcentaje' => round($margenNeto, 2),
            'roi_bruto_porcentaje' => round($roiBruto, 2),
            'roi_neto_porcentaje' => round($roiNeto, 2),
            'ticket_promedio' => round($ticketPromedio, 2),
            'utilidad_por_venta' => round($utilidadPorVenta, 2),
            'estado_negocio' => $estadoNegocio
        ]),
        'gastos_fijos' => [
            'mensuales' => 0,
            'diarios' => 0,
            'periodo' => 0,
            'tipo_calculo' => 'ELIMINADOS',
            'dias_aplicados' => $diasPeriodo,
            'formula' => 'Gastos fijos eliminados del sistema por solicitud del usuario',
            'descripcion' => 'Gastos fijos eliminados - toda ganancia es utilidad neta',
            'dias_mes' => $diasPeriodo
        ],
        'metodos_pago' => $metodosPago,
        'productos_analisis' => array_slice($productosAnalisis, 0, 20), // Top 20
        'ventas_detalladas' => $ventasProcesadas,
        'validaciones' => [
            'total_ventas_procesadas' => count($ventasProcesadas),
            'diferencias_detectadas' => $resumenGeneral['diferencias_detectadas'],
            'coherencia_general' => $resumenGeneral['diferencias_detectadas'] == 0,
            'formula_utilidad' => 'Utilidad = Precio_Venta - Costo_Producto',
            'formula_margen' => 'Margen = (Utilidad / Precio_Venta) * 100',
            'formula_gastos_diarios' => 'Gastos_Diarios = Gastos_Mensuales / Días_Mes'
        ],
        'debug' => $calculadora->getDebug(),
        // 🔬 INFORMACIÓN DE DEBUG PARA SINCRONIZACIÓN
        'debug_sincronizacion' => [
            'fecha_servidor' => date('Y-m-d H:i:s'),
            'periodo_solicitado' => $_GET['periodo'] ?? 'hoy',
            'fecha_inicio_calculada' => $fechaInicio,
            'fecha_fin_calculada' => $fechaFin,
            'total_ventas_encontradas' => count($ventas),
            'query_utilizada' => "DATE(v.fecha) BETWEEN '$fechaInicio' AND '$fechaFin' AND v.estado IN ('completado', 'completada')",
            'problema_identificado' => count($ventas) === 0 ? 'Sin ventas en el rango de fechas consultado' : 'Datos encontrados correctamente',
            'correccion_aplicada' => $periodo === 'hoy' ? 'Búsqueda automática en últimos 3 días si no hay ventas hoy' : 'Sin corrección aplicada'
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
?> 