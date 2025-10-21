<?php
/**
 * 🚀 API OPTIMIZADA DE PRODUCTOS PARA PUNTO DE VENTA
 * 
 * Endpoint especializado para POS con:
 * - Filtrado inteligente de stock cero
 * - Alertas de stock bajo
 * - Performance optimizada para alto tráfico
 * - Sincronización en tiempo real
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';
require_once 'cache_manager_pos.php';
require_once 'pricing_engine.php';

class ProductosPOSOptimizado {
    
    private $pdo;
    private $cache;
    private $stockMinimoDefault = 3; // Stock bajo <= 3 unidades
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        if (!$this->pdo) {
            throw new Exception('Error de conexión a la base de datos');
        }
        
        // Inicializar cache manager
        $this->cache = POSCacheManager::getInstance()->getCache();
    }
    
    /**
     * 🎯 OBTENER PRODUCTOS OPTIMIZADOS PARA POS
     */
    public function getProductosParaPOS() {
        $startTime = microtime(true);
        
        try {
            // 💾 VERIFICAR CACHE PRIMERO
            $filtrosCache = [
                'incluir_sin_stock' => filter_var($_GET['incluir_sin_stock'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
                'solo_stock_bajo' => filter_var($_GET['solo_stock_bajo'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
                'busqueda' => trim($_GET['search'] ?? ''),
                'categoria' => trim($_GET['categoria'] ?? ''),
                'limite' => min(500, max(1, (int)($_GET['limite'] ?? 100))),
                'offset' => max(0, (int)($_GET['offset'] ?? 0))
            ];
            
            // Verificar cache si no es una búsqueda específica
            $useCache = empty($filtrosCache['busqueda']) && $filtrosCache['limite'] <= 100;
            
            if ($useCache) {
                $cachedData = $this->cache->getProductosPOS($filtrosCache);
                if ($cachedData) {
                    $cachedData['cache_hit'] = true;
                    $cachedData['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
                    return $cachedData;
                }
            }
            // Parámetros de entrada con valores por defecto optimizados
            $incluirSinStock = filter_var($_GET['incluir_sin_stock'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
            $soloStockBajo = filter_var($_GET['solo_stock_bajo'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
            $busqueda = trim($_GET['search'] ?? '');
            $categoria = trim($_GET['categoria'] ?? '');
            $limite = min(500, max(1, (int)($_GET['limite'] ?? 100))); // Máximo 500 productos por request
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            
            // Query base optimizada con índices
            $query = "
                SELECT 
                    p.id,
                    p.nombre,
                    p.codigo,
                    p.barcode,
                    p.precio_venta,
                    p.precio_costo,
                    p.stock,
                    p.stock_actual,
                    p.categoria,
                    p.descripcion,
                    p.activo,
                    COALESCE(p.stock_minimo, ?) as stock_minimo,
                    
                    -- ⚡ CÁLCULOS OPTIMIZADOS DE ESTADO
                    CASE 
                        WHEN p.stock <= 0 THEN 'sin_stock'
                        WHEN p.stock <= COALESCE(p.stock_minimo, ?) THEN 'stock_bajo'
                        WHEN p.stock <= (COALESCE(p.stock_minimo, ?) * 2) THEN 'stock_medio'
                        ELSE 'stock_normal'
                    END as estado_stock,
                    
                    -- 🚨 ALERTAS DE STOCK
                    CASE 
                        WHEN p.stock <= 0 THEN 'SIN_STOCK'
                        WHEN p.stock <= COALESCE(p.stock_minimo, ?) THEN 'STOCK_BAJO'
                        WHEN p.stock <= (COALESCE(p.stock_minimo, ?) * 1.5) THEN 'STOCK_CRITICO'
                        ELSE 'STOCK_OK'
                    END as alerta_stock,
                    
                    -- 📊 INFORMACIÓN ADICIONAL
                    (p.stock * p.precio_costo) as valor_inventario,
                    p.updated_at,
                    
                    -- 🎯 PRIORIDAD PARA ORDENAMIENTO
                    CASE 
                        WHEN p.stock > COALESCE(p.stock_minimo, ?) THEN 1
                        WHEN p.stock > 0 THEN 2
                        ELSE 3
                    END as prioridad_stock
                    
                FROM productos p
                WHERE p.activo = 1
            ";
            
            $params = [
                $this->stockMinimoDefault, // stock_minimo
                $this->stockMinimoDefault, // estado_stock
                $this->stockMinimoDefault, // estado_stock (stock_medio)
                $this->stockMinimoDefault, // alerta_stock STOCK_BAJO
                $this->stockMinimoDefault, // alerta_stock STOCK_CRITICO
                $this->stockMinimoDefault  // prioridad_stock
            ];
            
            // 🚫 FILTRO DE STOCK CERO (comportamiento por defecto para POS)
            if (!$incluirSinStock) {
                $query .= " AND p.stock > 0";
            }
            
            // 🔍 FILTRO DE SOLO STOCK BAJO
            if ($soloStockBajo) {
                $query .= " AND p.stock > 0 AND p.stock <= COALESCE(p.stock_minimo, ?)";
                $params[] = $this->stockMinimoDefault;
            }
            
            // 🔍 FILTRO DE BÚSQUEDA OPTIMIZADO
            if (!empty($busqueda)) {
                $busqueda = '%' . $busqueda . '%';
                $query .= " AND (
                    p.nombre LIKE ? OR 
                    p.codigo LIKE ? OR 
                    p.barcode LIKE ? OR
                    p.descripcion LIKE ?
                )";
                $params = array_merge($params, [$busqueda, $busqueda, $busqueda, $busqueda]);
            }
            
            // 📂 FILTRO DE CATEGORÍA
            if (!empty($categoria) && $categoria !== 'todas') {
                $query .= " AND p.categoria = ?";
                $params[] = $categoria;
            }
            
            // 📈 ORDENAMIENTO INTELIGENTE PARA POS
            $query .= " 
                ORDER BY 
                    prioridad_stock ASC,
                    p.stock DESC,
                    p.nombre ASC
                LIMIT ? OFFSET ?
            ";
            $params[] = $limite;
            $params[] = $offset;
            
            // Ejecutar query principal
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 📊 ENRIQUECER DATOS PARA POS
            $productos = $this->enriquecerProductosParaPOS($productos);
            
            // 💰 APLICAR DYNAMIC PRICING (si está activo)
            $productos = $this->aplicarDynamicPricing($productos);
            
            // 📈 ESTADÍSTICAS DE STOCK
            $estadisticas = $this->obtenerEstadisticasStock($incluirSinStock);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $response = [
                'success' => true,
                'data' => $productos,
                'estadisticas' => $estadisticas,
                'cache_hit' => false,
                'meta' => [
                    'total_productos' => count($productos),
                    'incluir_sin_stock' => $incluirSinStock,
                    'solo_stock_bajo' => $soloStockBajo,
                    'limite' => $limite,
                    'offset' => $offset,
                    'execution_time_ms' => round($executionTime, 2),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            // 💾 GUARDAR EN CACHE
            if ($useCache && count($productos) > 0) {
                $cacheTTL = empty($filtrosCache['busqueda']) ? 180 : 60; // 3 min normal, 1 min búsquedas
                $this->cache->cacheProductosPOS($filtrosCache, $response, $cacheTTL);
                
                // Cache estadísticas por separado
                $this->cache->cacheEstadisticasStock($estadisticas, 120);
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Error en ProductosPOSOptimizado: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al obtener productos: ' . $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }
    
    /**
     * 🎯 ENRIQUECER PRODUCTOS CON INFORMACIÓN POS
     */
    private function enriquecerProductosParaPOS($productos) {
        foreach ($productos as &$producto) {
            // 🚨 CONFIGURAR ALERTAS VISUALES
            $producto['alertas_visuales'] = $this->generarAlertasVisuales($producto);
            
            // 📊 INFORMACIÓN DE STOCK DETALLADA
            $producto['stock_info'] = [
                'cantidad' => (int)$producto['stock'],
                'estado' => $producto['estado_stock'],
                'alerta' => $producto['alerta_stock'],
                'puede_vender' => $producto['stock'] > 0,
                'stock_minimo' => (int)$producto['stock_minimo'],
                'dias_stock_estimado' => $this->calcularDiasStockEstimado($producto)
            ];
            
            // 💰 INFORMACIÓN FINANCIERA
            $producto['precio_info'] = [
                'precio_venta' => (float)$producto['precio_venta'],
                'precio_costo' => (float)$producto['precio_costo'],
                'margen' => $this->calcularMargen($producto),
                'valor_inventario' => (float)$producto['valor_inventario']
            ];
            
            // 🎯 OPTIMIZACIÓN PARA FRONTEND
            $producto['search_terms'] = $this->generarTerminosBusqueda($producto);
        }
        
        return $productos;
    }
    
    /**
     * 🚨 GENERAR ALERTAS VISUALES PARA FRONTEND
     */
    private function generarAlertasVisuales($producto) {
        $stock = (int)$producto['stock'];
        $stockMinimo = (int)$producto['stock_minimo'];
        
        $alertas = [
            'mostrar_badge' => false,
            'tipo_badge' => '',
            'mensaje' => '',
            'color_badge' => '',
            'icono' => '',
            'css_classes' => [],
            'prioridad' => 0
        ];
        
        if ($stock <= 0) {
            $alertas = [
                'mostrar_badge' => true,
                'tipo_badge' => 'sin_stock',
                'mensaje' => 'Sin Stock',
                'color_badge' => 'bg-red-500 text-white',
                'icono' => 'AlertCircle',
                'css_classes' => ['border-red-300', 'bg-red-50', 'opacity-75'],
                'prioridad' => 3
            ];
        } elseif ($stock <= $stockMinimo) {
            $alertas = [
                'mostrar_badge' => true,
                'tipo_badge' => 'stock_bajo',
                'mensaje' => "Stock Bajo ({$stock})",
                'color_badge' => 'bg-yellow-500 text-white',
                'icono' => 'AlertTriangle',
                'css_classes' => ['border-yellow-300', 'bg-yellow-50'],
                'prioridad' => 2
            ];
        } elseif ($stock <= ($stockMinimo * 1.5)) {
            $alertas = [
                'mostrar_badge' => true,
                'tipo_badge' => 'stock_critico',
                'mensaje' => "¡Últimas {$stock} unidades!",
                'color_badge' => 'bg-orange-500 text-white',
                'icono' => 'Clock',
                'css_classes' => ['border-orange-300'],
                'prioridad' => 1
            ];
        }
        
        return $alertas;
    }
    
    /**
     * 📊 OBTENER ESTADÍSTICAS GENERALES DE STOCK
     */
    private function obtenerEstadisticasStock($incluirSinStock = false) {
        $query = "
            SELECT 
                COUNT(*) as total_productos,
                SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as sin_stock,
                SUM(CASE WHEN stock > 0 AND stock <= COALESCE(stock_minimo, ?) THEN 1 ELSE 0 END) as stock_bajo,
                SUM(CASE WHEN stock > COALESCE(stock_minimo, ?) THEN 1 ELSE 0 END) as stock_normal,
                SUM(stock * precio_costo) as valor_total_inventario,
                AVG(stock) as stock_promedio
            FROM productos 
            WHERE activo = 1
        ";
        
        if (!$incluirSinStock) {
            $query .= " AND stock > 0";
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$this->stockMinimoDefault, $this->stockMinimoDefault]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_productos' => (int)$stats['total_productos'],
            'sin_stock' => (int)$stats['sin_stock'],
            'stock_bajo' => (int)$stats['stock_bajo'],
            'stock_normal' => (int)$stats['stock_normal'],
            'valor_total_inventario' => (float)$stats['valor_total_inventario'],
            'stock_promedio' => round((float)$stats['stock_promedio'], 2),
            'porcentaje_sin_stock' => $stats['total_productos'] > 0 ? round(($stats['sin_stock'] / $stats['total_productos']) * 100, 1) : 0,
            'porcentaje_stock_bajo' => $stats['total_productos'] > 0 ? round(($stats['stock_bajo'] / $stats['total_productos']) * 100, 1) : 0
        ];
    }
    
    /**
     * 📈 CALCULAR DÍAS DE STOCK ESTIMADO
     */
    private function calcularDiasStockEstimado($producto) {
        // Implementación básica - se puede mejorar con histórico de ventas
        $stock = (int)$producto['stock'];
        $stockMinimo = (int)$producto['stock_minimo'];
        
        if ($stock <= 0) return 0;
        if ($stockMinimo <= 0) return null;
        
        // Estimación simple: stock / consumo_diario_estimado
        $consumoDiarioEstimado = max(1, $stockMinimo / 7); // Asumimos que stock_minimo es para 1 semana
        return round($stock / $consumoDiarioEstimado);
    }
    
    /**
     * 💰 CALCULAR MARGEN DE GANANCIA
     */
    private function calcularMargen($producto) {
        $costo = (float)$producto['precio_costo'];
        $venta = (float)$producto['precio_venta'];
        
        if ($costo <= 0) return 0;
        
        return round((($venta - $costo) / $costo) * 100, 2);
    }
    
    /**
     * 🔍 GENERAR TÉRMINOS DE BÚSQUEDA OPTIMIZADOS
     */
    private function generarTerminosBusqueda($producto) {
        $terminos = [];
        
        if (!empty($producto['nombre'])) $terminos[] = strtolower($producto['nombre']);
        if (!empty($producto['codigo'])) $terminos[] = strtolower($producto['codigo']);
        if (!empty($producto['barcode'])) $terminos[] = strtolower($producto['barcode']);
        if (!empty($producto['categoria'])) $terminos[] = strtolower($producto['categoria']);
        
        return implode(' ', $terminos);
    }
    
    /**
     * 💰 APLICAR DYNAMIC PRICING (precios dinámicos basados en tiempo)
     * 
     * Aplica reglas de ajuste de precios según día/hora
     * Ej: bebidas alcohólicas +10% viernes y sábados desde las 18:00
     */
    private function aplicarDynamicPricing($productos) {
        try {
            // Cargar configuración de pricing
            $pricingConfig = require __DIR__ . '/pricing_config.php';
            
            // Si el sistema está desactivado, retornar sin cambios
            if (!isset($pricingConfig['enabled']) || !$pricingConfig['enabled']) {
                return $productos;
            }
            
            // Aplicar reglas a todos los productos
            $productosConPricing = [];
            foreach ($productos as $producto) {
                // Preparar datos para el engine
                $productoParaPricing = [
                    'id' => $producto['id'] ?? null,
                    'codigo_barras' => $producto['barcode'] ?? '',
                    'categoria_slug' => $this->slugify($producto['categoria'] ?? ''),
                    'precio' => $producto['precio_venta'] ?? 0,
                    'nombre' => $producto['nombre'] ?? '',
                ];
                
                // Aplicar reglas
                $productoAjustado = PricingEngine::applyPricingRules($productoParaPricing, $pricingConfig);
                
                // Actualizar precio en el producto original si hubo ajuste
                if (isset($productoAjustado['pricing']) && $productoAjustado['pricing']['ajuste_aplicado']) {
                    $producto['precio_venta'] = $productoAjustado['precio'];
                    $producto['precio_info']['precio_venta'] = (float)$productoAjustado['precio'];
                    
                    // Agregar metadata de pricing
                    $producto['dynamic_pricing'] = [
                        'activo' => true,
                        'precio_original' => $productoAjustado['pricing']['precio_original'],
                        'precio_ajustado' => $productoAjustado['pricing']['precio_ajustado'],
                        'porcentaje_incremento' => $productoAjustado['pricing']['porcentaje'],
                        'regla_aplicada' => $productoAjustado['pricing']['regla_nombre'],
                        'regla_id' => $productoAjustado['pricing']['regla_id'],
                    ];
                } else {
                    // Sin ajuste
                    $producto['dynamic_pricing'] = [
                        'activo' => false,
                        'precio_original' => $producto['precio_venta'],
                        'precio_ajustado' => null,
                    ];
                }
                
                $productosConPricing[] = $producto;
            }
            
            return $productosConPricing;
            
        } catch (Exception $e) {
            // Si falla el pricing, retornar productos sin cambios
            error_log("Error en aplicarDynamicPricing: " . $e->getMessage());
            return $productos;
        }
    }
    
    /**
     * Convierte un string a slug (para matching de categorías)
     */
    private function slugify($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
    
    /**
     * 🚀 INVALIDAR CACHE CUANDO CAMBIE EL STOCK
     */
    public function invalidarCacheStock() {
        return $this->cache->invalidarCacheProductos();
    }
    
    /**
     * 🔄 VERIFICAR STOCK EN TIEMPO REAL
     */
    public function verificarStockTiempoReal() {
        $productosIds = $_GET['productos_ids'] ?? '';
        
        if (empty($productosIds)) {
            return ['success' => false, 'error' => 'IDs de productos requeridos'];
        }
        
        $ids = explode(',', $productosIds);
        $ids = array_filter(array_map('intval', $ids));
        
        if (empty($ids)) {
            return ['success' => false, 'error' => 'IDs de productos inválidos'];
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $query = "
            SELECT 
                id, 
                stock, 
                CASE 
                    WHEN stock <= 0 THEN 'sin_stock'
                    WHEN stock <= COALESCE(stock_minimo, ?) THEN 'stock_bajo'
                    ELSE 'stock_normal'
                END as estado_stock,
                updated_at
            FROM productos 
            WHERE id IN ($placeholders) AND activo = 1
        ";
        
        $params = array_merge([$this->stockMinimoDefault], $ids);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'stocks' => $stocks,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Si hay cambios de stock, invalidar cache relacionado
        if (!empty($stocks)) {
            $this->invalidarCacheStock();
        }
        
        return $response;
    }
}

// 🚀 MAIN EXECUTION
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }
    
    $productosAPI = new ProductosPOSOptimizado();
    
    // Determinar acción
    $accion = $_GET['accion'] ?? 'obtener_productos';
    
    switch ($accion) {
        case 'obtener_productos':
            $resultado = $productosAPI->getProductosParaPOS();
            break;
            
        case 'verificar_stock':
            $resultado = $productosAPI->verificarStockTiempoReal();
            break;
            
        case 'invalidar_cache':
            $invalidated = $productosAPI->invalidarCacheStock();
            $resultado = ['success' => true, 'invalidated_entries' => $invalidated];
            break;
            
        case 'cache_stats':
            $stats = $productosAPI->cache->getStats();
            $resultado = ['success' => true, 'cache_stats' => $stats];
            break;
            
        default:
            $resultado = ['success' => false, 'error' => 'Acción no reconocida'];
    }
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en productos_pos_optimizado.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
