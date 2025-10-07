<?php
/*
 * Product Financial Validator - Sistema Banking Grade
 * Audita integridad financiera de productos y detecta anomalías críticas
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

class ProductFinancialValidator {
    private $pdo;
    private $auditLogger;
    
    // Configuración de validación financiera
    const MIN_COST_THRESHOLD = 0.01; // Costo mínimo aceptable
    const MAX_MARGIN_THRESHOLD = 500; // Margen máximo aceptable (500%)
    const MIN_MARGIN_THRESHOLD = -10; // Margen mínimo aceptable (-10% para productos promocionales)
    const DECIMAL_PRECISION = 2;
    const INVENTORY_TOLERANCE = 0.01; // 1 centavo de tolerancia en valoración
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->auditLogger = new ProductAuditLogger($this->pdo);
    }
    
    /**
     * 🔍 VALIDACIÓN INTEGRAL DE PRODUCTOS
     */
    public function validateProductCatalog() {
        try {
            $startTime = microtime(true);
            
            // Obtener todos los productos
            $products = $this->getAllProducts();
            
            $validation = [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_products' => count($products),
                'inventory_valuation' => $this->validateInventoryValuation($products),
                'pricing_anomalies' => $this->detectPricingAnomalies($products),
                'zero_cost_products' => $this->findZeroCostProducts($products),
                'extreme_margins' => $this->findExtremeMargins($products),
                'data_integrity' => $this->validateDataIntegrity($products),
                'financial_summary' => $this->calculateFinancialSummary($products),
                'recommendations' => $this->generateRecommendations($products),
                'critical_alerts' => $this->generateCriticalAlerts($products)
            ];
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            $validation['execution_time_ms'] = round($executionTime, 2);
            
            // Audit logging
            $this->auditLogger->log('PRODUCT_FINANCIAL_VALIDATION', 'SUCCESS', $validation);
            
            echo json_encode([
                'success' => true,
                'validation' => $validation
            ]);
            
        } catch (Exception $e) {
            $this->auditLogger->log('PRODUCT_FINANCIAL_VALIDATION', 'ERROR', [
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
     * 📊 OBTENER TODOS LOS PRODUCTOS CON DATOS COMPLETOS
     */
    private function getAllProducts() {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                (p.stock * p.precio_costo) as valor_inventario,
                CASE 
                    WHEN p.precio_costo > 0 
                    THEN ((p.precio_venta - p.precio_costo) / p.precio_costo) * 100
                    ELSE 0
                END as margen_porcentaje,
                (p.precio_venta - p.precio_costo) as margen_absoluto,
                CASE 
                    WHEN p.stock = 0 THEN 'sin_stock'
                    WHEN p.stock <= COALESCE(p.stock_minimo, 10) THEN 'stock_bajo'
                    ELSE 'stock_normal'
                END as estado_stock
            FROM productos p
            WHERE p.activo = 1 OR p.activo IS NULL
            ORDER BY p.id
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 💰 VALIDAR VALORACIÓN DE INVENTARIO
     */
    private function validateInventoryValuation($products) {
        $totalValue = 0;
        $totalUnits = 0;
        $activeProducts = 0;
        $zeroStockProducts = 0;
        $valuationIssues = [];
        
        foreach ($products as $product) {
            $stock = (int)$product['stock'];
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            $inventoryValue = $stock * $cost;
            
            $totalValue += $inventoryValue;
            $totalUnits += $stock;
            
            if ($stock > 0) {
                $activeProducts++;
            } else {
                $zeroStockProducts++;
            }
            
            // Detectar problemas de valoración
            if ($stock > 0 && $cost <= 0) {
                $valuationIssues[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'issue' => 'zero_cost_with_stock',
                    'stock' => $stock,
                    'cost' => $cost,
                    'potential_loss' => $stock * 1000 // Estimación conservadora de pérdida
                ];
            }
            
            // Verificar inconsistencias decimales
            if (round($inventoryValue, self::DECIMAL_PRECISION) != $inventoryValue) {
                $valuationIssues[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'issue' => 'decimal_precision',
                    'calculated_value' => $inventoryValue,
                    'rounded_value' => round($inventoryValue, self::DECIMAL_PRECISION)
                ];
            }
        }
        
        return [
            'total_inventory_value' => round($totalValue, self::DECIMAL_PRECISION),
            'total_units' => $totalUnits,
            'active_products' => $activeProducts,
            'zero_stock_products' => $zeroStockProducts,
            'valuation_issues' => $valuationIssues,
            'issues_count' => count($valuationIssues),
            'value_at_risk' => array_sum(array_column($valuationIssues, 'potential_loss') ?: [0])
        ];
    }
    
    /**
     * 🚨 DETECTAR ANOMALÍAS DE PRECIOS
     */
    private function detectPricingAnomalies($products) {
        $anomalies = [];
        
        foreach ($products as $product) {
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            $margin = (float)$product['margen_porcentaje'];
            
            // Producto con costo cero pero precio positivo
            if ($cost == 0 && $price > 0) {
                $anomalies[] = [
                    'type' => 'zero_cost_positive_price',
                    'severity' => 'critical',
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'cost' => $cost,
                    'price' => $price,
                    'description' => 'Producto sin costo definido con precio de venta',
                    'risk_level' => 'high'
                ];
            }
            
            // Precio menor que costo (margen negativo)
            if ($cost > 0 && $price < $cost) {
                $anomalies[] = [
                    'type' => 'negative_margin',
                    'severity' => 'high',
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'cost' => $cost,
                    'price' => $price,
                    'margin' => $margin,
                    'description' => 'Precio de venta menor al costo',
                    'loss_per_unit' => round($cost - $price, 2)
                ];
            }
            
            // Margen extremadamente alto (posible error)
            if ($margin > self::MAX_MARGIN_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'excessive_margin',
                    'severity' => 'warning',
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'cost' => $cost,
                    'price' => $price,
                    'margin' => $margin,
                    'description' => 'Margen excesivamente alto, posible error de captura'
                ];
            }
            
            // Precios con precisión decimal incorrecta
            if (round($price, self::DECIMAL_PRECISION) != $price || round($cost, self::DECIMAL_PRECISION) != $cost) {
                $anomalies[] = [
                    'type' => 'decimal_precision_error',
                    'severity' => 'low',
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'cost' => $cost,
                    'price' => $price,
                    'description' => 'Precisión decimal incorrecta en precios'
                ];
            }
        }
        
        return [
            'total_anomalies' => count($anomalies),
            'critical_count' => count(array_filter($anomalies, function($a) { return $a['severity'] === 'critical'; })),
            'high_count' => count(array_filter($anomalies, function($a) { return $a['severity'] === 'high'; })),
            'warning_count' => count(array_filter($anomalies, function($a) { return $a['severity'] === 'warning'; })),
            'anomalies' => $anomalies
        ];
    }
    
    /**
     * 🔍 ENCONTRAR PRODUCTOS CON COSTO CERO
     */
    private function findZeroCostProducts($products) {
        $zeroCostProducts = array_filter($products, function($product) {
            return (float)$product['precio_costo'] == 0;
        });
        
        $totalPotentialLoss = 0;
        $detailedProducts = [];
        
        foreach ($zeroCostProducts as $product) {
            $stock = (int)$product['stock'];
            $price = (float)$product['precio_venta'];
            $potentialLoss = $stock * $price; // Si se vendera sin registrar costo
            
            $totalPotentialLoss += $potentialLoss;
            
            $detailedProducts[] = [
                'id' => $product['id'],
                'name' => $product['nombre'],
                'category' => $product['categoria'],
                'stock' => $stock,
                'price' => $price,
                'potential_loss' => $potentialLoss,
                'status' => $stock == 0 ? 'no_impact' : 'high_risk'
            ];
        }
        
        return [
            'count' => count($zeroCostProducts),
            'total_potential_loss' => round($totalPotentialLoss, 2),
            'products' => $detailedProducts,
            'risk_assessment' => count($zeroCostProducts) > 0 ? 'critical' : 'none'
        ];
    }
    
    /**
     * 📈 ENCONTRAR MÁRGENES EXTREMOS
     */
    private function findExtremeMargins($products) {
        $extremeMargins = [];
        
        foreach ($products as $product) {
            $margin = (float)$product['margen_porcentaje'];
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            
            if ($cost > 0 && ($margin > 200 || $margin < -5)) { // Márgenes > 200% o < -5%
                $extremeMargins[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'cost' => $cost,
                    'price' => $price,
                    'margin' => round($margin, 2),
                    'category' => $product['categoria'],
                    'stock' => (int)$product['stock'],
                    'severity' => $margin > 500 || $margin < -20 ? 'critical' : 'warning'
                ];
            }
        }
        
        // Ordenar por margen descendente
        usort($extremeMargins, function($a, $b) {
            return $b['margin'] <=> $a['margin'];
        });
        
        return [
            'count' => count($extremeMargins),
            'highest_margin' => count($extremeMargins) > 0 ? $extremeMargins[0]['margin'] : 0,
            'lowest_margin' => count($extremeMargins) > 0 ? end($extremeMargins)['margin'] : 0,
            'products' => $extremeMargins
        ];
    }
    
    /**
     * 🔒 VALIDAR INTEGRIDAD DE DATOS
     */
    private function validateDataIntegrity($products) {
        $issues = [];
        $stats = [
            'missing_names' => 0,
            'missing_codes' => 0,
            'missing_categories' => 0,
            'duplicate_codes' => 0,
            'negative_stock' => 0,
            'invalid_prices' => 0
        ];
        
        $codes = [];
        
        foreach ($products as $product) {
            $productId = $product['id'];
            $name = trim($product['nombre'] ?? '');
            $code = trim($product['codigo'] ?? '');
            $category = trim($product['categoria'] ?? '');
            $stock = (int)$product['stock'];
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            
            // Nombre faltante
            if (empty($name)) {
                $issues[] = ['type' => 'missing_name', 'product_id' => $productId];
                $stats['missing_names']++;
            }
            
            // Código faltante
            if (empty($code)) {
                $issues[] = ['type' => 'missing_code', 'product_id' => $productId];
                $stats['missing_codes']++;
            } else {
                // Verificar códigos duplicados
                if (in_array($code, $codes)) {
                    $issues[] = ['type' => 'duplicate_code', 'product_id' => $productId, 'code' => $code];
                    $stats['duplicate_codes']++;
                } else {
                    $codes[] = $code;
                }
            }
            
            // Categoría faltante
            if (empty($category) || $category === 'Sin categoría') {
                $stats['missing_categories']++;
            }
            
            // Stock negativo
            if ($stock < 0) {
                $issues[] = ['type' => 'negative_stock', 'product_id' => $productId, 'stock' => $stock];
                $stats['negative_stock']++;
            }
            
            // Precios inválidos
            if ($price <= 0) {
                $issues[] = ['type' => 'invalid_price', 'product_id' => $productId, 'price' => $price];
                $stats['invalid_prices']++;
            }
        }
        
        return [
            'total_issues' => count($issues),
            'statistics' => $stats,
            'issues' => $issues,
            'integrity_score' => $this->calculateIntegrityScore($stats, count($products))
        ];
    }
    
    /**
     * 📊 CALCULAR RESUMEN FINANCIERO
     */
    private function calculateFinancialSummary($products) {
        $totalProducts = count($products);
        $totalInventoryValue = 0;
        $totalRetailValue = 0;
        $totalUnits = 0;
        $categorySummary = [];
        
        foreach ($products as $product) {
            $stock = (int)$product['stock'];
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            $category = $product['categoria'] ?? 'Sin categoría';
            
            $inventoryValue = $stock * $cost;
            $retailValue = $stock * $price;
            
            $totalInventoryValue += $inventoryValue;
            $totalRetailValue += $retailValue;
            $totalUnits += $stock;
            
            // Resumen por categoría
            if (!isset($categorySummary[$category])) {
                $categorySummary[$category] = [
                    'products' => 0,
                    'total_units' => 0,
                    'inventory_value' => 0,
                    'retail_value' => 0
                ];
            }
            
            $categorySummary[$category]['products']++;
            $categorySummary[$category]['total_units'] += $stock;
            $categorySummary[$category]['inventory_value'] += $inventoryValue;
            $categorySummary[$category]['retail_value'] += $retailValue;
        }
        
        // Calcular márgenes por categoría
        foreach ($categorySummary as &$catData) {
            $catData['average_margin'] = $catData['inventory_value'] > 0 ? 
                (($catData['retail_value'] - $catData['inventory_value']) / $catData['inventory_value']) * 100 : 0;
            $catData['average_margin'] = round($catData['average_margin'], 2);
        }
        
        return [
            'total_products' => $totalProducts,
            'total_inventory_value' => round($totalInventoryValue, 2),
            'total_retail_value' => round($totalRetailValue, 2),
            'total_units' => $totalUnits,
            'overall_margin' => $totalInventoryValue > 0 ? 
                round((($totalRetailValue - $totalInventoryValue) / $totalInventoryValue) * 100, 2) : 0,
            'category_breakdown' => $categorySummary
        ];
    }
    
    /**
     * 💡 GENERAR RECOMENDACIONES
     */
    private function generateRecommendations($products) {
        $recommendations = [];
        
        // Contadores para análisis
        $zeroCostCount = count(array_filter($products, function($p) { return (float)$p['precio_costo'] == 0; }));
        $extremeMarginCount = count(array_filter($products, function($p) { 
            return abs((float)$p['margen_porcentaje']) > 200; 
        }));
        $negativeMarginCount = count(array_filter($products, function($p) { 
            return (float)$p['margen_porcentaje'] < 0; 
        }));
        
        if ($zeroCostCount > 0) {
            $recommendations[] = [
                'priority' => 'urgent',
                'category' => 'pricing',
                'title' => 'Corregir Productos sin Costo',
                'description' => "Se encontraron {$zeroCostCount} productos con costo cero",
                'action' => 'Actualizar costos basados en última compra o precio de mercado',
                'impact' => 'Prevenir pérdidas no detectadas en ventas'
            ];
        }
        
        if ($extremeMarginCount > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'validation',
                'title' => 'Revisar Márgenes Extremos',
                'description' => "Se detectaron {$extremeMarginCount} productos con márgenes anómalos",
                'action' => 'Validar precios de compra y venta para corregir errores',
                'impact' => 'Asegurar precios competitivos y precisión de datos'
            ];
        }
        
        if ($negativeMarginCount > 0) {
            $recommendations[] = [
                'priority' => 'urgent',
                'category' => 'profitability',
                'title' => 'Corregir Márgenes Negativos',
                'description' => "Se encontraron {$negativeMarginCount} productos con pérdidas",
                'action' => 'Ajustar precios de venta o renegociar costos con proveedores',
                'impact' => 'Eliminar productos que generan pérdidas'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * 🚨 GENERAR ALERTAS CRÍTICAS
     */
    private function generateCriticalAlerts($products) {
        $alerts = [];
        
        // Valor total de inventario con inconsistencias
        $valuationIssues = $this->validateInventoryValuation($products);
        if ($valuationIssues['value_at_risk'] > 10000) {
            $alerts[] = [
                'type' => 'high_value_at_risk',
                'severity' => 'critical',
                'message' => "Valor en riesgo: $" . number_format($valuationIssues['value_at_risk'], 2),
                'action_required' => 'Auditoría inmediata de productos con costo cero'
            ];
        }
        
        // Productos críticos sin costo
        $zeroCostCritical = array_filter($products, function($p) {
            return (float)$p['precio_costo'] == 0 && (int)$p['stock'] > 0;
        });
        
        if (count($zeroCostCritical) > 5) {
            $alerts[] = [
                'type' => 'multiple_zero_cost_products',
                'severity' => 'critical',
                'message' => count($zeroCostCritical) . " productos activos sin costo definido",
                'action_required' => 'Definir costos inmediatamente para evitar pérdidas'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * 📈 CALCULAR SCORE DE INTEGRIDAD
     */
    private function calculateIntegrityScore($stats, $totalProducts) {
        if ($totalProducts == 0) return 0;
        
        $penalties = 0;
        $penalties += $stats['missing_names'] * 10;
        $penalties += $stats['missing_codes'] * 5;
        $penalties += $stats['duplicate_codes'] * 15;
        $penalties += $stats['negative_stock'] * 20;
        $penalties += $stats['invalid_prices'] * 25;
        
        $maxPossiblePenalty = $totalProducts * 25; // Peor caso posible
        $score = 100 - (($penalties / max($maxPossiblePenalty, 1)) * 100);
        
        return max(0, min(100, round($score, 1)));
    }
}

/**
 * 📋 LOGGER DE AUDITORÍA PARA PRODUCTOS
 */
class ProductAuditLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function log($operation, $status, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO product_audit_log (operation, status, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $operation,
                $status,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Product audit log error: " . $e->getMessage());
        }
    }
}

// Crear tabla de auditoría si no existe
try {
    $pdo = Conexion::obtenerConexion();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_audit_log (
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
    error_log("Error creating product audit table: " . $e->getMessage());
}

// Procesar solicitud
$validator = new ProductFinancialValidator();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'validate':
    default:
        $validator->validateProductCatalog();
        break;
}
?>