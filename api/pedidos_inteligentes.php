<?php
/**
 * api/pedidos_inteligentes.php
 * API de análisis inteligente de pedidos basado en ventas históricas
 * Calcula consumo, predice agotamiento y sugiere cantidades óptimas
 * RELEVANT FILES: api/proveedores.php, api/reportes_financieros_precisos.php
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $accion = $_GET['accion'] ?? 'analizar';
    
    switch ($accion) {
        case 'analizar':
            analizarPedidos($pdo);
            break;
        case 'por_proveedor':
            analizarPorProveedor($pdo);
            break;
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Analizar pedidos agrupados por proveedor
 */
function analizarPorProveedor($pdo) {
    $diasAnalisis = (int)($_GET['dias'] ?? 30);
    $fechaInicio = date('Y-m-d', strtotime("-{$diasAnalisis} days"));
    $fechaFin = date('Y-m-d');
    
    // Obtener todos los productos activos con su proveedor
    $stmtProductos = $pdo->query("
        SELECT 
            id, codigo, nombre, stock, stock_minimo, precio_costo,
            proveedor, categoria, stock_minimo_dias
        FROM productos 
        WHERE activo = 1
        ORDER BY proveedor, nombre
    ");
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener ventas del período
    $stmtVentas = $pdo->prepare("
        SELECT id, fecha, detalles_json
        FROM ventas 
        WHERE DATE(fecha) BETWEEN ? AND ?
        AND estado IN ('completado', 'completada')
    ");
    $stmtVentas->execute([$fechaInicio, $fechaFin]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    
    // Analizar consumo por producto
    $consumoPorProducto = [];
    
    foreach ($ventas as $venta) {
        if (empty($venta['detalles_json'])) continue;
        
        $detalles = json_decode($venta['detalles_json'], true);
        if (!isset($detalles['cart'])) continue;
        
        foreach ($detalles['cart'] as $item) {
            $productoId = $item['id'] ?? null;
            $cantidad = (int)($item['quantity'] ?? $item['cantidad'] ?? 1);
            
            if (!$productoId) continue;
            
            if (!isset($consumoPorProducto[$productoId])) {
                $consumoPorProducto[$productoId] = 0;
            }
            $consumoPorProducto[$productoId] += $cantidad;
        }
    }
    
    // Agrupar por proveedor con análisis inteligente
    $pedidosPorProveedor = [];
    
    foreach ($productos as $producto) {
        $productoId = $producto['id'];
        $proveedor = trim($producto['proveedor']);
        
        // Si no tiene proveedor asignado, usar "Sin Proveedor"
        if (empty($proveedor)) {
            $proveedor = 'Sin Proveedor';
        }
        
        // Inicializar proveedor si no existe
        if (!isset($pedidosPorProveedor[$proveedor])) {
            $pedidosPorProveedor[$proveedor] = [
                'nombre' => $proveedor,
                'productos' => [],
                'total_productos' => 0,
                'total_unidades_sugeridas' => 0
            ];
        }
        
        // Calcular métricas del producto
        $consumoTotal = $consumoPorProducto[$productoId] ?? 0;
        $consumoDiario = $diasAnalisis > 0 ? $consumoTotal / $diasAnalisis : 0;
        $consumoSemanal = $consumoDiario * 7;
        $stockActual = (int)$producto['stock'];
        $stockMinimo = (int)($producto['stock_minimo'] ?? 10);
        $diasStockMinimo = (int)($producto['stock_minimo_dias'] ?? 7);
        
        // Días hasta agotar stock
        $diasHastaAgotar = $consumoDiario > 0 ? $stockActual / $consumoDiario : 999;
        
        // ¿Necesita pedido?
        $necesitaPedido = false;
        $cantidadSugerida = 0;
        $urgencia = 'normal';
        $razon = '';
        
        if ($stockActual <= $stockMinimo) {
            $necesitaPedido = true;
            $urgencia = 'alta';
            $razon = 'Stock por debajo del mínimo';
            // Sugerir para 2 semanas
            $cantidadSugerida = ceil($consumoSemanal * 2) - $stockActual;
        } elseif ($diasHastaAgotar <= $diasStockMinimo) {
            $necesitaPedido = true;
            $urgencia = 'media';
            $razon = "Se agotará en {$diasHastaAgotar} días";
            // Sugerir para 2 semanas
            $cantidadSugerida = ceil($consumoSemanal * 2) - $stockActual;
        }
        
        // Solo agregar si necesita pedido
        if ($necesitaPedido && $cantidadSugerida > 0) {
            $pedidosPorProveedor[$proveedor]['productos'][] = [
                'id' => $productoId,
                'codigo' => $producto['codigo'],
                'nombre' => $producto['nombre'],
                'stock_actual' => $stockActual,
                'stock_minimo' => $stockMinimo,
                'consumo_diario' => round($consumoDiario, 2),
                'consumo_semanal' => round($consumoSemanal, 2),
                'consumo_mensual' => round($consumoDiario * 30, 2),
                'dias_hasta_agotar' => round($diasHastaAgotar, 1),
                'cantidad_sugerida' => (int)$cantidadSugerida,
                'urgencia' => $urgencia,
                'razon' => $razon,
                'costo_unitario' => (float)$producto['precio_costo'],
                'costo_total_pedido' => round($cantidadSugerida * (float)$producto['precio_costo'], 2)
            ];
            
            $pedidosPorProveedor[$proveedor]['total_productos']++;
            $pedidosPorProveedor[$proveedor]['total_unidades_sugeridas'] += $cantidadSugerida;
        }
    }
    
    // Filtrar proveedores sin productos a pedir
    $pedidosPorProveedor = array_filter($pedidosPorProveedor, function($p) {
        return $p['total_productos'] > 0;
    });
    
    // Calcular totales por proveedor
    foreach ($pedidosPorProveedor as &$proveedor) {
        $proveedor['costo_total_proveedor'] = array_sum(
            array_column($proveedor['productos'], 'costo_total_pedido')
        );
        
        // Ordenar productos por urgencia
        usort($proveedor['productos'], function($a, $b) {
            $urgencias = ['alta' => 1, 'media' => 2, 'normal' => 3];
            return ($urgencias[$a['urgencia']] ?? 3) - ($urgencias[$b['urgencia']] ?? 3);
        });
    }
    
    // Convertir a array numerado y ordenar por urgencia
    $pedidosArray = array_values($pedidosPorProveedor);
    
    echo json_encode([
        'success' => true,
        'periodo_analisis' => [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'dias_analizados' => $diasAnalisis
        ],
        'pedidos_por_proveedor' => $pedidosArray,
        'total_proveedores' => count($pedidosArray),
        'resumen_general' => [
            'total_productos_a_pedir' => array_sum(array_column($pedidosArray, 'total_productos')),
            'total_unidades' => array_sum(array_column($pedidosArray, 'total_unidades_sugeridas')),
            'costo_total_estimado' => array_sum(array_column($pedidosArray, 'costo_total_proveedor'))
        ]
    ], JSON_PRETTY_PRINT);
}

/**
 * Análisis general de todos los productos
 */
function analizarPedidos($pdo) {
    $diasAnalisis = (int)($_GET['dias'] ?? 30);
    $fechaInicio = date('Y-m-d', strtotime("-{$diasAnalisis} days"));
    $fechaFin = date('Y-m-d');
    
    // Similar a analizarPorProveedor pero sin agrupar
    analizarPorProveedor($pdo);
}
?>

