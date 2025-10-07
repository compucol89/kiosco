<?php
/*
 * Catalog Performance Optimizer - Optimización de Rendimiento de Catálogo
 * Mejora performance, paginación y búsqueda para catálogos grandes
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

class CatalogPerformanceOptimizer {
    private $pdo;
    private $cacheManager;
    
    // Configuración de performance
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;
    const CACHE_TTL = 300; // 5 minutos
    const SEARCH_MIN_LENGTH = 2;
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->cacheManager = new CatalogCacheManager();
        $this->createOptimizedIndexes();
    }
    
    /**
     * 📊 OBTENER PRODUCTOS CON PAGINACIÓN OPTIMIZADA
     */
    public function getOptimizedProductList() {
        try {
            $startTime = microtime(true);
            
            // Parámetros de entrada
            $page = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = min(self::MAX_PAGE_SIZE, max(1, (int)($_GET['page_size'] ?? self::DEFAULT_PAGE_SIZE)));
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $sortBy = $_GET['sort_by'] ?? 'nombre';
            $sortOrder = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
            $stockFilter = $_GET['stock_filter'] ?? 'all'; // all, in_stock, out_of_stock, low_stock
            
            // Verificar caché
            $cacheKey = $this->generateCacheKey($page, $pageSize, $search, $category, $sortBy, $sortOrder, $stockFilter);
            $cachedResult = $this->cacheManager->get($cacheKey);
            
            if ($cachedResult && !($_GET['force_refresh'] ?? false)) {
                $cachedResult['cache_hit'] = true;
                $cachedResult['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode($cachedResult);
                return;
            }
            
            // Construir query optimizada
            $queryResult = $this->buildOptimizedQuery($search, $category, $sortBy, $sortOrder, $stockFilter, $page, $pageSize);
            
            $response = [
                'success' => true,
                'data' => [
                    'products' => $queryResult['products'],
                    'pagination' => $queryResult['pagination'],
                    'filters' => $queryResult['filters'],
                    'summary' => $queryResult['summary']
                ],
                'performance' => [
                    'query_time_ms' => $queryResult['query_time_ms'],
                    'total_rows_scanned' => $queryResult['total_rows'],
                    'cache_hit' => false,
                    'optimization_level' => $queryResult['optimization_level']
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
            
            // Guardar en caché si la query fue rápida
            if ($queryResult['query_time_ms'] < 1000) {
                $this->cacheManager->set($cacheKey, $response, self::CACHE_TTL);
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 🔍 CONSTRUIR QUERY OPTIMIZADA
     */
    private function buildOptimizedQuery($search, $category, $sortBy, $sortOrder, $stockFilter, $page, $pageSize) {
        $startTime = microtime(true);
        
        // Campos principales con cálculos optimizados
        $selectFields = "
            p.id,
            p.nombre,
            p.codigo,
            p.barcode,
            p.categoria,
            p.descripcion,
            p.precio_costo,
            p.precio_venta,
            p.stock,
            p.stock_minimo,
            (p.stock * p.precio_costo) as valor_inventario,
            CASE 
                WHEN p.precio_costo > 0 
                THEN ROUND(((p.precio_venta - p.precio_costo) / p.precio_costo) * 100, 2)
                ELSE 0
            END as margen_porcentaje,
            CASE 
                WHEN p.stock = 0 THEN 'sin_stock'
                WHEN p.stock <= COALESCE(p.stock_minimo, 10) THEN 'stock_bajo'
                ELSE 'stock_normal'
            END as estado_stock,
            p.updated_at,
            p.activo
        ";
        
        // Condiciones WHERE optimizadas
        $whereConditions = ['(p.activo = 1 OR p.activo IS NULL)'];
        $params = [];
        
        // Filtro de búsqueda con full-text o LIKE optimizado
        if (!empty($search) && strlen($search) >= self::SEARCH_MIN_LENGTH) {
            if ($this->hasFullTextIndex()) {
                $whereConditions[] = "MATCH(p.nombre, p.descripcion) AGAINST(? IN BOOLEAN MODE)";
                $params[] = $search . '*';
            } else {
                $whereConditions[] = "(p.nombre LIKE ? OR p.codigo LIKE ? OR p.barcode LIKE ? OR p.descripcion LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }
        }
        
        // Filtro de categoría
        if (!empty($category) && $category !== 'todas') {
            $whereConditions[] = "p.categoria = ?";
            $params[] = $category;
        }
        
        // Filtro de stock
        switch ($stockFilter) {
            case 'in_stock':
                $whereConditions[] = "p.stock > 0";
                break;
            case 'out_of_stock':
                $whereConditions[] = "p.stock = 0";
                break;
            case 'low_stock':
                $whereConditions[] = "p.stock > 0 AND p.stock <= COALESCE(p.stock_minimo, 10)";
                break;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Validar campo de ordenamiento
        $allowedSortFields = ['nombre', 'precio_venta', 'precio_costo', 'stock', 'categoria', 'margen_porcentaje', 'valor_inventario', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'nombre';
        }
        
        // Query de conteo optimizada
        $countQuery = "SELECT COUNT(*) FROM productos p WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRows = (int)$countStmt->fetchColumn();
        
        // Calcular paginación
        $totalPages = max(1, ceil($totalRows / $pageSize));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $pageSize;
        
        // Query principal con LIMIT optimizado
        $mainQuery = "
            SELECT $selectFields
            FROM productos p
            WHERE $whereClause
            ORDER BY $sortBy $sortOrder
            LIMIT $pageSize OFFSET $offset
        ";
        
        $stmt = $this->pdo->prepare($mainQuery);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enriquecer productos con datos adicionales si es necesario
        $products = $this->enrichProductData($products);
        
        // Obtener estadísticas de filtros
        $filters = $this->getFilterStatistics($whereConditions, $params);
        
        // Calcular resumen
        $summary = $this->calculateSummary($products, $totalRows);
        
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_pages' => $totalPages,
                'total_records' => $totalRows,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ],
            'filters' => $filters,
            'summary' => $summary,
            'query_time_ms' => $queryTime,
            'total_rows' => $totalRows,
            'optimization_level' => $this->getOptimizationLevel($queryTime, $totalRows)
        ];
    }
    
    /**
     * 🚀 ENRIQUECER DATOS DE PRODUCTOS
     */
    private function enrichProductData($products) {
        foreach ($products as &$product) {
            // Formatear precios
            $product['precio_costo'] = round((float)$product['precio_costo'], 2);
            $product['precio_venta'] = round((float)$product['precio_venta'], 2);
            $product['valor_inventario'] = round((float)$product['valor_inventario'], 2);
            
            // Agregar URL de imagen optimizada
            $product['imagen_url'] = $this->getOptimizedImageUrl($product);
            
            // Calcular métricas adicionales
            $product['needs_reorder'] = (int)$product['stock'] <= (int)($product['stock_minimo'] ?? 10);
            $product['margin_status'] = $this->getMarginStatus((float)$product['margen_porcentaje']);
            
            // Formatear fechas
            if ($product['updated_at']) {
                $product['last_updated_human'] = $this->timeAgo($product['updated_at']);
            }
        }
        
        return $products;
    }
    
    /**
     * 📊 OBTENER ESTADÍSTICAS DE FILTROS
     */
    private function getFilterStatistics($baseConditions, $baseParams) {
        // Categorías disponibles
        $categoryQuery = "
            SELECT categoria, COUNT(*) as count 
            FROM productos p 
            WHERE " . implode(' AND ', $baseConditions) . "
            GROUP BY categoria 
            ORDER BY count DESC, categoria
        ";
        $stmt = $this->pdo->prepare($categoryQuery);
        $stmt->execute($baseParams);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estados de stock
        $stockQuery = "
            SELECT 
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock > 0 AND stock <= COALESCE(stock_minimo, 10) THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock > COALESCE(stock_minimo, 10) THEN 1 ELSE 0 END) as normal_stock
            FROM productos p 
            WHERE " . implode(' AND ', $baseConditions);
        $stmt = $this->pdo->prepare($stockQuery);
        $stmt->execute($baseParams);
        $stockStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'categories' => $categories,
            'stock_distribution' => $stockStats
        ];
    }
    
    /**
     * 📈 CALCULAR RESUMEN
     */
    private function calculateSummary($products, $totalRows) {
        $totalValue = 0;
        $totalUnits = 0;
        $avgMargin = 0;
        $marginSum = 0;
        $validMarginCount = 0;
        
        foreach ($products as $product) {
            $totalValue += (float)$product['valor_inventario'];
            $totalUnits += (int)$product['stock'];
            
            $margin = (float)$product['margen_porcentaje'];
            if ($margin > 0) {
                $marginSum += $margin;
                $validMarginCount++;
            }
        }
        
        $avgMargin = $validMarginCount > 0 ? round($marginSum / $validMarginCount, 2) : 0;
        
        return [
            'displayed_products' => count($products),
            'total_products' => $totalRows,
            'total_inventory_value' => round($totalValue, 2),
            'total_units' => $totalUnits,
            'average_margin' => $avgMargin,
            'page_load_optimized' => count($products) <= self::DEFAULT_PAGE_SIZE
        ];
    }
    
    /**
     * 🖼️ OBTENER URL DE IMAGEN OPTIMIZADA
     */
    private function getOptimizedImageUrl($product) {
        $imageId = $product['barcode'] ?: $product['codigo'] ?: 'no-image';
        
        // Priorizar formatos más ligeros
        $formats = ['webp', 'jpg', 'png', 'svg'];
        
        foreach ($formats as $format) {
            $url = "/img/productos/{$imageId}.{$format}";
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $url)) {
                return $url;
            }
        }
        
        return '/img/no-image.svg';
    }
    
    /**
     * 📊 OBTENER ESTADO DEL MARGEN
     */
    private function getMarginStatus($margin) {
        if ($margin < 0) return 'negative';
        if ($margin < 10) return 'low';
        if ($margin > 200) return 'excessive';
        return 'normal';
    }
    
    /**
     * ⏰ TIEMPO TRANSCURRIDO LEGIBLE
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'hace ' . $time . ' segundos';
        if ($time < 3600) return 'hace ' . round($time/60) . ' minutos';
        if ($time < 86400) return 'hace ' . round($time/3600) . ' horas';
        return 'hace ' . round($time/86400) . ' días';
    }
    
    /**
     * 🔍 CREAR ÍNDICES OPTIMIZADOS
     */
    private function createOptimizedIndexes() {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_productos_active_name ON productos(activo, nombre)",
            "CREATE INDEX IF NOT EXISTS idx_productos_category_stock ON productos(categoria, stock)",
            "CREATE INDEX IF NOT EXISTS idx_productos_search_fields ON productos(nombre(50), codigo, barcode)",
            "CREATE INDEX IF NOT EXISTS idx_productos_pricing ON productos(precio_costo, precio_venta)",
            "CREATE INDEX IF NOT EXISTS idx_productos_stock_status ON productos(stock, stock_minimo)",
            "CREATE INDEX IF NOT EXISTS idx_productos_updated ON productos(updated_at)",
            // Full-text index para búsqueda avanzada
            "CREATE FULLTEXT INDEX IF NOT EXISTS idx_productos_fulltext ON productos(nombre, descripcion)"
        ];
        
        foreach ($indexes as $index) {
            try {
                $this->pdo->exec($index);
            } catch (Exception $e) {
                // Index ya existe o error, continuar
            }
        }
    }
    
    /**
     * 🔍 VERIFICAR ÍNDICE FULL-TEXT
     */
    private function hasFullTextIndex() {
        try {
            $stmt = $this->pdo->query("SHOW INDEX FROM productos WHERE Key_name = 'idx_productos_fulltext'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 🔑 GENERAR CLAVE DE CACHÉ
     */
    private function generateCacheKey($page, $pageSize, $search, $category, $sortBy, $sortOrder, $stockFilter) {
        return 'catalog_' . md5(implode('_', [$page, $pageSize, $search, $category, $sortBy, $sortOrder, $stockFilter]));
    }
    
    /**
     * 📈 OBTENER NIVEL DE OPTIMIZACIÓN
     */
    private function getOptimizationLevel($queryTime, $totalRows) {
        if ($queryTime < 100) return 'excellent';
        if ($queryTime < 500) return 'good';
        if ($queryTime < 1000) return 'acceptable';
        return 'needs_optimization';
    }
    
    /**
     * 🗃️ BÚSQUEDA AVANZADA CON AUTOCOMPLETADO
     */
    public function getSearchSuggestions() {
        try {
            $query = trim($_GET['q'] ?? '');
            
            if (strlen($query) < self::SEARCH_MIN_LENGTH) {
                echo json_encode(['success' => true, 'suggestions' => []]);
                return;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT nombre, codigo, categoria
                FROM productos 
                WHERE (nombre LIKE ? OR codigo LIKE ?) 
                AND (activo = 1 OR activo IS NULL)
                ORDER BY 
                    CASE WHEN nombre LIKE ? THEN 1 ELSE 2 END,
                    nombre
                LIMIT 10
            ");
            
            $searchParam = $query . '%';
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'suggestions' => $suggestions
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

/**
 * 💾 GESTOR DE CACHÉ PARA CATÁLOGO
 */
class CatalogCacheManager {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = sys_get_temp_dir() . '/catalog_cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->cacheDir . $key . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['content'];
    }
    
    public function set($key, $content, $ttl) {
        $file = $this->cacheDir . $key . '.cache';
        $data = [
            'content' => $content,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($data));
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// Procesar solicitud
$optimizer = new CatalogPerformanceOptimizer();

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $optimizer->getOptimizedProductList();
        break;
    case 'search':
        $optimizer->getSearchSuggestions();
        break;
    case 'clear_cache':
        $cacheManager = new CatalogCacheManager();
        $cacheManager->clear();
        echo json_encode(['success' => true, 'message' => 'Cache cleared']);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no reconocida']);
}
?>