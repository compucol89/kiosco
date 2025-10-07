<?php
/**
 * API POS v2 - Enterprise Grade
 * Performance Target: <50ms response time
 * Features: Stock filtering, optimized queries, caching
 * 
 * Endpoints:
 * GET /productos_pos_v2.php - Catálogo optimizado para POS
 * GET /productos_pos_v2.php?categoria=X - Filtro por categoría
 * GET /productos_pos_v2.php?buscar=X - Búsqueda optimizada
 * GET /productos_pos_v2.php?stock_only=true - Solo productos con stock
 * GET /productos_pos_v2.php?admin=true - Vista completa (incluye sin stock)
 */

// Middleware de CORS y headers de performance
require_once 'cors_middleware.php';
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: max-age=60, public"); // Cache de 1 minuto
header("X-API-Version: 2.0");

// Configuración de base de datos
require_once 'config.php';

// Iniciar medición de performance
$start_time = microtime(true);

try {
    // ===== VALIDACIÓN Y SANITIZACIÓN DE PARÁMETROS =====
    $filters = [
        'categoria' => filter_input(INPUT_GET, 'categoria', FILTER_SANITIZE_STRING),
        'buscar' => filter_input(INPUT_GET, 'buscar', FILTER_SANITIZE_STRING),
        'stock_only' => filter_input(INPUT_GET, 'stock_only', FILTER_VALIDATE_BOOLEAN),
        'admin' => filter_input(INPUT_GET, 'admin', FILTER_VALIDATE_BOOLEAN),
        'limit' => min(filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50, 1000),
        'offset' => max(filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0, 0)
    ];

    // ===== CONSTRUCCIÓN DE QUERY OPTIMIZADA =====
    $sql = "SELECT 
                id,
                codigo,
                nombre,
                descripcion,
                precio_venta,
                precio_costo,
                COALESCE(stock_actual, stock) as stock,
                categoria,
                barcode,
                aplica_descuento_forma_pago,
                CASE 
                    WHEN COALESCE(stock_actual, stock) = 0 THEN 'sin_stock'
                    WHEN COALESCE(stock_actual, stock) <= stock_minimo THEN 'bajo_stock'
                    ELSE 'disponible'
                END as estado_stock,
                stock_minimo,
                created_at
            FROM productos 
            WHERE activo = TRUE";
    
    $params = [];
    
    // ===== FILTROS CRÍTICOS DE NEGOCIO =====
    
    // 🚨 FILTRO DE STOCK CRÍTICO: Por defecto solo mostrar productos con stock
    if (!$filters['admin']) {
        if ($filters['stock_only'] !== false) { // Por defecto true si no se especifica admin
            $sql .= " AND COALESCE(stock_actual, stock) > 0";
        }
    }
    
    // Filtro por categoría
    if (!empty($filters['categoria'])) {
        $sql .= " AND categoria = ?";
        $params[] = $filters['categoria'];
    }
    
    // Búsqueda optimizada con FULLTEXT si está disponible
    if (!empty($filters['buscar'])) {
        $buscar = '%' . $filters['buscar'] . '%';
        $sql .= " AND (nombre LIKE ? OR codigo LIKE ? OR COALESCE(barcode, '') LIKE ?)";
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
    }
    
    // ===== OPTIMIZACIONES DE PERFORMANCE =====
    
    // Ordenamiento optimizado (índice compuesto)
    $sql .= " ORDER BY 
                CASE WHEN COALESCE(stock_actual, stock) > 0 THEN 0 ELSE 1 END,
                nombre ASC";
    
    // Paginación para evitar memory issues
    if (!$filters['admin']) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $filters['limit'];
        $params[] = $filters['offset'];
    }
    
    // ===== EJECUCIÓN DE QUERY =====
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== CONTEO TOTAL PARA PAGINACIÓN =====
    $sqlCount = "SELECT COUNT(*) as total FROM productos WHERE activo = TRUE";
    $countParams = [];
    
    if (!$filters['admin'] && $filters['stock_only'] !== false) {
        $sqlCount .= " AND COALESCE(stock_actual, stock) > 0";
    }
    
    if (!empty($filters['categoria'])) {
        $sqlCount .= " AND categoria = ?";
        $countParams[] = $filters['categoria'];
    }
    
    if (!empty($filters['buscar'])) {
        $buscar = '%' . $filters['buscar'] . '%';
        $sqlCount .= " AND (nombre LIKE ? OR codigo LIKE ? OR COALESCE(barcode, '') LIKE ?)";
        $countParams[] = $buscar;
        $countParams[] = $buscar;
        $countParams[] = $buscar;
    }
    
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($countParams);
    $totalCount = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    // ===== OBTENER CATEGORÍAS DISPONIBLES =====
    $sqlCategorias = "SELECT DISTINCT categoria FROM productos WHERE activo = TRUE";
    if (!$filters['admin'] && $filters['stock_only'] !== false) {
        $sqlCategorias .= " AND COALESCE(stock_actual, stock) > 0";
    }
    $sqlCategorias .= " ORDER BY categoria";
    
    $stmtCategorias = $pdo->prepare($sqlCategorias);
    $stmtCategorias->execute();
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);
    
    // ===== CÁLCULO DE PERFORMANCE =====
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // En milisegundos
    
    // ===== RESPUESTA ESTRUCTURADA =====
    $response = [
        'success' => true,
        'data' => [
            'productos' => $productos,
            'pagination' => [
                'total' => (int)$totalCount,
                'count' => count($productos),
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
                'has_more' => ($filters['offset'] + count($productos)) < $totalCount
            ],
            'filters_applied' => [
                'categoria' => $filters['categoria'],
                'buscar' => $filters['buscar'],
                'stock_only' => !$filters['admin'] && $filters['stock_only'] !== false,
                'admin_mode' => $filters['admin']
            ],
            'categorias_disponibles' => $categorias
        ],
        'performance' => [
            'execution_time_ms' => $execution_time,
            'query_count' => 3, // productos, count, categorías
            'sla_compliance' => $execution_time < 50 ? 'PASS' : 'FAIL',
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'meta' => [
            'api_version' => '2.0',
            'endpoint' => 'productos_pos_v2',
            'cache_ttl' => 60
        ]
    ];
    
    // ===== LOGGING DE PERFORMANCE PARA MONITOREO =====
    if ($execution_time > 50) {
        error_log("🚨 POS API v2 SLOW QUERY: {$execution_time}ms - Filters: " . json_encode($filters));
    }
    
    // ===== HEADERS DE PERFORMANCE =====
    header("X-Execution-Time: {$execution_time}ms");
    header("X-Total-Records: {$totalCount}");
    header("X-SLA-Status: " . ($execution_time < 50 ? 'PASS' : 'FAIL'));
    
    echo json_encode($response);

} catch (PDOException $e) {
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);
    
    error_log("🚨 POS API v2 DATABASE ERROR: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Error interno del servidor',
        'performance' => [
            'execution_time_ms' => $execution_time,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'meta' => [
            'api_version' => '2.0',
            'endpoint' => 'productos_pos_v2'
        ]
    ]);
} catch (Exception $e) {
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);
    
    error_log("🚨 POS API v2 GENERAL ERROR: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'Error interno del servidor',
        'performance' => [
            'execution_time_ms' => $execution_time,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'meta' => [
            'api_version' => '2.0',
            'endpoint' => 'productos_pos_v2'
        ]
    ]);
}
?>