<?php
/**
 * 🧮 CALCULADORA FINANCIERA CORREGIDA - SPACEX GRADE
 * Implementa la fórmula CORRECTA según especificación del usuario:
 * GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO PRODUCTO
 */

class CalculadoraFinancieraCorregida {
    
    private $productosLookup;
    private $debug = [];
    
    public function __construct($productosLookup) {
        $this->productosLookup = $productosLookup;
    }
    
    /**
     * 🎯 FÓRMULA CORRECTA: GANANCIA NETA = (PRECIO VENTA - DESCUENTO) - COSTO
     * Ejemplo: Precio $1,000, Descuento $100, Costo $600 = Ganancia $300
     */
    public function calcularGananciaNeta($productoId, $cantidad, $precioVentaOriginal, $descuentoAplicado = 0, $ventaId = null) {
        $producto = $this->productosLookup[$productoId] ?? null;
        
        if (!$producto) {
            return [
                'error' => 'Producto no encontrado',
                'producto_id' => $productoId
            ];
        }
        
        // Datos base limpios
        $costo_unitario = floatval($producto['precio_costo']);
        $precio_venta_original = floatval($precioVentaOriginal);
        $descuento_unitario = floatval($descuentoAplicado);
        $cantidad_numerica = floatval($cantidad);
        
        // 🧮 CÁLCULO CORRECTO SEGÚN ESPECIFICACIÓN DEL USUARIO:
        // PASO 1: Precio final después de descuento
        $precio_venta_final = $precio_venta_original - $descuento_unitario;
        
        // PASO 2: Ganancia neta por unidad
        $ganancia_neta_unitaria = $precio_venta_final - $costo_unitario;
        
        // PASO 3: Totales
        $costo_total = $costo_unitario * $cantidad_numerica;
        $ingreso_bruto_total = $precio_venta_original * $cantidad_numerica;
        $descuento_total = $descuento_unitario * $cantidad_numerica;
        $ingreso_neto_total = $ingreso_bruto_total - $descuento_total;
        $ganancia_neta_total = $ganancia_neta_unitaria * $cantidad_numerica;
        
        // Validaciones
        $margen_porcentaje = $precio_venta_final > 0 ? 
            ($ganancia_neta_unitaria / $precio_venta_final) * 100 : 0;
        
        $porcentaje_aumento = $costo_unitario > 0 ? 
            (($precio_venta_final - $costo_unitario) / $costo_unitario) * 100 : 0;
        
        // Debug para verificación
        $this->debug[] = [
            'producto_id' => $productoId,
            'venta_id' => $ventaId,
            'calculo_paso_a_paso' => [
                '1_precio_original' => $precio_venta_original,
                '2_descuento_aplicado' => $descuento_unitario,
                '3_precio_final' => $precio_venta_final,
                '4_costo_producto' => $costo_unitario,
                '5_ganancia_neta_unitaria' => $ganancia_neta_unitaria,
                '6_cantidad' => $cantidad_numerica,
                '7_ganancia_neta_total' => $ganancia_neta_total
            ],
            'formula_aplicada' => "($precio_venta_original - $descuento_unitario) - $costo_unitario = $ganancia_neta_unitaria",
            'verificacion' => $ganancia_neta_unitaria === ($precio_venta_final - $costo_unitario)
        ];
        
        return [
            'producto_id' => $productoId,
            'nombre' => $producto['nombre'],
            'categoria' => $producto['categoria'],
            'codigo' => $producto['codigo'] ?? '',
            'cantidad' => $cantidad_numerica,
            
            // Precios y costos
            'precio_venta_original' => round($precio_venta_original, 2),
            'descuento_unitario' => round($descuento_unitario, 2),
            'precio_venta_final' => round($precio_venta_final, 2),
            'costo_unitario' => round($costo_unitario, 2),
            
            // Totales
            'ingreso_bruto_total' => round($ingreso_bruto_total, 2),
            'descuento_total' => round($descuento_total, 2),
            'ingreso_neto_total' => round($ingreso_neto_total, 2),
            'costo_total' => round($costo_total, 2),
            
            // 🎯 GANANCIA NETA CORRECTA
            'ganancia_neta_unitaria' => round($ganancia_neta_unitaria, 2),
            'ganancia_neta_total' => round($ganancia_neta_total, 2),
            
            // Métricas
            'margen_porcentaje' => round($margen_porcentaje, 2),
            'porcentaje_aumento' => round($porcentaje_aumento, 2),
            'rentabilidad' => $ganancia_neta_unitaria > 0 ? 'RENTABLE' : 'PERDIDA',
            
            'venta_id' => $ventaId
        ];
    }
    
    /**
     * 🔍 Procesar venta completa con fórmula corregida
     */
    public function procesarVentaCorregida($venta) {
        $ventaId = $venta['id'];
        $detallesJson = $venta['detalles_json'];
        $montoTotalRegistrado = floatval($venta['monto_total']);
        $descuentoGlobalVenta = floatval($venta['descuento'] ?? 0);
        $metodoPago = $venta['metodo_pago'];
        
        // Inicializar acumuladores
        $productos = [];
        $totalGananciaNeta = 0;
        $totalCostos = 0;
        $totalIngresosBrutos = 0;
        $totalDescuentos = 0;
        $totalIngresosNetos = 0;
        
        if (!empty($detallesJson)) {
            $detalles = json_decode($detallesJson, true);
            
            if ($detalles && isset($detalles['cart'])) {
                foreach ($detalles['cart'] as $item) {
                    // Calcular descuento proporcional por ítem si hay descuento global
                    $precioItemOriginal = floatval($item['price'] ?? $item['precio'] ?? 0);
                    $cantidadItem = floatval($item['quantity'] ?? $item['cantidad'] ?? 1);
                    $subtotalItem = $precioItemOriginal * $cantidadItem;
                    
                    // Descuento proporcional
                    $descuentoItem = 0;
                    if ($descuentoGlobalVenta > 0 && $montoTotalRegistrado > 0) {
                        $proporcionItem = $subtotalItem / ($montoTotalRegistrado + $descuentoGlobalVenta);
                        $descuentoItem = ($descuentoGlobalVenta * $proporcionItem) / $cantidadItem;
                    }
                    
                    // Calcular ganancia neta con fórmula corregida
                    $calculoProducto = $this->calcularGananciaNeta(
                        $item['id'] ?? $item['codigo'] ?? 'GENERICO',
                        $cantidadItem,
                        $precioItemOriginal,
                        $descuentoItem,
                        $ventaId
                    );
                    
                    if (!isset($calculoProducto['error'])) {
                        $productos[] = $calculoProducto;
                        $totalGananciaNeta += $calculoProducto['ganancia_neta_total'];
                        $totalCostos += $calculoProducto['costo_total'];
                        $totalIngresosBrutos += $calculoProducto['ingreso_bruto_total'];
                        $totalDescuentos += $calculoProducto['descuento_total'];
                        $totalIngresosNetos += $calculoProducto['ingreso_neto_total'];
                    }
                }
            }
        }
        
        // Verificar coherencia
        $diferenciaMonto = abs($montoTotalRegistrado - $totalIngresosNetos);
        $coherenciaOk = $diferenciaMonto < 0.01; // Tolerancia de 1 centavo
        
        return [
            'venta_id' => $ventaId,
            'fecha' => $venta['fecha'],
            'cliente' => $venta['cliente_nombre'] ?? 'Consumidor Final',
            'metodo_pago' => $metodoPago,
            'productos' => $productos,
            'resumen' => [
                'cantidad_productos' => count($productos),
                'total_costos' => round($totalCostos, 2),
                'total_ingresos_brutos' => round($totalIngresosBrutos, 2),
                'total_descuentos' => round($totalDescuentos, 2),
                'total_ingresos_netos' => round($totalIngresosNetos, 2),
                
                // 🎯 GANANCIA NETA CORRECTA
                'ganancia_neta' => round($totalGananciaNeta, 2),
                
                'monto_total_registrado' => $montoTotalRegistrado,
                'diferencia_calculo' => round($diferenciaMonto, 2),
                'coherencia_ok' => $coherenciaOk,
                
                // Métricas adicionales
                'margen_promedio' => $totalIngresosNetos > 0 ? 
                    round(($totalGananciaNeta / $totalIngresosNetos) * 100, 2) : 0,
                'roi' => $totalCostos > 0 ? 
                    round(($totalGananciaNeta / $totalCostos) * 100, 2) : 0
            ]
        ];
    }
    
    /**
     * 📊 Calcular resumen general con ganancias netas correctas
     */
    public function calcularResumenGeneral($ventasProcesadas) {
        $resumen = [
            'total_ventas' => count($ventasProcesadas),
            'total_productos_vendidos' => 0,
            'total_costos' => 0,
            'total_ingresos_brutos' => 0,
            'total_descuentos' => 0,
            'total_ingresos_netos' => 0,
            'total_ganancia_neta' => 0,
            'diferencias_detectadas' => 0
        ];
        
        foreach ($ventasProcesadas as $venta) {
            $resumenVenta = $venta['resumen'];
            $resumen['total_productos_vendidos'] += $resumenVenta['cantidad_productos'];
            $resumen['total_costos'] += $resumenVenta['total_costos'];
            $resumen['total_ingresos_brutos'] += $resumenVenta['total_ingresos_brutos'];
            $resumen['total_descuentos'] += $resumenVenta['total_descuentos'];
            $resumen['total_ingresos_netos'] += $resumenVenta['total_ingresos_netos'];
            $resumen['total_ganancia_neta'] += $resumenVenta['ganancia_neta'];
            
            if (!$resumenVenta['coherencia_ok']) {
                $resumen['diferencias_detectadas']++;
            }
        }
        
        // Calcular métricas finales
        $resumen['margen_bruto_porcentaje'] = $resumen['total_ingresos_netos'] > 0 ? 
            ($resumen['total_ganancia_neta'] / $resumen['total_ingresos_netos']) * 100 : 0;
        
        $resumen['roi_porcentaje'] = $resumen['total_costos'] > 0 ? 
            ($resumen['total_ganancia_neta'] / $resumen['total_costos']) * 100 : 0;
        
        $resumen['ticket_promedio'] = $resumen['total_ventas'] > 0 ? 
            $resumen['total_ingresos_netos'] / $resumen['total_ventas'] : 0;
        
        $resumen['ganancia_por_venta'] = $resumen['total_ventas'] > 0 ? 
            $resumen['total_ganancia_neta'] / $resumen['total_ventas'] : 0;
        
        return $resumen;
    }
    
    public function getDebug() {
        return $this->debug;
    }
}
?>
