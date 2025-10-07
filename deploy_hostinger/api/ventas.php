<?php
// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una solicitud OPTIONS (preflight), respondemos exitosamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivo de configuración
require_once 'config.php';

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Solo permitir GET
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

// Obtener productos para generar datos simulados
try {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error al obtener productos: ' . $e->getMessage()]);
    exit;
}

// Función para generar ventas simuladas
function generarVentasSimuladas($productos, $cantidad, $minCantidad, $maxCantidad) {
    $ventas = [];
    
    // Mezclar productos
    shuffle($productos);
    $productosSeleccionados = array_slice($productos, 0, min(count($productos), $cantidad));
    
    foreach ($productosSeleccionados as $producto) {
        $cantidadVendida = rand($minCantidad, $maxCantidad);
        $totalVenta = $cantidadVendida * floatval($producto['precio_venta']);
        
        $ventas[] = [
            'id' => $producto['id'],
            'codigo' => $producto['codigo'],
            'nombre' => $producto['nombre'],
            'categoria' => $producto['categoria'],
            'cantidad_vendida' => $cantidadVendida,
            'precio_venta' => floatval($producto['precio_venta']),
            'total_venta' => $totalVenta
        ];
    }
    
    // Ordenar por cantidad vendida (descendente)
    usort($ventas, function($a, $b) {
        return $b['cantidad_vendida'] - $a['cantidad_vendida'];
    });
    
    return $ventas;
}

// Procesar diferentes tipos de solicitudes
$pathInfo = $_SERVER['PATH_INFO'] ?? '';

// Productos más vendidos (día/mes)
if (strpos($pathInfo, '/productos-mas-vendidos') !== false) {
    $periodo = $_GET['periodo'] ?? 'dia';
    
    if ($periodo === 'dia') {
        $ventasDia = generarVentasSimuladas($productos, 5, 1, 10);
        echo json_encode($ventasDia);
    } else {
        $ventasMes = generarVentasSimuladas($productos, 8, 10, 100);
        echo json_encode($ventasMes);
    }
    exit;
}

// Ventas de la última semana
if (strpos($pathInfo, '/ultimas-semana') !== false) {
    $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $ventasSemana = [];
    
    foreach ($diasSemana as $dia) {
        // Generar una cantidad de ventas entre 3 y 7 productos
        $cantidadProductos = rand(3, 7);
        $productosDelDia = array_slice($productos, rand(0, count($productos) - $cantidadProductos), $cantidadProductos);
        
        // Calcular el total del día
        $totalDia = 0;
        foreach ($productosDelDia as $producto) {
            $cantidad = rand(1, 5);
            $totalDia += $cantidad * floatval($producto['precio_venta']);
        }
        
        $ventasSemana[] = [
            'day' => $dia,
            'amount' => round($totalDia)
        ];
    }
    
    echo json_encode($ventasSemana);
    exit;
}

// Si no coincide con ningún endpoint específico, devolver un error
if (empty($pathInfo) || $pathInfo === '/') {
    // Consultar ventas desde la base de datos
    try {
        $stmt = $pdo->query("SELECT * FROM ventas ORDER BY fecha DESC");
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mostrar solo datos reales de la base de datos
        
        echo json_encode(['success' => true, 'ventas' => $ventas]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener ventas: ' . $e->getMessage()]);
        exit;
    }
}

// Si no coincide con ningún endpoint específico, devolver un error
http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Endpoint no encontrado']); 