<?php
// Habilitar visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeceras para CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivo de configuración
require_once 'config.php';

try {
    // Solo permitir GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Método no permitido. Esta API solo acepta solicitudes GET.");
    }
    
    // Extraer parámetros de filtrado si los hay
    $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'hoy'; // CAMBIO: Por defecto 'hoy' en lugar de 'todas'
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
    
    // Construir la consulta SQL base (solo ventas completadas para consistencia con el dashboard)
    $sql = "SELECT * FROM ventas WHERE estado = 'completado'";
    $params = [];
    
    // Aplicar filtros según los parámetros recibidos
    if ($periodo === 'hoy') {
        $sql .= " AND DATE(fecha) = CURDATE()";
    } else if ($periodo === 'ayer') {
        $sql .= " AND DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } else if ($periodo === 'semana') {
        $sql .= " AND WEEK(fecha, 1) = WEEK(CURDATE(), 1) AND YEAR(fecha) = YEAR(CURDATE())";
    } else if ($periodo === 'mes') {
        $sql .= " AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())";
    } else if ($periodo === 'mes_pasado') {
        $sql .= " AND MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    } else if ($periodo === 'todas') {
        // No agregar filtro de fecha - mostrar todas las ventas completadas
    } else if ($periodo === 'personalizado' && $fecha_inicio && $fecha_fin) {
        $sql .= " AND fecha BETWEEN ? AND ?";
        $params[] = $fecha_inicio . ' 00:00:00';
        $params[] = $fecha_fin . ' 23:59:59';
    }
    
    // Ordenar por fecha descendente (las más recientes primero)
    $sql .= " ORDER BY fecha DESC";
    
    // Preparar y ejecutar la consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Obtener todas las ventas
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada venta, procesar los detalles JSON si existen
    foreach ($ventas as &$venta) {
        if (isset($venta['detalles_json']) && !empty($venta['detalles_json'])) {
            $detalles = json_decode($venta['detalles_json'], true);
            $venta['detalles'] = $detalles;
            // Eliminar el JSON en bruto para no duplicar datos
            unset($venta['detalles_json']);
        }
    }
    
    // Calcular totales para el resumen
    $totalVentas = array_sum(array_column($ventas, 'monto_total'));
    $totalDescuentos = array_sum(array_column($ventas, 'descuento'));
    
    // Agrupar por método de pago
    $metodosPago = [];
    foreach ($ventas as $venta) {
        $metodo = $venta['metodo_pago'];
        if (!isset($metodosPago[$metodo])) {
            $metodosPago[$metodo] = [
                'cantidad' => 0,
                'monto' => 0
            ];
        }
        $metodosPago[$metodo]['cantidad']++;
        $metodosPago[$metodo]['monto'] += floatval($venta['monto_total']);
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'items' => $ventas,
        'total' => count($ventas),
        'resumen' => [
            'total_ventas' => $totalVentas,
            'total_descuentos' => $totalDescuentos,
            'metodos_pago' => $metodosPago
        ]
    ]);
    
} catch (Exception $e) {
    // Registrar error
    error_log("Error listando ventas: " . $e->getMessage());
    
    // Devolver respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener ventas: ' . $e->getMessage()
    ]);
} 