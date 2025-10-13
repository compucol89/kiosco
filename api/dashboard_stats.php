<?php
// API para estadísticas del dashboard del día
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'config.php';

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


try {
    // Obtener parámetro de fecha (por defecto hoy)
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
    $fecha_inicio = $fecha . ' 00:00:00';
    $fecha_fin = $fecha . ' 23:59:59';
    
    // ========== ESTADÍSTICAS DE VENTAS DEL DÍA ==========
    
    // Ventas del día
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as cantidad_ventas,
            COALESCE(SUM(monto_total), 0) as total_ventas,
            COALESCE(SUM(descuento), 0) as total_descuentos,
            COALESCE(AVG(monto_total), 0) as promedio_venta
        FROM ventas 
        WHERE fecha BETWEEN ? AND ? AND (estado = 'completada' OR estado = 'completado' OR estado IS NULL)
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $ventas_hoy = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ventas por método de pago del día
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            COALESCE(SUM(monto_total), 0) as monto_total
        FROM ventas 
        WHERE fecha BETWEEN ? AND ? AND (estado = 'completada' OR estado = 'completado' OR estado IS NULL)
        GROUP BY metodo_pago
        ORDER BY monto_total DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $metodos_pago_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Productos más vendidos del día (análisis desde datos JSON reales)
    $fecha_semana = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = $pdo->prepare("
        SELECT 
            id,
            detalles_json
        FROM ventas 
        WHERE fecha >= ? AND (estado = 'completada' OR estado = 'completado' OR estado IS NULL)
        AND detalles_json IS NOT NULL
    ");
    $stmt->execute([$fecha_semana]);
    $ventas_con_detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar productos desde datos JSON
    $productos_contador = array();
    foreach ($ventas_con_detalles as $venta) {
        $detalles = json_decode($venta['detalles_json'], true);
        
        // Buscar productos en diferentes estructuras posibles
        $items = array();
        if (isset($detalles['cart']) && is_array($detalles['cart'])) {
            $items = $detalles['cart'];
        } elseif (isset($detalles['items']) && is_array($detalles['items'])) {
            $items = $detalles['items'];
        } elseif (isset($detalles['productos']) && is_array($detalles['productos'])) {
            $items = $detalles['productos'];
        }
        
        foreach ($items as $item) {
            $nombre = isset($item['name']) ? $item['name'] : (isset($item['nombre']) ? $item['nombre'] : 'Producto sin nombre');
            $cantidad = isset($item['quantity']) ? $item['quantity'] : (isset($item['cantidad']) ? $item['cantidad'] : 1);
            $precio = isset($item['price']) ? $item['price'] : (isset($item['precio']) ? $item['precio'] : (isset($item['precio_unitario']) ? $item['precio_unitario'] : 0));
            
            $key = $nombre;
            if (!isset($productos_contador[$key])) {
                $codigo = isset($item['codigo']) ? $item['codigo'] : (isset($item['barcode']) ? $item['barcode'] : '');
                $categoria = isset($item['categoria']) ? $item['categoria'] : 'Sin categoría';
                
                $productos_contador[$key] = array(
                    'producto_nombre' => $nombre,
                    'codigo' => $codigo,
                    'categoria' => $categoria,
                    'cantidad_vendida' => 0,
                    'total_vendido' => 0
                );
            }
            
            $productos_contador[$key]['cantidad_vendida'] += $cantidad;
            $productos_contador[$key]['total_vendido'] += ($cantidad * $precio);
        }
    }
    
    // Ordenar por cantidad vendida y tomar top 10
    uasort($productos_contador, function($a, $b) {
        return $b['cantidad_vendida'] - $a['cantidad_vendida'];
    });
    
    // Convertir a array numérico para React
    $productos_mas_vendidos = array_values(array_slice($productos_contador, 0, 10));
    
    // ========== ESTADÍSTICAS DE CAJA ==========
    
    // Estado actual de la caja (desde BD real - TABLA CORRECTA: turnos_caja)
    $stmt = $pdo->prepare("
        SELECT * FROM turnos_caja 
        WHERE estado = 'abierto' 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $caja_abierta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($caja_abierta) {
        $estado_caja = [
            'esta_abierta' => true,
            'fecha_apertura' => $caja_abierta['fecha_apertura'],
            'monto_apertura' => floatval($caja_abierta['monto_apertura']),
            'efectivo_actual' => floatval($caja_abierta['monto_apertura']),
            'total_ingresos' => 0,
            'total_egresos' => 0
        ];
    } else {
        $estado_caja = [
            'esta_abierta' => false,
            'fecha_apertura' => null,
            'monto_apertura' => 0,
            'efectivo_actual' => 0,
            'total_ingresos' => 0,
            'total_egresos' => 0
        ];
    }
    
    // Calcular totales de caja_movimientos
    $stmt = $pdo->prepare("
        SELECT 
            tipo,
            COALESCE(SUM(monto), 0) as total_monto
        FROM caja_movimientos 
        WHERE fecha >= ? 
        GROUP BY tipo
    ");
    $stmt->execute([$fecha_inicio]);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ingresos = 0;
    $egresos = 0;
    
    foreach ($movimientos as $mov) {
        if ($mov['tipo'] === 'ingreso') {
            $ingresos += $mov['total_monto'];
        } else {
            $egresos += $mov['total_monto'];
        }
    }
    
    $estado_caja['total_ingresos'] = $ingresos;
    $estado_caja['total_egresos'] = $egresos;
    $estado_caja['efectivo_actual'] = $estado_caja['monto_apertura'] + $ingresos - $egresos;
    
    // ========== PRODUCTOS CON STOCK BAJO ==========
    
    $stmt = $pdo->prepare("
        SELECT 
            codigo,
            nombre,
            stock,
            categoria,
            stock_minimo
        FROM productos 
        WHERE (stock <= 10 OR stock <= stock_minimo) AND activo = 1
        ORDER BY stock ASC
        LIMIT 10
    ");
    $stmt->execute();
    $productos_stock_bajo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ========== COMPARACIÓN CON AYER ==========
    
    $fecha_ayer = date('Y-m-d', strtotime($fecha . ' -1 day'));
    $fecha_ayer_inicio = $fecha_ayer . ' 00:00:00';
    $fecha_ayer_fin = $fecha_ayer . ' 23:59:59';
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as cantidad_ventas,
            COALESCE(SUM(monto_total), 0) as total_ventas
        FROM ventas 
        WHERE fecha BETWEEN ? AND ? AND (estado = 'completada' OR estado = 'completado' OR estado IS NULL)
    ");
    $stmt->execute([$fecha_ayer_inicio, $fecha_ayer_fin]);
    $ventas_ayer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular porcentajes de cambio
    $cambio_cantidad = 0;
    $cambio_total = 0;
    
    if ($ventas_ayer['cantidad_ventas'] > 0) {
        $cambio_cantidad = (($ventas_hoy['cantidad_ventas'] - $ventas_ayer['cantidad_ventas']) / $ventas_ayer['cantidad_ventas']) * 100;
    }
    
    if ($ventas_ayer['total_ventas'] > 0) {
        $cambio_total = (($ventas_hoy['total_ventas'] - $ventas_ayer['total_ventas']) / $ventas_ayer['total_ventas']) * 100;
    }
    
    // ========== RESUMEN SEMANAL ==========
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(fecha) as fecha,
            COUNT(*) as cantidad_ventas,
            COALESCE(SUM(monto_total), 0) as total_ventas
        FROM ventas 
        WHERE fecha >= DATE_SUB(?, INTERVAL 6 DAY) 
        AND fecha <= ?
        AND (estado = 'completada' OR estado = 'completado' OR estado IS NULL)
        GROUP BY DATE(fecha)
        ORDER BY fecha ASC
    ");
    $stmt->execute([$fecha, $fecha . ' 23:59:59']);
    $ventas_semana = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ========== RESPUESTA CONSOLIDADA ==========
    
    $response = [
        'success' => true,
        'fecha' => $fecha,
        'ventas_hoy' => [
            'cantidad' => intval($ventas_hoy['cantidad_ventas']),
            'total' => floatval($ventas_hoy['total_ventas']),
            'descuentos' => floatval($ventas_hoy['total_descuentos']),
            'promedio' => floatval($ventas_hoy['promedio_venta']),
            'cambio_cantidad_pct' => round($cambio_cantidad, 1),
            'cambio_total_pct' => round($cambio_total, 1)
        ],
        'metodos_pago' => $metodos_pago_hoy,
        'productos_mas_vendidos' => $productos_mas_vendidos,
        'estado_caja' => $estado_caja,
        'productos_stock_bajo' => $productos_stock_bajo,
        'ventas_semana' => $ventas_semana,
        'comparacion_ayer' => [
            'cantidad_ayer' => intval($ventas_ayer['cantidad_ventas']),
            'total_ayer' => floatval($ventas_ayer['total_ventas'])
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage()
    ]);
}
?> 