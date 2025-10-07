<?php
/*
 * Stock Reconciliation Engine - Sistema Banking Grade
 * Valida consistencia de stock en tiempo real contra movimientos y ventas
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

class StockReconciliationEngine {
    private $pdo;
    private $auditLogger;
    
    // Configuración de reconciliación
    const TOLERANCE_PERCENTAGE = 0.1; // 0.1% de tolerancia
    const MAX_VARIANCE_UNITS = 1; // Máximo 1 unidad de diferencia aceptable
    const DECIMAL_PRECISION = 0; // Stock siempre en enteros
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->auditLogger = new StockAuditLogger($this->pdo);
    }
    
    /**
     * 🔍 RECONCILIACIÓN COMPLETA DE STOCK
     */
    public function performFullReconciliation() {
        try {
            $startTime = microtime(true);
            
            // Obtener todos los productos con movimientos
            $products = $this->getProductsWithMovements();
            
            $reconciliation = [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_products_analyzed' => count($products),
                'discrepancies' => [],
                'summary' => [
                    'products_with_discrepancies' => 0,
                    'total_variance_units' => 0,
                    'critical_discrepancies' => 0,
                    'minor_discrepancies' => 0
                ],
                'recommendations' => []
            ];
            
            foreach ($products as $product) {
                $analysis = $this->analyzeProductStock($product);
                
                if ($analysis['has_discrepancy']) {
                    $reconciliation['discrepancies'][] = $analysis;
                    $reconciliation['summary']['products_with_discrepancies']++;
                    $reconciliation['summary']['total_variance_units'] += abs($analysis['variance_units']);
                    
                    if ($analysis['severity'] === 'critical') {
                        $reconciliation['summary']['critical_discrepancies']++;
                    } else {
                        $reconciliation['summary']['minor_discrepancies']++;
                    }
                }
            }
            
            // Generar recomendaciones
            $reconciliation['recommendations'] = $this->generateRecommendations($reconciliation);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            $reconciliation['execution_time_ms'] = round($executionTime, 2);
            
            // Audit logging
            $this->auditLogger->log('FULL_RECONCILIATION', 'SUCCESS', $reconciliation);
            
            echo json_encode([
                'success' => true,
                'reconciliation' => $reconciliation
            ]);
            
        } catch (Exception $e) {
            $this->auditLogger->log('FULL_RECONCILIATION', 'ERROR', [
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
     * 📊 OBTENER PRODUCTOS CON MOVIMIENTOS PARA ANÁLISIS
     */
    private function getProductsWithMovements() {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.codigo,
                p.stock as stock_actual,
                p.stock_minimo,
                p.precio_costo,
                
                -- Calcular stock teórico basado en movimientos
                COALESCE(stock_inicial.stock_inicial, 0) as stock_inicial,
                COALESCE(entradas.total_entradas, 0) as total_entradas,
                COALESCE(salidas.total_salidas, 0) as total_salidas,
                COALESCE(ventas.total_vendido, 0) as total_vendido,
                COALESCE(ajustes.total_ajustes, 0) as total_ajustes,
                
                -- Stock teórico = Inicial + Entradas - Salidas - Ventas + Ajustes
                (
                    COALESCE(stock_inicial.stock_inicial, 0) +
                    COALESCE(entradas.total_entradas, 0) -
                    COALESCE(salidas.total_salidas, 0) -
                    COALESCE(ventas.total_vendido, 0) +
                    COALESCE(ajustes.total_ajustes, 0)
                ) as stock_teorico,
                
                -- Fechas para análisis temporal
                stock_inicial.fecha_inicial,
                ventas.ultima_venta,
                ajustes.ultimo_ajuste
                
            FROM productos p
            
            -- Stock inicial (primera entrada o stock base)
            LEFT JOIN (
                SELECT 
                    producto_id,
                    MIN(fecha) as fecha_inicial,
                    FIRST_VALUE(stock_anterior) OVER (
                        PARTITION BY producto_id 
                        ORDER BY fecha ASC
                    ) as stock_inicial
                FROM movimientos_inventario 
                WHERE tipo = 'entrada'
                GROUP BY producto_id
            ) stock_inicial ON p.id = stock_inicial.producto_id
            
            -- Total de entradas
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(cantidad) as total_entradas
                FROM movimientos_inventario 
                WHERE tipo = 'entrada'
                GROUP BY producto_id
            ) entradas ON p.id = entradas.producto_id
            
            -- Total de salidas
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(cantidad) as total_salidas
                FROM movimientos_inventario 
                WHERE tipo = 'salida'
                GROUP BY producto_id
            ) salidas ON p.id = salidas.producto_id
            
            -- Total vendido
            LEFT JOIN (
                SELECT 
                    dv.producto_id,
                    SUM(dv.cantidad) as total_vendido,
                    MAX(v.fecha) as ultima_venta
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado IN ('completada', 'completado')
                GROUP BY dv.producto_id
            ) ventas ON p.id = ventas.producto_id
            
            -- Ajustes de inventario
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(CASE 
                        WHEN tipo = 'ajuste_positivo' THEN cantidad
                        WHEN tipo = 'ajuste_negativo' THEN -cantidad
                        ELSE 0
                    END) as total_ajustes,
                    MAX(fecha) as ultimo_ajuste
                FROM movimientos_inventario 
                WHERE tipo IN ('ajuste_positivo', 'ajuste_negativo')
                GROUP BY producto_id
            ) ajustes ON p.id = ajustes.producto_id
            
            ORDER BY p.nombre
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 🔎 ANALIZAR STOCK DE UN PRODUCTO ESPECÍFICO
     */
    private function analyzeProductStock($product) {
        $stockActual = (int)$product['stock_actual'];
        $stockTeorico = (int)$product['stock_teorico'];
        $varianceUnits = $stockActual - $stockTeorico;
        $variancePercentage = $stockTeorico != 0 ? abs($varianceUnits / $stockTeorico) * 100 : 0;
        
        // Determinar si hay discrepancia
        $hasDiscrepancy = abs($varianceUnits) > self::MAX_VARIANCE_UNITS || 
                         $variancePercentage > self::TOLERANCE_PERCENTAGE;
        
        // Determinar severidad
        $severity = 'minor';
        if (abs($varianceUnits) > 10 || $variancePercentage > 5) {
            $severity = 'critical';
        } elseif (abs($varianceUnits) > 5 || $variancePercentage > 2) {
            $severity = 'moderate';
        }
        
        // Análisis de causas potenciales
        $potentialCauses = $this->analyzePotentialCauses($product, $varianceUnits);
        
        return [
            'product_id' => $product['id'],
            'product_name' => $product['nombre'],
            'product_code' => $product['codigo'],
            'stock_actual' => $stockActual,
            'stock_teorico' => $stockTeorico,
            'variance_units' => $varianceUnits,
            'variance_percentage' => round($variancePercentage, 2),
            'has_discrepancy' => $hasDiscrepancy,
            'severity' => $severity,
            'potential_causes' => $potentialCauses,
            'movement_summary' => [
                'stock_inicial' => (int)($product['stock_inicial'] ?? 0),
                'total_entradas' => (int)($product['total_entradas'] ?? 0),
                'total_salidas' => (int)($product['total_salidas'] ?? 0),
                'total_vendido' => (int)($product['total_vendido'] ?? 0),
                'total_ajustes' => (int)($product['total_ajustes'] ?? 0)
            ],
            'recommendations' => $this->generateProductRecommendations($product, $varianceUnits, $severity)
        ];
    }
    
    /**
     * 🔍 ANALIZAR CAUSAS POTENCIALES DE DISCREPANCIAS
     */
    private function analyzePotentialCauses($product, $varianceUnits) {
        $causes = [];
        
        // Falta de movimientos registrados
        if ($product['total_entradas'] == 0 && $product['stock_actual'] > 0) {
            $causes[] = [
                'type' => 'missing_entries',
                'description' => 'Posibles entradas no registradas en el sistema',
                'likelihood' => 'high'
            ];
        }
        
        // Ventas no registradas (stock actual mayor al teórico)
        if ($varianceUnits > 0 && $product['total_vendido'] > 0) {
            $causes[] = [
                'type' => 'unrecorded_sales',
                'description' => 'Posibles ventas no registradas correctamente',
                'likelihood' => 'medium'
            ];
        }
        
        // Pérdidas o mermas (stock actual menor al teórico)
        if ($varianceUnits < 0) {
            $causes[] = [
                'type' => 'shrinkage',
                'description' => 'Posibles pérdidas, mermas o robos',
                'likelihood' => abs($varianceUnits) > 5 ? 'high' : 'medium'
            ];
        }
        
        // Errores en conteo físico
        if (abs($varianceUnits) <= 2) {
            $causes[] = [
                'type' => 'counting_error',
                'description' => 'Posible error en conteo físico',
                'likelihood' => 'medium'
            ];
        }
        
        // Productos sin movimientos recientes (datos obsoletos)
        if (!$product['ultima_venta'] && !$product['ultimo_ajuste']) {
            $causes[] = [
                'type' => 'obsolete_data',
                'description' => 'Datos de stock posiblemente obsoletos',
                'likelihood' => 'low'
            ];
        }
        
        return $causes;
    }
    
    /**
     * 💡 GENERAR RECOMENDACIONES PARA PRODUCTO ESPECÍFICO
     */
    private function generateProductRecommendations($product, $varianceUnits, $severity) {
        $recommendations = [];
        
        if ($severity === 'critical') {
            $recommendations[] = [
                'priority' => 'urgent',
                'action' => 'immediate_physical_count',
                'description' => 'Realizar conteo físico inmediato para verificar stock real'
            ];
            
            $recommendations[] = [
                'priority' => 'urgent',
                'action' => 'review_movement_history',
                'description' => 'Revisar historial de movimientos de los últimos 30 días'
            ];
        }
        
        if ($varianceUnits < 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'investigate_shrinkage',
                'description' => 'Investigar posibles causas de merma o pérdida'
            ];
        }
        
        if ($product['total_ajustes'] == 0 && abs($varianceUnits) > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'create_adjustment',
                'description' => 'Crear ajuste de inventario para corregir discrepancia'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * 📋 GENERAR RECOMENDACIONES GENERALES
     */
    private function generateRecommendations($reconciliation) {
        $recommendations = [];
        
        $criticalCount = $reconciliation['summary']['critical_discrepancies'];
        $totalDiscrepancies = $reconciliation['summary']['products_with_discrepancies'];
        $totalProducts = $reconciliation['total_products_analyzed'];
        
        // Recomendaciones basadas en el nivel de discrepancias
        if ($criticalCount > 0) {
            $recommendations[] = [
                'priority' => 'urgent',
                'type' => 'process_improvement',
                'title' => 'Discrepancias Críticas Detectadas',
                'description' => "Se encontraron {$criticalCount} discrepancias críticas que requieren atención inmediata",
                'actions' => [
                    'Realizar conteo físico de productos críticos',
                    'Revisar procesos de registro de movimientos',
                    'Implementar controles adicionales de inventario'
                ]
            ];
        }
        
        if ($totalProducts > 0) {
            $discrepancyRate = ($totalDiscrepancies / $totalProducts) * 100;
            
            if ($discrepancyRate > 10) {
                $recommendations[] = [
                    'priority' => 'high',
                    'type' => 'system_review',
                    'title' => 'Alto Porcentaje de Discrepancias',
                    'description' => "El {$discrepancyRate}% de productos presenta discrepancias",
                    'actions' => [
                        'Revisar configuración del sistema de inventario',
                        'Capacitar personal en registro de movimientos',
                        'Implementar auditorías periódicas'
                    ]
                ];
            }
        }
        
        if ($reconciliation['summary']['total_variance_units'] > 100) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'financial_impact',
                'title' => 'Impacto Financiero Significativo',
                'description' => 'Las discrepancias pueden tener impacto financiero considerable',
                'actions' => [
                    'Calcular impacto monetario de las discrepancias',
                    'Revisar políticas de control de inventario',
                    'Considerar implementar tecnología RFID o códigos de barras'
                ]
            ];
        }
        
        return $recommendations;
    }
}

/**
 * 📋 LOGGER DE AUDITORÍA PARA RECONCILIACIÓN DE STOCK
 */
class StockAuditLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function log($operation, $status, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO stock_audit_log (operation, status, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $operation,
                $status,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            error_log("Stock audit log error: " . $e->getMessage());
        }
    }
}

// Crear tabla de auditoría si no existe
try {
    $pdo = Conexion::obtenerConexion();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock_audit_log (
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
    error_log("Error creating stock audit table: " . $e->getMessage());
}

// Procesar solicitud
$engine = new StockReconciliationEngine();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'full_reconciliation':
        $engine->performFullReconciliation();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no reconocida']);
}
?>