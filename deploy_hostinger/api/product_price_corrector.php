<?php
/*
 * Product Price Corrector - Sistema Automático de Corrección de Precios
 * Corrige automáticamente anomalías de precios y costos en productos
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

class ProductPriceCorrector {
    private $pdo;
    private $auditLogger;
    
    // Configuración de corrección
    const DEFAULT_MARGIN = 40; // Margen por defecto 40%
    const MIN_MARGIN = 10; // Margen mínimo aceptable
    const MAX_MARGIN = 200; // Margen máximo aceptable
    const DECIMAL_PRECISION = 2;
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->auditLogger = new PriceCorrectionLogger($this->pdo);
    }
    
    /**
     * 🔧 CORRECCIÓN AUTOMÁTICA DE PRECIOS
     */
    public function performAutomaticCorrection($mode = 'safe') {
        try {
            $startTime = microtime(true);
            
            // Obtener productos con problemas
            $problematicProducts = $this->getProblematicProducts();
            
            $corrections = [
                'timestamp' => date('Y-m-d H:i:s'),
                'mode' => $mode,
                'total_products_analyzed' => count($problematicProducts),
                'zero_cost_corrections' => $this->correctZeroCostProducts($problematicProducts, $mode),
                'extreme_margin_corrections' => $this->correctExtremeMargins($problematicProducts, $mode),
                'negative_margin_corrections' => $this->correctNegativeMargins($problematicProducts, $mode),
                'precision_corrections' => $this->correctDecimalPrecision($problematicProducts, $mode),
                'summary' => []
            ];
            
            // Calcular resumen
            $totalCorrections = 
                count($corrections['zero_cost_corrections']['corrected_products']) +
                count($corrections['extreme_margin_corrections']['corrected_products']) +
                count($corrections['negative_margin_corrections']['corrected_products']) +
                count($corrections['precision_corrections']['corrected_products']);
            
            $corrections['summary'] = [
                'total_corrections_made' => $totalCorrections,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'status' => $totalCorrections > 0 ? 'success' : 'no_corrections_needed'
            ];
            
            // Audit logging
            $this->auditLogger->log('AUTOMATIC_PRICE_CORRECTION', 'SUCCESS', $corrections);
            
            echo json_encode([
                'success' => true,
                'corrections' => $corrections
            ]);
            
        } catch (Exception $e) {
            $this->auditLogger->log('AUTOMATIC_PRICE_CORRECTION', 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 📊 OBTENER PRODUCTOS PROBLEMÁTICOS
     */
    private function getProblematicProducts() {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                (p.stock * p.precio_costo) as valor_inventario,
                CASE 
                    WHEN p.precio_costo > 0 
                    THEN ((p.precio_venta - p.precio_costo) / p.precio_costo) * 100
                    ELSE 0
                END as margen_porcentaje,
                (p.precio_venta - p.precio_costo) as margen_absoluto
            FROM productos p
            WHERE 
                p.precio_costo = 0 OR 
                (p.precio_costo > 0 AND ((p.precio_venta - p.precio_costo) / p.precio_costo) * 100 > 500) OR
                (p.precio_costo > 0 AND ((p.precio_venta - p.precio_costo) / p.precio_costo) * 100 < -10) OR
                p.precio_venta < p.precio_costo OR
                ROUND(p.precio_costo, 2) != p.precio_costo OR
                ROUND(p.precio_venta, 2) != p.precio_venta
            ORDER BY p.id
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 💰 CORREGIR PRODUCTOS CON COSTO CERO
     */
    private function correctZeroCostProducts($products, $mode) {
        $zeroCostProducts = array_filter($products, function($p) {
            return (float)$p['precio_costo'] == 0;
        });
        
        $correctedProducts = [];
        $estimationMethods = [];
        
        foreach ($zeroCostProducts as $product) {
            $productId = $product['id'];
            $currentPrice = (float)$product['precio_venta'];
            
            // Estrategias para estimar el costo
            $estimatedCost = $this->estimateProductCost($product, $estimationMethods);
            
            if ($estimatedCost > 0) {
                if ($mode === 'safe') {
                    // Modo seguro: solo sugerir, no aplicar
                    $correctedProducts[] = [
                        'product_id' => $productId,
                        'product_name' => $product['nombre'],
                        'current_cost' => 0,
                        'suggested_cost' => $estimatedCost,
                        'current_price' => $currentPrice,
                        'estimation_method' => $estimationMethods[$productId] ?? 'default_margin',
                        'action' => 'suggestion_only'
                    ];
                } else {
                    // Modo automático: aplicar corrección
                    $this->updateProductCost($productId, $estimatedCost, $estimationMethods[$productId] ?? 'automatic');
                    
                    $correctedProducts[] = [
                        'product_id' => $productId,
                        'product_name' => $product['nombre'],
                        'old_cost' => 0,
                        'new_cost' => $estimatedCost,
                        'current_price' => $currentPrice,
                        'estimation_method' => $estimationMethods[$productId] ?? 'default_margin',
                        'action' => 'applied'
                    ];
                }
            }
        }
        
        return [
            'total_zero_cost_products' => count($zeroCostProducts),
            'corrected_products' => $correctedProducts,
            'corrections_applied' => $mode === 'automatic' ? count($correctedProducts) : 0
        ];
    }
    
    /**
     * 📈 CORREGIR MÁRGENES EXTREMOS
     */
    private function correctExtremeMargins($products, $mode) {
        $extremeMarginProducts = array_filter($products, function($p) {
            $margin = (float)$p['margen_porcentaje'];
            return $margin > 500 && (float)$p['precio_costo'] > 0;
        });
        
        $correctedProducts = [];
        
        foreach ($extremeMarginProducts as $product) {
            $productId = $product['id'];
            $currentCost = (float)$product['precio_costo'];
            $currentPrice = (float)$product['precio_venta'];
            $currentMargin = (float)$product['margen_porcentaje'];
            
            // Estrategia: ajustar costo para lograr margen razonable
            $targetMargin = min(self::MAX_MARGIN, max(self::DEFAULT_MARGIN, $currentMargin / 5));
            $suggestedCost = $currentPrice / (1 + ($targetMargin / 100));
            $suggestedCost = round($suggestedCost, self::DECIMAL_PRECISION);
            
            if ($mode === 'safe') {
                $correctedProducts[] = [
                    'product_id' => $productId,
                    'product_name' => $product['nombre'],
                    'current_cost' => $currentCost,
                    'current_price' => $currentPrice,
                    'current_margin' => round($currentMargin, 2),
                    'suggested_cost' => $suggestedCost,
                    'target_margin' => $targetMargin,
                    'action' => 'suggestion_only'
                ];
            } else {
                $this->updateProductCost($productId, $suggestedCost, 'extreme_margin_correction');
                
                $correctedProducts[] = [
                    'product_id' => $productId,
                    'product_name' => $product['nombre'],
                    'old_cost' => $currentCost,
                    'new_cost' => $suggestedCost,
                    'current_price' => $currentPrice,
                    'old_margin' => round($currentMargin, 2),
                    'new_margin' => $targetMargin,
                    'action' => 'applied'
                ];
            }
        }
        
        return [
            'total_extreme_margin_products' => count($extremeMarginProducts),
            'corrected_products' => $correctedProducts,
            'corrections_applied' => $mode === 'automatic' ? count($correctedProducts) : 0
        ];
    }
    
    /**
     * 📉 CORREGIR MÁRGENES NEGATIVOS
     */
    private function correctNegativeMargins($products, $mode) {
        $negativeMarginProducts = array_filter($products, function($p) {
            return (float)$p['precio_venta'] < (float)$p['precio_costo'] && (float)$p['precio_costo'] > 0;
        });
        
        $correctedProducts = [];
        
        foreach ($negativeMarginProducts as $product) {
            $productId = $product['id'];
            $currentCost = (float)$product['precio_costo'];
            $currentPrice = (float)$product['precio_venta'];
            
            // Estrategia: ajustar precio para margen mínimo
            $suggestedPrice = $currentCost * (1 + (self::MIN_MARGIN / 100));
            $suggestedPrice = round($suggestedPrice, self::DECIMAL_PRECISION);
            
            if ($mode === 'safe') {
                $correctedProducts[] = [
                    'product_id' => $productId,
                    'product_name' => $product['nombre'],
                    'current_cost' => $currentCost,
                    'current_price' => $currentPrice,
                    'current_margin' => round((($currentPrice - $currentCost) / $currentCost) * 100, 2),
                    'suggested_price' => $suggestedPrice,
                    'target_margin' => self::MIN_MARGIN,
                    'action' => 'suggestion_only'
                ];
            } else {
                $this->updateProductPrice($productId, $suggestedPrice, 'negative_margin_correction');
                
                $correctedProducts[] = [
                    'product_id' => $productId,
                    'product_name' => $product['nombre'],
                    'cost' => $currentCost,
                    'old_price' => $currentPrice,
                    'new_price' => $suggestedPrice,
                    'old_margin' => round((($currentPrice - $currentCost) / $currentCost) * 100, 2),
                    'new_margin' => self::MIN_MARGIN,
                    'action' => 'applied'
                ];
            }
        }
        
        return [
            'total_negative_margin_products' => count($negativeMarginProducts),
            'corrected_products' => $correctedProducts,
            'corrections_applied' => $mode === 'automatic' ? count($correctedProducts) : 0
        ];
    }
    
    /**
     * 🔢 CORREGIR PRECISIÓN DECIMAL
     */
    private function correctDecimalPrecision($products, $mode) {
        $precisionProducts = array_filter($products, function($p) {
            $cost = (float)$p['precio_costo'];
            $price = (float)$p['precio_venta'];
            return round($cost, self::DECIMAL_PRECISION) != $cost || 
                   round($price, self::DECIMAL_PRECISION) != $price;
        });
        
        $correctedProducts = [];
        
        foreach ($precisionProducts as $product) {
            $productId = $product['id'];
            $currentCost = (float)$product['precio_costo'];
            $currentPrice = (float)$product['precio_venta'];
            $correctedCost = round($currentCost, self::DECIMAL_PRECISION);
            $correctedPrice = round($currentPrice, self::DECIMAL_PRECISION);
            
            if ($mode === 'automatic') {
                $this->updateProductPricing($productId, $correctedCost, $correctedPrice, 'decimal_precision_correction');
            }
            
            $correctedProducts[] = [
                'product_id' => $productId,
                'product_name' => $product['nombre'],
                'old_cost' => $currentCost,
                'new_cost' => $correctedCost,
                'old_price' => $currentPrice,
                'new_price' => $correctedPrice,
                'action' => $mode === 'automatic' ? 'applied' : 'suggestion_only'
            ];
        }
        
        return [
            'total_precision_products' => count($precisionProducts),
            'corrected_products' => $correctedProducts,
            'corrections_applied' => $mode === 'automatic' ? count($correctedProducts) : 0
        ];
    }
    
    /**
     * 🔍 ESTIMAR COSTO DE PRODUCTO
     */
    private function estimateProductCost($product, &$estimationMethods) {
        $productId = $product['id'];
        $currentPrice = (float)$product['precio_venta'];
        $category = $product['categoria'];
        
        // Método 1: Costo histórico de ventas
        $historicalCost = $this->getHistoricalCost($productId);
        if ($historicalCost > 0) {
            $estimationMethods[$productId] = 'historical_sales';
            return $historicalCost;
        }
        
        // Método 2: Promedio de categoría
        $categoryCost = $this->getCategoryAverageCost($category, $currentPrice);
        if ($categoryCost > 0) {
            $estimationMethods[$productId] = 'category_average';
            return $categoryCost;
        }
        
        // Método 3: Margen por defecto (precio / (1 + margen))
        $estimatedCost = $currentPrice / (1 + (self::DEFAULT_MARGIN / 100));
        $estimationMethods[$productId] = 'default_margin';
        return round($estimatedCost, self::DECIMAL_PRECISION);
    }
    
    /**
     * 📊 OBTENER COSTO HISTÓRICO
     */
    private function getHistoricalCost($productId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT precio_compra 
                FROM venta_detalles 
                WHERE producto_id = ? AND precio_compra > 0 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt->execute([$productId]);
            return (float)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * 📈 OBTENER COSTO PROMEDIO POR CATEGORÍA
     */
    private function getCategoryAverageCost($category, $currentPrice) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT AVG(precio_costo / precio_venta) as avg_cost_ratio
                FROM productos 
                WHERE categoria = ? AND precio_costo > 0 AND precio_venta > 0
            ");
            $stmt->execute([$category]);
            $ratio = (float)$stmt->fetchColumn();
            
            if ($ratio > 0 && $ratio < 1) {
                return round($currentPrice * $ratio, self::DECIMAL_PRECISION);
            }
            
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * 💾 ACTUALIZAR COSTO DE PRODUCTO
     */
    private function updateProductCost($productId, $newCost, $method) {
        $stmt = $this->pdo->prepare("
            UPDATE productos 
            SET precio_costo = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newCost, $productId]);
        
        // Log del cambio
        $this->auditLogger->log('COST_UPDATE', 'SUCCESS', [
            'product_id' => $productId,
            'new_cost' => $newCost,
            'method' => $method
        ]);
    }
    
    /**
     * 💲 ACTUALIZAR PRECIO DE PRODUCTO
     */
    private function updateProductPrice($productId, $newPrice, $method) {
        $stmt = $this->pdo->prepare("
            UPDATE productos 
            SET precio_venta = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newPrice, $productId]);
        
        // Log del cambio
        $this->auditLogger->log('PRICE_UPDATE', 'SUCCESS', [
            'product_id' => $productId,
            'new_price' => $newPrice,
            'method' => $method
        ]);
    }
    
    /**
     * 💰 ACTUALIZAR PRECIO Y COSTO
     */
    private function updateProductPricing($productId, $newCost, $newPrice, $method) {
        $stmt = $this->pdo->prepare("
            UPDATE productos 
            SET precio_costo = ?, precio_venta = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newCost, $newPrice, $productId]);
        
        // Log del cambio
        $this->auditLogger->log('PRICING_UPDATE', 'SUCCESS', [
            'product_id' => $productId,
            'new_cost' => $newCost,
            'new_price' => $newPrice,
            'method' => $method
        ]);
    }
    
    /**
     * 📋 GENERAR REPORTE DE CORRECCIONES
     */
    public function generateCorrectionReport() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN precio_costo = 0 THEN 1 ELSE 0 END) as zero_cost_count,
                    SUM(CASE WHEN precio_costo > 0 AND ((precio_venta - precio_costo) / precio_costo) * 100 > 500 THEN 1 ELSE 0 END) as extreme_margin_count,
                    SUM(CASE WHEN precio_venta < precio_costo AND precio_costo > 0 THEN 1 ELSE 0 END) as negative_margin_count,
                    SUM(stock * precio_costo) as total_inventory_value
                FROM productos
                WHERE activo = 1 OR activo IS NULL
            ");
            
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'report' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'summary' => $summary,
                    'issues_requiring_attention' => [
                        'zero_cost_products' => (int)$summary['zero_cost_count'],
                        'extreme_margins' => (int)$summary['extreme_margin_count'],
                        'negative_margins' => (int)$summary['negative_margin_count']
                    ],
                    'total_inventory_value' => round((float)$summary['total_inventory_value'], 2)
                ]
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
 * 📋 LOGGER DE CORRECCIONES DE PRECIOS
 */
class PriceCorrectionLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function log($operation, $status, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO price_correction_log (operation, status, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $operation,
                $status,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Price correction log error: " . $e->getMessage());
        }
    }
}

// Crear tabla de log si no existe
try {
    $pdo = Conexion::obtenerConexion();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS price_correction_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            operation VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_operation_date (operation, created_at),
            INDEX idx_status (status)
        )
    ");
} catch (Exception $e) {
    error_log("Error creating price correction log table: " . $e->getMessage());
}

// Procesar solicitud
$corrector = new ProductPriceCorrector();

$action = $_GET['action'] ?? '';
$mode = $_GET['mode'] ?? 'safe';

switch ($action) {
    case 'correct':
        $corrector->performAutomaticCorrection($mode);
        break;
    case 'report':
        $corrector->generateCorrectionReport();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no reconocida']);
}
?>