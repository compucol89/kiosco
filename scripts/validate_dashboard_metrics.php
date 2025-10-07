<?php
/**
 * 🔍 VALIDADOR ESPECÍFICO DE MÉTRICAS DEL DASHBOARD
 * 
 * Script enterprise para validar los valores específicos mencionados:
 * - Ticket promedio: $2,749.37
 * - Total: $13,746.85
 * - Cantidad: 5 transacciones
 * 
 * Valida la coherencia matemática y detecta discrepancias en tiempo real.
 */

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../api/bd_conexion.php';

class DashboardMetricsValidator {
    private $pdo;
    private $targetValues;
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        
        // Valores específicos del dashboard mencionados en el análisis
        $this->targetValues = [
            'ticket_promedio' => 2749.37,
            'total' => 13746.85,
            'cantidad' => 5
        ];
    }
    
    /**
     * 🎯 VALIDACIÓN ESPECÍFICA DE VALORES DEL DASHBOARD
     */
    public function validateDashboardMetrics() {
        echo "<h2>🎯 VALIDACIÓN ESPECÍFICA DEL DASHBOARD</h2>";
        echo "<div class='dashboard-target'>";
        echo "<h3>Valores objetivo del dashboard:</h3>";
        echo "<ul>";
        echo "<li><strong>Ticket Promedio:</strong> \$" . number_format($this->targetValues['ticket_promedio'], 2) . "</li>";
        echo "<li><strong>Total:</strong> \$" . number_format($this->targetValues['total'], 2) . "</li>";
        echo "<li><strong>Cantidad:</strong> " . $this->targetValues['cantidad'] . " transacciones</li>";
        echo "</ul>";
        echo "</div>";
        
        // Buscar combinaciones de ventas que coincidan con estos valores
        $this->findMatchingTransactionSets();
        
        // Validar matemáticamente
        $this->validateMathematicalConsistency();
        
        // Buscar en diferentes períodos
        $this->searchAcrossPeriods();
    }
    
    /**
     * 🔍 BUSCAR CONJUNTOS DE TRANSACCIONES QUE COINCIDAN
     */
    private function findMatchingTransactionSets() {
        echo "<h3>🔍 Buscando conjuntos de 5 transacciones que sumen \$13,746.85:</h3>";
        
        try {
            // Obtener todas las ventas completadas ordenadas por fecha
            $sql = "SELECT id, fecha, monto_total, cliente_nombre, metodo_pago, estado 
                    FROM ventas 
                    WHERE estado IN ('completado', 'completada') 
                    AND monto_total > 0
                    ORDER BY fecha DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $todasLasVentas = $stmt->fetchAll();
            
            echo "<p>Total de ventas completadas en el sistema: " . count($todasLasVentas) . "</p>";
            
            if (count($todasLasVentas) < 5) {
                echo "<div class='warning'>⚠️ No hay suficientes ventas para formar un conjunto de 5</div>";
                return;
            }
            
            // Buscar combinaciones de 5 ventas consecutivas que sumen el total objetivo
            $matchesFound = 0;
            $bestMatches = [];
            
            for ($i = 0; $i <= count($todasLasVentas) - 5; $i++) {
                $ventasSubset = array_slice($todasLasVentas, $i, 5);
                $sumaTotal = array_sum(array_column($ventasSubset, 'monto_total'));
                $diferencia = abs($sumaTotal - $this->targetValues['total']);
                
                // Si la diferencia es menor a $0.01, es una coincidencia válida
                if ($diferencia < 0.01) {
                    $matchesFound++;
                    $ticketPromedio = $sumaTotal / 5;
                    
                    echo "<div class='match-found'>";
                    echo "<h4>✅ COINCIDENCIA ENCONTRADA #{$matchesFound}:</h4>";
                    echo "<table border='1' style='width:100%; margin:10px 0; border-collapse: collapse;'>";
                    echo "<tr style='background: #f0f0f0;'>";
                    echo "<th>ID</th><th>Fecha</th><th>Cliente</th><th>Monto</th><th>Método</th>";
                    echo "</tr>";
                    
                    foreach ($ventasSubset as $venta) {
                        echo "<tr>";
                        echo "<td>{$venta['id']}</td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($venta['fecha'])) . "</td>";
                        echo "<td>" . htmlspecialchars($venta['cliente_nombre']) . "</td>";
                        echo "<td>\$" . number_format($venta['monto_total'], 2) . "</td>";
                        echo "<td>{$venta['metodo_pago']}</td>";
                        echo "</tr>";
                    }
                    
                    echo "<tr style='background: #d4edda; font-weight: bold;'>";
                    echo "<td colspan='3'>TOTALES</td>";
                    echo "<td>\$" . number_format($sumaTotal, 2) . "</td>";
                    echo "<td>Promedio: \$" . number_format($ticketPromedio, 2) . "</td>";
                    echo "</tr>";
                    echo "</table>";
                    
                    // Validar si el ticket promedio también coincide
                    $diferenciaPromedio = abs($ticketPromedio - $this->targetValues['ticket_promedio']);
                    if ($diferenciaPromedio < 0.01) {
                        echo "<div class='perfect-match'>🎯 <strong>COINCIDENCIA PERFECTA:</strong> El ticket promedio también coincide (\$" . number_format($ticketPromedio, 2) . ")</div>";
                        $bestMatches[] = [
                            'ventas' => $ventasSubset,
                            'total' => $sumaTotal,
                            'promedio' => $ticketPromedio,
                            'fecha_inicio' => $ventasSubset[4]['fecha'], // La más antigua
                            'fecha_fin' => $ventasSubset[0]['fecha']     // La más reciente
                        ];
                    }
                    echo "</div>";
                }
            }
            
            if ($matchesFound === 0) {
                echo "<div class='info'>ℹ️ No se encontraron conjuntos de 5 ventas consecutivas que sumen exactamente \$13,746.85</div>";
                
                // Buscar el conjunto más cercano
                $this->findClosestMatch($todasLasVentas);
            } else {
                echo "<div class='success'>✅ Se encontraron {$matchesFound} conjunto(s) que coinciden con los valores del dashboard</div>";
                
                // Si encontramos coincidencias perfectas, mostrar análisis adicional
                if (!empty($bestMatches)) {
                    $this->analyzeMatchDetails($bestMatches);
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error en la búsqueda: " . $e->getMessage() . "</div>";
        }
    }
    
    /**
     * ✅ VALIDACIÓN MATEMÁTICA DE COHERENCIA
     */
    private function validateMathematicalConsistency() {
        echo "<h3>✅ Validación Matemática de Coherencia:</h3>";
        
        $calculatedAverage = $this->targetValues['total'] / $this->targetValues['cantidad'];
        $difference = abs($calculatedAverage - $this->targetValues['ticket_promedio']);
        
        echo "<div class='math-validation'>";
        echo "<p><strong>Cálculo matemático:</strong></p>";
        echo "<p>\$" . number_format($this->targetValues['total'], 2) . " ÷ " . $this->targetValues['cantidad'] . " = \$" . number_format($calculatedAverage, 2) . "</p>";
        echo "<p><strong>Ticket promedio reportado:</strong> \$" . number_format($this->targetValues['ticket_promedio'], 2) . "</p>";
        echo "<p><strong>Diferencia:</strong> \$" . number_format($difference, 2) . "</p>";
        
        if ($difference < 0.01) {
            echo "<div class='success'>✅ <strong>VALIDACIÓN EXITOSA:</strong> Los valores son matemáticamente coherentes</div>";
        } else {
            echo "<div class='error'>❌ <strong>ERROR DETECTADO:</strong> Inconsistencia matemática de \$" . number_format($difference, 2) . "</div>";
        }
        echo "</div>";
    }
    
    /**
     * 📅 BUSCAR EN DIFERENTES PERÍODOS
     */
    private function searchAcrossPeriods() {
        echo "<h3>📅 Búsqueda por Períodos Específicos:</h3>";
        
        $periods = [
            'hoy' => [
                'inicio' => date('Y-m-d') . ' 00:00:00',
                'fin' => date('Y-m-d') . ' 23:59:59',
                'nombre' => 'Hoy'
            ],
            'ayer' => [
                'inicio' => date('Y-m-d', strtotime('-1 day')) . ' 00:00:00',
                'fin' => date('Y-m-d', strtotime('-1 day')) . ' 23:59:59',
                'nombre' => 'Ayer'
            ],
            'esta_semana' => [
                'inicio' => date('Y-m-d', strtotime('monday this week')) . ' 00:00:00',
                'fin' => date('Y-m-d') . ' 23:59:59',
                'nombre' => 'Esta semana'
            ],
            'ultimo_mes' => [
                'inicio' => date('Y-m-d', strtotime('-30 days')) . ' 00:00:00',
                'fin' => date('Y-m-d') . ' 23:59:59',
                'nombre' => 'Últimos 30 días'
            ]
        ];
        
        foreach ($periods as $key => $period) {
            $this->analyzeSpecificPeriod($period['nombre'], $period['inicio'], $period['fin']);
        }
    }
    
    /**
     * 📊 ANALIZAR PERÍODO ESPECÍFICO
     */
    private function analyzeSpecificPeriod($nombrePeriodo, $fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        COUNT(*) as cantidad,
                        SUM(monto_total) as total,
                        AVG(monto_total) as promedio,
                        MIN(fecha) as primera_venta,
                        MAX(fecha) as ultima_venta
                    FROM ventas 
                    WHERE fecha BETWEEN :inicio AND :fin 
                    AND estado IN ('completado', 'completada')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ]);
            
            $resultado = $stmt->fetch();
            
            echo "<div class='period-analysis'>";
            echo "<h4>{$nombrePeriodo}:</h4>";
            
            if ($resultado['cantidad'] > 0) {
                echo "<ul>";
                echo "<li><strong>Cantidad:</strong> {$resultado['cantidad']} ventas</li>";
                echo "<li><strong>Total:</strong> \$" . number_format($resultado['total'], 2) . "</li>";
                echo "<li><strong>Promedio:</strong> \$" . number_format($resultado['promedio'], 2) . "</li>";
                echo "<li><strong>Período:</strong> " . date('d/m/Y H:i', strtotime($resultado['primera_venta'])) . " a " . date('d/m/Y H:i', strtotime($resultado['ultima_venta'])) . "</li>";
                echo "</ul>";
                
                // Comparar con valores objetivo
                $coincidencias = 0;
                if (abs($resultado['cantidad'] - $this->targetValues['cantidad']) == 0) {
                    echo "<span class='match'>✅ Cantidad coincide</span><br>";
                    $coincidencias++;
                }
                if (abs($resultado['total'] - $this->targetValues['total']) < 0.01) {
                    echo "<span class='match'>✅ Total coincide</span><br>";
                    $coincidencias++;
                }
                if (abs($resultado['promedio'] - $this->targetValues['ticket_promedio']) < 0.01) {
                    echo "<span class='match'>✅ Promedio coincide</span><br>";
                    $coincidencias++;
                }
                
                if ($coincidencias == 3) {
                    echo "<div class='perfect-period-match'>🎯 <strong>PERÍODO PERFECTO ENCONTRADO:</strong> Todos los valores coinciden</div>";
                } elseif ($coincidencias >= 2) {
                    echo "<div class='good-period-match'>⚡ <strong>PERÍODO PROMETEDOR:</strong> {$coincidencias}/3 valores coinciden</div>";
                }
                
            } else {
                echo "<p>No hay ventas en este período</p>";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>Error analizando período {$nombrePeriodo}: " . $e->getMessage() . "</div>";
        }
    }
    
    /**
     * 🔍 BUSCAR LA COINCIDENCIA MÁS CERCANA
     */
    private function findClosestMatch($ventas) {
        echo "<h4>🔍 Buscando la combinación más cercana:</h4>";
        
        $bestMatch = null;
        $smallestDifference = PHP_FLOAT_MAX;
        
        // Buscar en ventanas de 5 ventas
        for ($i = 0; $i <= count($ventas) - 5; $i++) {
            $subset = array_slice($ventas, $i, 5);
            $total = array_sum(array_column($subset, 'monto_total'));
            $difference = abs($total - $this->targetValues['total']);
            
            if ($difference < $smallestDifference) {
                $smallestDifference = $difference;
                $bestMatch = [
                    'ventas' => $subset,
                    'total' => $total,
                    'promedio' => $total / 5,
                    'diferencia' => $difference
                ];
            }
        }
        
        if ($bestMatch) {
            echo "<div class='closest-match'>";
            echo "<h5>⭐ Combinación más cercana (diferencia: \$" . number_format($bestMatch['diferencia'], 2) . "):</h5>";
            echo "<p><strong>Total:</strong> \$" . number_format($bestMatch['total'], 2) . " (objetivo: \$" . number_format($this->targetValues['total'], 2) . ")</p>";
            echo "<p><strong>Promedio:</strong> \$" . number_format($bestMatch['promedio'], 2) . " (objetivo: \$" . number_format($this->targetValues['ticket_promedio'], 2) . ")</p>";
            echo "</div>";
        }
    }
    
    /**
     * 📊 ANÁLISIS DETALLADO DE LAS MEJORES COINCIDENCIAS
     */
    private function analyzeMatchDetails($matches) {
        echo "<h3>📊 Análisis Detallado de Coincidencias Perfectas:</h3>";
        
        foreach ($matches as $index => $match) {
            echo "<div class='detailed-match'>";
            echo "<h4>Coincidencia #" . ($index + 1) . ":</h4>";
            
            // Análisis por método de pago
            $metodosPago = [];
            foreach ($match['ventas'] as $venta) {
                $metodo = $venta['metodo_pago'];
                if (!isset($metodosPago[$metodo])) {
                    $metodosPago[$metodo] = ['cantidad' => 0, 'total' => 0];
                }
                $metodosPago[$metodo]['cantidad']++;
                $metodosPago[$metodo]['total'] += $venta['monto_total'];
            }
            
            echo "<h5>Distribución por Método de Pago:</h5>";
            echo "<table border='1' style='width:100%; margin:10px 0; border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th>Método</th><th>Cantidad</th><th>Total</th><th>%</th></tr>";
            
            foreach ($metodosPago as $metodo => $data) {
                $porcentaje = ($data['total'] / $match['total']) * 100;
                echo "<tr>";
                echo "<td>{$metodo}</td>";
                echo "<td>{$data['cantidad']}</td>";
                echo "<td>\$" . number_format($data['total'], 2) . "</td>";
                echo "<td>" . number_format($porcentaje, 1) . "%</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Análisis temporal
            $fechas = array_column($match['ventas'], 'fecha');
            $rangoTemporal = (strtotime(max($fechas)) - strtotime(min($fechas))) / 3600; // horas
            
            echo "<p><strong>Rango temporal:</strong> " . number_format($rangoTemporal, 1) . " horas</p>";
            echo "<p><strong>Período:</strong> " . date('d/m/Y H:i', strtotime($match['fecha_inicio'])) . " a " . date('d/m/Y H:i', strtotime($match['fecha_fin'])) . "</p>";
            
            echo "</div>";
        }
    }
}

// ===== EJECUCIÓN DEL VALIDADOR =====

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>🎯 Validador de Métricas del Dashboard</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; background: #f5f5f5; }";
echo "h1, h2, h3 { color: #333; }";
echo "table { margin: 10px 0; background: white; }";
echo "th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }";
echo ".dashboard-target { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2196f3; }";
echo ".match-found { background: #f1f8e9; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #4caf50; }";
echo ".perfect-match { background: #fff3e0; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #ff9800; color: #e65100; font-weight: bold; }";
echo ".perfect-period-match { background: #e8f5e8; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #4caf50; color: #2e7d32; font-weight: bold; }";
echo ".good-period-match { background: #fff8e1; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #ffc107; color: #f57c00; font-weight: bold; }";
echo ".warning { color: #856404; background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }";
echo ".error { color: #721c24; background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0; }";
echo ".success { color: #155724; background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0; }";
echo ".info { color: #0c5460; background: #d1ecf1; padding: 10px; border-left: 4px solid #17a2b8; margin: 10px 0; }";
echo ".math-validation { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #dee2e6; }";
echo ".period-analysis { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #dee2e6; }";
echo ".closest-match { background: #fff9c4; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffeb3b; }";
echo ".detailed-match { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #dee2e6; }";
echo ".match { color: #28a745; font-weight: bold; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🎯 VALIDADOR DE MÉTRICAS DEL DASHBOARD</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Objetivo:</strong> Validar específicamente los valores del dashboard: Ticket promedio \$2,749.37, Total \$13,746.85, Cantidad 5</p>";

$validator = new DashboardMetricsValidator();
$validator->validateDashboardMetrics();

echo "<div style='margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;'>";
echo "<h3>🎯 Conclusión del Análisis:</h3>";
echo "<p>Este validador busca específicamente las combinaciones de ventas que coinciden con los valores mostrados en la imagen del dashboard.</p>";
echo "<p>Si se encuentran coincidencias perfectas, confirma que los valores del dashboard son correctos y provienen de datos reales del sistema.</p>";
echo "</div>";

echo "</body>";
echo "</html>";
?>