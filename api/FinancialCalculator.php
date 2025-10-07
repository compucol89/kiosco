<?php
/**
 * CALCULADORA FINANCIERA UNIFICADA
 * Elimina inconsistencias entre frontend y backend
 */

class FinancialCalculator {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calcular utilidad de producto con datos verificados
     */
    public function calcularUtilidadProducto($venta, $producto, $periodo = null) {
        // 1. Validar y limpiar datos de entrada
        $precioVenta = $this->validarPrecio($venta['precio_unitario'] ?? 0);
        $precioCompra = $this->obtenerCostoReal($producto['id'] ?? 0, $venta['fecha'] ?? date('Y-m-d'));
        $cantidad = $this->validarCantidad($venta['cantidad'] ?? 0);
        
        // 2. Cálculo estándar unificado
        $utilidadUnitaria = $precioVenta - $precioCompra;
        $utilidadTotal = $utilidadUnitaria * $cantidad;
        $ingresoTotal = $precioVenta * $cantidad;
        
        // 3. Margen porcentual
        $margen = $ingresoTotal > 0 ? ($utilidadTotal / $ingresoTotal) * 100 : 0;
        
        return [
            'utilidad_unitaria' => round($utilidadUnitaria, 2),
            'utilidad_total' => round($utilidadTotal, 2),
            'ingreso_total' => round($ingresoTotal, 2),
            'margen_porcentaje' => round($margen, 2),
            'precio_compra_usado' => $precioCompra,
            'precio_venta_usado' => $precioVenta,
            'cantidad' => $cantidad,
            'metodo_costo' => $this->lastCostMethod
        ];
    }
    
    /**
     * Aplicar gastos proporcionales al período
     */
    public function aplicarGastosProporcionales($utilidadBruta, $gastosMensuales, $periodo) {
        $factorTiempo = $this->calcularFactorTemporal($periodo);
        $gastosProporcionados = $gastosMensuales * $factorTiempo;
        
        return [
            'utilidad_bruta' => round($utilidadBruta, 2),
            'gastos_proporcionales' => round($gastosProporcionados, 2),
            'utilidad_neta' => round($utilidadBruta - $gastosProporcionados, 2),
            'factor_temporal' => $factorTiempo,
            'gastos_mensuales' => $gastosMensuales
        ];
    }
    
    private $lastCostMethod = 'unknown';
    
    /**
     * Obtener costo real con fallback inteligente
     */
    private function obtenerCostoReal($productoId, $fecha) {
        try {
            // 1. Costo actual del producto (más confiable)
            $stmt = $this->pdo->prepare("SELECT precio_compra, precio_costo FROM productos WHERE id = ?");
            $stmt->execute([$productoId]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($producto && $producto['precio_compra'] > 0) {
                $this->lastCostMethod = 'producto_precio_compra';
                return floatval($producto['precio_compra']);
            }
            
            if ($producto && $producto['precio_costo'] > 0) {
                $this->lastCostMethod = 'producto_precio_costo';
                return floatval($producto['precio_costo']);
            }
            
            // 2. Costo histórico de ventas similares
            $stmt = $this->pdo->prepare("
                SELECT precio_compra 
                FROM venta_detalles 
                WHERE producto_id = ? AND precio_compra > 0 
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$productoId]);
            $costoHistorico = $stmt->fetchColumn();
            
            if ($costoHistorico > 0) {
                $this->lastCostMethod = 'historico_venta';
                return floatval($costoHistorico);
            }
            
            // 3. Promedio de categoría
            $stmt = $this->pdo->prepare("
                SELECT AVG(precio_compra) as promedio
                FROM productos p1
                WHERE p1.categoria = (SELECT categoria FROM productos WHERE id = ?) 
                AND precio_compra > 0
            ");
            $stmt->execute([$productoId]);
            $promedioCategoria = $stmt->fetchColumn();
            
            if ($promedioCategoria > 0) {
                $this->lastCostMethod = 'promedio_categoria';
                return floatval($promedioCategoria);
            }
            
            // 4. Estimación conservadora (75% del precio de venta promedio)
            $stmt = $this->pdo->prepare("SELECT precio_venta FROM productos WHERE id = ?");
            $stmt->execute([$productoId]);
            $precioVenta = $stmt->fetchColumn();
            
            if ($precioVenta > 0) {
                $this->lastCostMethod = 'estimacion_75_porciento';
                return floatval($precioVenta) * 0.75; // Más conservador que 60%
            }
            
            // 5. Fallback final
            $this->lastCostMethod = 'fallback_minimo';
            return 0.01; // Evitar división por cero
            
        } catch (Exception $e) {
            error_log("Error obteniendo costo real: " . $e->getMessage());
            $this->lastCostMethod = 'error_fallback';
            return 0.01;
        }
    }
    
    /**
     * Calcular factor temporal para gastos proporcionales
     */
    private function calcularFactorTemporal($periodo) {
        if (!$periodo || !isset($periodo['tipo'])) {
            // Fallback: calcular días entre fechas
            $inicio = $periodo['inicio'] ?? date('Y-m-d');
            $fin = $periodo['fin'] ?? date('Y-m-d');
            $dias = (strtotime($fin) - strtotime($inicio)) / (24 * 3600) + 1;
            return $dias / 30; // Convertir a fracción mensual
        }
        
        switch($periodo['tipo']) {
            case 'diario': return 1/30;
            case 'semanal': return 7/30;
            case 'mensual': return 1;
            case 'trimestral': return 3;
            case 'anual': return 12;
            default: return 1/30; // Default diario
        }
    }
    
    /**
     * Validar precio de entrada
     */
    private function validarPrecio($precio) {
        $precio = floatval($precio);
        return max(0, $precio); // No permitir precios negativos
    }
    
    /**
     * Validar cantidad de entrada
     */
    private function validarCantidad($cantidad) {
        $cantidad = floatval($cantidad);
        return max(0, $cantidad); // No permitir cantidades negativas
    }
    
    /**
     * Generar reporte de métodos de costo utilizados
     */
    public function getReporteCostos() {
        return [
            'metodo_utilizado' => $this->lastCostMethod,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
?>