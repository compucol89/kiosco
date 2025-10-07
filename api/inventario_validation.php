<?php
/*
 * Validador de Integridad de Inventario - Sistema Banking Grade
 * Implementa validaciones matemáticas críticas y principio de Pareto
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

class InventoryValidator {
    private $pdo;
    private $auditLogger;
    
    // Configuración de validación
    const PARETO_TOLERANCE_PERCENT = 10; // 10% de tolerancia en clasificación ABC
    const DECIMAL_PRECISION = 2;
    const MIN_PRODUCTS_FOR_ABC = 10; // Mínimo de productos para análisis ABC válido
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->auditLogger = new ValidationAuditLogger($this->pdo);
    }
    
    /**
     * 🔍 VALIDACIÓN INTEGRAL DE INVENTARIO
     */
    public function validateInventory() {
        try {
            $startTime = microtime(true);
            
            // Obtener datos de inventario
            $products = $this->getInventoryData();
            
            // Ejecutar validaciones críticas
            $validation = [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_products' => count($products),
                'mathematical_integrity' => $this->validateMathematicalIntegrity($products),
                'abc_analysis' => $this->validateABCAnalysis($products),
                'stock_consistency' => $this->validateStockConsistency($products),
                'financial_accuracy' => $this->validateFinancialAccuracy($products),
                'pareto_compliance' => $this->validateParetoCompliance($products),
                'performance_metrics' => $this->calculatePerformanceMetrics($products),
                'critical_alerts' => $this->generateCriticalAlerts($products)
            ];
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            $validation['execution_time_ms'] = round($executionTime, 2);
            
            // Audit logging
            $this->auditLogger->log('INVENTORY_VALIDATION', 'SUCCESS', $validation);
            
            echo json_encode([
                'success' => true,
                'validation' => $validation
            ]);
            
        } catch (Exception $e) {
            $this->auditLogger->log('INVENTORY_VALIDATION', 'ERROR', [
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
     * 📊 OBTENER DATOS DE INVENTARIO COMPLETOS
     */
    private function getInventoryData() {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                COALESCE(v.ventas_30_dias, 0) as ventas_30_dias,
                COALESCE(v.ventas_total, 0) as ventas_total,
                COALESCE(v.ultima_venta, NULL) as ultima_venta,
                (p.stock * p.precio_costo) as valor_inventario,
                CASE 
                    WHEN p.stock <= 0 THEN 'sin_stock'
                    WHEN p.stock <= COALESCE(p.stock_minimo, 10) THEN 'stock_bajo'
                    ELSE 'stock_normal'
                END as estado_stock
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(cantidad) as ventas_total,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN cantidad ELSE 0 END) as ventas_30_dias,
                    MAX(fecha) as ultima_venta
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            ORDER BY (p.stock * p.precio_costo) DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 🧮 VALIDACIÓN DE INTEGRIDAD MATEMÁTICA
     */
    private function validateMathematicalIntegrity($products) {
        $validation = [
            'status' => 'pass',
            'errors' => [],
            'warnings' => [],
            'metrics' => []
        ];
        
        $totalProducts = count($products);
        $totalValue = 0;
        $activeProducts = 0;
        $zeroStockProducts = 0;
        $lowStockProducts = 0;
        
        foreach ($products as $product) {
            $stock = (float)$product['stock'];
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            
            // Validar valores numéricos
            if (!is_finite($stock) || $stock < 0) {
                $validation['errors'][] = "Producto ID {$product['id']}: Stock inválido ({$stock})";
                $validation['status'] = 'fail';
            }
            
            if (!is_finite($cost) || $cost < 0) {
                $validation['errors'][] = "Producto ID {$product['id']}: Precio costo inválido ({$cost})";
                $validation['status'] = 'fail';
            }
            
            if (!is_finite($price) || $price <= 0) {
                $validation['errors'][] = "Producto ID {$product['id']}: Precio venta inválido ({$price})";
                $validation['status'] = 'fail';
            }
            
            // Validar coherencia de precios
            if ($cost > 0 && $price > 0 && $price <= $cost) {
                $validation['warnings'][] = "Producto ID {$product['id']}: Precio venta menor o igual al costo";
            }
            
            // Acumular métricas
            $totalValue += $stock * $cost;
            if ($stock > 0) $activeProducts++;
            if ($stock == 0) $zeroStockProducts++;
            if ($stock > 0 && $stock <= ($product['stock_minimo'] ?? 10)) $lowStockProducts++;
        }
        
        $validation['metrics'] = [
            'total_products' => $totalProducts,
            'total_value' => round($totalValue, self::DECIMAL_PRECISION),
            'active_products' => $activeProducts,
            'zero_stock_products' => $zeroStockProducts,
            'low_stock_products' => $lowStockProducts,
            'stock_coverage_percentage' => $totalProducts > 0 ? round(($activeProducts / $totalProducts) * 100, 2) : 0
        ];
        
        return $validation;
    }
    
    /**
     * 📈 VALIDACIÓN DE ANÁLISIS ABC CON PRINCIPIO DE PARETO
     */
    private function validateABCAnalysis($products) {
        if (count($products) < self::MIN_PRODUCTS_FOR_ABC) {
            return [
                'status' => 'skip',
                'message' => 'Insuficientes productos para análisis ABC válido'
            ];
        }
        
        // Clasificar productos según valor de inventario
        $productsWithValue = array_map(function($product) {
            return array_merge($product, [
                'valor_inventario' => ($product['stock'] ?? 0) * ($product['precio_costo'] ?? 0)
            ]);
        }, $products);
        
        // Ordenar por valor descendente
        usort($productsWithValue, function($a, $b) {
            return $b['valor_inventario'] <=> $a['valor_inventario'];
        });
        
        $totalValue = array_sum(array_column($productsWithValue, 'valor_inventario'));
        $totalProducts = count($productsWithValue);
        
        // Aplicar clasificación ABC real basada en Pareto
        $cumulativeValue = 0;
        $classA = 0;
        $classB = 0;
        $classC = 0;
        
        foreach ($productsWithValue as $index => $product) {
            $cumulativeValue += $product['valor_inventario'];
            $valuePercentage = $totalValue > 0 ? ($cumulativeValue / $totalValue) : 0;
            $productPercentage = ($index + 1) / $totalProducts;
            
            if ($valuePercentage <= 0.80 && $productPercentage <= 0.20) {
                $classA++;
            } elseif ($valuePercentage <= 0.95 && $productPercentage <= 0.50) {
                $classB++;
            } else {
                $classC++;
            }
        }
        
        // Calcular porcentajes reales
        $percentageA = ($classA / $totalProducts) * 100;
        $percentageB = ($classB / $totalProducts) * 100;
        $percentageC = ($classC / $totalProducts) * 100;
        
        // Validar cumplimiento de Pareto
        $expectedA = 20; // 20% de productos
        $expectedB = 30; // 30% de productos
        $expectedC = 50; // 50% de productos
        
        $deviationA = abs($percentageA - $expectedA);
        $deviationB = abs($percentageB - $expectedB);
        $deviationC = abs($percentageC - $expectedC);
        
        $paretoCompliant = $deviationA <= self::PARETO_TOLERANCE_PERCENT && 
                          $deviationB <= self::PARETO_TOLERANCE_PERCENT && 
                          $deviationC <= self::PARETO_TOLERANCE_PERCENT;
        
        return [
            'status' => $paretoCompliant ? 'pass' : 'warning',
            'classification' => [
                'class_a' => $classA,
                'class_b' => $classB,
                'class_c' => $classC
            ],
            'percentages' => [
                'class_a' => round($percentageA, 2),
                'class_b' => round($percentageB, 2),
                'class_c' => round($percentageC, 2)
            ],
            'deviations' => [
                'class_a' => round($deviationA, 2),
                'class_b' => round($deviationB, 2),
                'class_c' => round($deviationC, 2)
            ],
            'pareto_compliant' => $paretoCompliant,
            'message' => $paretoCompliant ? 
                'Clasificación ABC cumple con principio de Pareto' : 
                'Desviación detectada del principio de Pareto 80/20'
        ];
    }
    
    /**
     * 📦 VALIDACIÓN DE CONSISTENCIA DE STOCK
     */
    private function validateStockConsistency($products) {
        $validation = [
            'status' => 'pass',
            'issues' => [],
            'summary' => []
        ];
        
        $criticalStockouts = 0;
        $excessiveStock = 0;
        $negativeStock = 0;
        
        foreach ($products as $product) {
            $stock = (int)$product['stock'];
            $minStock = (int)($product['stock_minimo'] ?? 10);
            $ventas30d = (int)($product['ventas_30_dias'] ?? 0);
            
            // Validar stock negativo
            if ($stock < 0) {
                $negativeStock++;
                $validation['issues'][] = [
                    'type' => 'negative_stock',
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'stock' => $stock
                ];
                $validation['status'] = 'fail';
            }
            
            // Detectar productos críticos sin stock con ventas recientes
            if ($stock == 0 && $ventas30d > 0) {
                $criticalStockouts++;
                $validation['issues'][] = [
                    'type' => 'critical_stockout',
                    'product_id' => $product['id'],
                    'product_name' => $product['nombre'],
                    'recent_sales' => $ventas30d
                ];
            }
            
            // Detectar exceso de stock (más de 90 días de cobertura)
            if ($ventas30d > 0 && $stock > 0) {
                $monthlyCoverage = $stock / ($ventas30d / 30);
                if ($monthlyCoverage > 90) {
                    $excessiveStock++;
                    $validation['issues'][] = [
                        'type' => 'excessive_stock',
                        'product_id' => $product['id'],
                        'product_name' => $product['nombre'],
                        'days_coverage' => round($monthlyCoverage)
                    ];
                }
            }
        }
        
        $validation['summary'] = [
            'critical_stockouts' => $criticalStockouts,
            'excessive_stock_items' => $excessiveStock,
            'negative_stock_items' => $negativeStock,
            'total_issues' => count($validation['issues'])
        ];
        
        return $validation;
    }
    
    /**
     * 💰 VALIDACIÓN DE PRECISIÓN FINANCIERA
     */
    private function validateFinancialAccuracy($products) {
        $validation = [
            'status' => 'pass',
            'financial_metrics' => [],
            'precision_errors' => []
        ];
        
        $totalInventoryValue = 0;
        $totalCostValue = 0;
        $totalRetailValue = 0;
        $precisionErrors = 0;
        
        foreach ($products as $product) {
            $stock = (float)$product['stock'];
            $cost = (float)$product['precio_costo'];
            $price = (float)$product['precio_venta'];
            
            $inventoryValue = $stock * $cost;
            $retailValue = $stock * $price;
            
            // Validar precisión decimal
            if (round($inventoryValue, self::DECIMAL_PRECISION) != $inventoryValue) {
                $precisionErrors++;
                $validation['precision_errors'][] = [
                    'product_id' => $product['id'],
                    'type' => 'inventory_value_precision',
                    'value' => $inventoryValue
                ];
            }
            
            $totalInventoryValue += $inventoryValue;
            $totalCostValue += $cost;
            $totalRetailValue += $retailValue;
        }
        
        if ($precisionErrors > 0) {
            $validation['status'] = 'warning';
        }
        
        $validation['financial_metrics'] = [
            'total_inventory_value' => round($totalInventoryValue, self::DECIMAL_PRECISION),
            'total_retail_value' => round($totalRetailValue, self::DECIMAL_PRECISION),
            'average_markup_percentage' => $totalCostValue > 0 ? 
                round((($totalRetailValue - $totalInventoryValue) / $totalInventoryValue) * 100, 2) : 0,
            'precision_errors_count' => $precisionErrors
        ];
        
        return $validation;
    }
    
    /**
     * ⚡ MÉTRICAS DE PERFORMANCE
     */
    private function calculatePerformanceMetrics($products) {
        $metrics = [
            'turnover_analysis' => [],
            'demand_patterns' => [],
            'supplier_performance' => []
        ];
        
        // Calcular rotación promedio ponderada
        $totalValue = 0;
        $weightedTurnover = 0;
        
        foreach ($products as $product) {
            $value = ($product['stock'] ?? 0) * ($product['precio_costo'] ?? 0);
            $sales30d = $product['ventas_30_dias'] ?? 0;
            
            if ($value > 0 && $product['stock'] > 0) {
                $turnover = ($sales30d / ($product['stock'] ?? 1)) * 12; // Anualizada
                $weightedTurnover += $turnover * $value;
                $totalValue += $value;
            }
        }
        
        $metrics['turnover_analysis'] = [
            'average_turnover' => $totalValue > 0 ? round($weightedTurnover / $totalValue, 2) : 0,
            'fast_movers' => count(array_filter($products, function($p) {
                return ($p['ventas_30_dias'] ?? 0) > 10;
            })),
            'slow_movers' => count(array_filter($products, function($p) {
                return ($p['ventas_30_dias'] ?? 0) == 0 && ($p['stock'] ?? 0) > 0;
            }))
        ];
        
        return $metrics;
    }
    
    /**
     * ⚠️ GENERAR ALERTAS CRÍTICAS
     */
    private function generateCriticalAlerts($products) {
        $alerts = [];
        
        // Alert por alto número de productos sin stock
        $zeroStock = count(array_filter($products, function($p) { return ($p['stock'] ?? 0) == 0; }));
        $totalProducts = count($products);
        
        if ($totalProducts > 0) {
            $stockoutRate = ($zeroStock / $totalProducts) * 100;
            
            if ($stockoutRate > 30) {
                $alerts[] = [
                    'type' => 'high_stockout_rate',
                    'severity' => 'critical',
                    'message' => "Alto nivel de productos sin stock: {$stockoutRate}% ({$zeroStock}/{$totalProducts})",
                    'recommended_action' => 'Revisar proceso de reabastecimiento urgentemente'
                ];
            }
        }
        
        // Alert por concentración excesiva en una categoría
        $categories = array_count_values(array_column($products, 'categoria'));
        $maxCategoryCount = max($categories);
        
        if ($totalProducts > 0 && ($maxCategoryCount / $totalProducts) > 0.6) {
            $alerts[] = [
                'type' => 'category_concentration',
                'severity' => 'warning',
                'message' => "Concentración excesiva en una categoría: {$maxCategoryCount} productos",
                'recommended_action' => 'Diversificar portfolio de productos'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * ✅ VALIDACIÓN DE CUMPLIMIENTO DE PARETO
     */
    private function validateParetoCompliance($products) {
        return $this->validateABCAnalysis($products);
    }
}

/**
 * 📋 LOGGER DE AUDITORÍA PARA VALIDACIONES
 */
class ValidationAuditLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function log($operation, $status, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO validation_audit_log (operation, status, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $operation,
                $status,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Validation audit log error: " . $e->getMessage());
        }
    }
}

// Crear tabla de auditoría si no existe
try {
    $pdo = Conexion::obtenerConexion();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS validation_audit_log (
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
    error_log("Error creating validation audit table: " . $e->getMessage());
}

// Ejecutar validación
$validator = new InventoryValidator();
$validator->validateInventory();
?>