<?php
/*
 * API de Inventario Inteligente - Sistema Optimizado
 * Incluye análisis predictivo, clasificación ABC, alertas inteligentes y más
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

try {
    $pdo = Conexion::obtenerConexion();
    if (!$pdo) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    $accion = $_GET['action'] ?? '';
    
    switch ($accion) {
        case 'productos':
            obtenerProductosEnriquecidos($pdo);
            break;
        case 'analisis-abc':
            obtenerAnalisisABC($pdo);
            break;
        case 'predicciones':
            obtenerPredicciones($pdo);
            break;
        case 'alertas':
            obtenerAlertasInteligentes($pdo);
            break;
        case 'sugerencias-pedidos':
            obtenerSugerenciasPedidos($pdo);
            break;
        case 'metricas':
            obtenerMetricasAvanzadas($pdo);
            break;
        case 'rotacion':
            obtenerAnalisisRotacion($pdo);
            break;
        case 'optimizacion':
            obtenerOptimizacionStock($pdo);
            break;
        case 'demanda':
            calcularDemandaPredicativa($pdo);
            break;
        case 'proveedores':
            analizarProveedores($pdo);
            break;
        case 'analisis-ia':
            obtenerAnalisisIA($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no reconocida']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Obtener productos con datos enriquecidos para análisis
 */
function obtenerProductosEnriquecidos($pdo) {
    try {
        // Consulta optimizada con análisis de ventas
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                COALESCE(v.ventas_total, 0) as ventas_total,
                COALESCE(v.ventas_30_dias, 0) as ventas_30_dias,
                COALESCE(v.ventas_7_dias, 0) as ventas_7_dias,
                COALESCE(v.ultima_venta, NULL) as ultima_venta,
                COALESCE(prov.nombre, 'Sin proveedor') as proveedor_nombre,
                COALESCE(prov.tiempo_entrega, 7) as tiempo_entrega_dias,
                (p.stock * p.precio_costo) as valor_inventario
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(cantidad) as ventas_total,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN cantidad ELSE 0 END) as ventas_30_dias,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as ventas_7_dias,
                    MAX(fecha) as ultima_venta
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            LEFT JOIN proveedores prov ON p.proveedor = prov.nombre
            ORDER BY p.nombre
        ");
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ✅ OPTIMIZADO: Enriquecer productos con cálculos batch para mejor rendimiento
        $productosEnriquecidos = enriquecerProductosBatch($productos);
        
        echo json_encode([
            'success' => true,
            'productos' => $productosEnriquecidos,
            'total' => count($productosEnriquecidos)
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al obtener productos: ' . $e->getMessage());
    }
}

/**
 * ✅ OPTIMIZADO: Enriquecer productos en batch para mejor rendimiento O(n) vs O(n²)
 */
function enriquecerProductosBatch($productos) {
    // Pre-calcular datos compartidos una sola vez
    $valoresInventario = array_map(fn($p) => ($p['stock'] ?? 0) * ($p['precio_costo'] ?? 0), $productos);
    $valoresStock = array_column($productos, 'stock');
    $valoresPrecios = array_column($productos, 'precio_venta');
    
    // Calcular percentiles una sola vez
    $p95Inventario = calcularPercentil(array_filter($valoresInventario, fn($v) => $v > 0), 95);
    $p95Stock = calcularPercentil(array_filter($valoresStock, fn($v) => $v > 0), 95);
    $p95Precio = calcularPercentil(array_filter($valoresPrecios, fn($v) => $v > 0), 95);
    
    // Preparar productos con valores calculados para ABC
    $productosConValores = [];
    foreach ($productos as $index => $producto) {
        $valorVentasProducto = ($producto['ventas_30_dias'] ?? 0) * ($producto['precio_venta'] ?? 0);
        if ($valorVentasProducto <= 0) {
            $valorVentasProducto = $valoresInventario[$index];
        }
        $productosConValores[] = [
            'index' => $index,
            'id' => $producto['id'],
            'valor' => $valorVentasProducto
        ];
    }
    
    // Ordenar por valor y asignar clasificación ABC una sola vez
    usort($productosConValores, fn($a, $b) => $b['valor'] <=> $a['valor']);
    $totalProductos = count($productosConValores);
    $umbralA = $totalProductos * 0.20;
    $umbralB = $totalProductos * 0.50;
    
    $clasificacionesABC = [];
    foreach ($productosConValores as $posicion => $item) {
        if ($posicion < $umbralA) {
            $clasificacionesABC[$item['id']] = 'A';
        } elseif ($posicion < $umbralB) {
            $clasificacionesABC[$item['id']] = 'B';
        } else {
            $clasificacionesABC[$item['id']] = 'C';
        }
    }
    
    // Enriquecer cada producto usando datos pre-calculados
    $productosEnriquecidos = [];
    foreach ($productos as $index => $producto) {
        $productosEnriquecidos[] = enriquecerProductoIndividual(
            $producto, 
            $clasificacionesABC,
            $p95Inventario,
            $p95Stock, 
            $p95Precio,
            $valoresInventario[$index]
        );
    }
    
    return $productosEnriquecidos;
}

/**
 * Enriquecer producto individual con datos pre-calculados
 */
function enriquecerProductoIndividual($producto, $clasificacionesABC, $p95Inventario, $p95Stock, $p95Precio, $valorInventario) {
  // ✅ MEJORADO: Cálculo de rotación más preciso y realista
  $ventasDiarias = 0;
  if ($producto['ventas_30_dias'] > 0) {
      $ventasDiarias = $producto['ventas_30_dias'] / 30;
  } elseif ($producto['ventas_7_dias'] > 0) {
      // Extrapolación de ventas semanales (más conservadora)
      $ventasDiarias = ($producto['ventas_7_dias'] / 7) * 0.8; // Factor de conservación
  } elseif ($producto['ventas_total'] > 0) {
      // Asumir que las ventas totales son del último año
      $ventasDiarias = $producto['ventas_total'] / 365;
  } else {
      // Para productos sin ventas, usar categoria para estimar rotación
      $categoria = strtolower($producto['categoria'] ?? 'general');
      switch($categoria) {
          case 'bebidas':
          case 'gaseosas':
              $ventasDiarias = 0.5; // Productos de alta rotación
              break;
          case 'golosinas':
          case 'snacks':
              $ventasDiarias = 0.3; // Media-alta rotación
              break;
          case 'almacen':
          case 'limpieza':
              $ventasDiarias = 0.1; // Baja rotación
              break;
          default:
              $ventasDiarias = 0.2; // Rotación promedio
      }
  }
  
  $velocidadRotacion = $ventasDiarias;
    
    // Calcular días de stock restante
    $diasStock = $velocidadRotacion > 0 ? 
        ceil($producto['stock'] / $velocidadRotacion) : 999;
    
    // Calcular urgencia de reabastecimiento (0-100)
    $urgencia = calcularUrgencia($producto['stock'], $producto['stock_minimo'], $diasStock);
    
    // Clasificación ABC basada en valor de ventas
    // ✅ OPTIMIZADO: Usar clasificación ABC pre-calculada
    $claseABC = $clasificacionesABC[$producto['id']] ?? 'C';
    
    // Calcular punto de reorden inteligente
    $tiempoEntrega = $producto['tiempo_entrega_dias'] ?? 7; // Default 7 días
    $puntoReorden = calcularPuntoReorden(
        $velocidadRotacion, 
        $tiempoEntrega, 
        $producto['stock_minimo']
    );
    
    // Calcular cantidad óptima de pedido (EOQ simplificado)
    $cantidadOptima = calcularCantidadOptima(
        $velocidadRotacion * 30, // demanda mensual
        $producto['precio_costo']
    );
    
    // Análisis de rentabilidad
    $margenUnitario = $producto['precio_venta'] - $producto['precio_costo'];
    $rentabilidad = $producto['precio_costo'] > 0 ? 
        ($margenUnitario / $producto['precio_costo']) * 100 : 0;
    
    // CORREGIDO: Cálculo de rotación anual realista
    $stockPromedio = $producto['stock'] > 0 ? $producto['stock'] : 1;
    $rotacionAnual = ($ventasDiarias * 365) / $stockPromedio;
    
    return array_merge($producto, [
        'velocidad_rotacion' => round($velocidadRotacion, 2),
        'dias_stock' => $diasStock,
        'urgencia' => $urgencia,
        'clase_abc' => $claseABC,
        'punto_reorden' => $puntoReorden,
        'cantidad_optima_pedido' => $cantidadOptima,
        'rentabilidad' => round($rentabilidad, 2),
        'rotacion_anual' => round($rotacionAnual, 2),
        'valor_inventario' => $producto['valor_inventario'],
        'necesita_pedido' => $producto['stock'] <= $puntoReorden,
        'estado_stock' => determinarEstadoStock($producto['stock'], $producto['stock_minimo'], $urgencia),
        // NUEVOS CAMPOS PARA IA Y VALIDACIÓN (OPTIMIZADOS)
        'performance_score' => calcularPerformanceScore($rentabilidad, $rotacionAnual, $urgencia),
        'recomendacion_ia' => generarRecomendacionBasica($producto, $rentabilidad, $rotacionAnual, $urgencia),
        'es_outlier' => detectarOutlierOptimizado($producto, $p95Inventario, $p95Stock, $p95Precio, $valorInventario),
        'alertas_validacion' => generarAlertasValidacion($producto)
    ]);
}

/**
 * OPTIMIZADO: Calcular urgencia de reabastecimiento más inteligente
 */
function calcularUrgencia($stock, $stockMinimo, $diasStock) {
    if ($stock <= 0) return 100; // Crítico
    
    $stockMinimo = max($stockMinimo, 3); // Mínimo 3 unidades (menos estricto)
    $ratioStock = $stock / $stockMinimo;
    
    // PRIORIDAD 1: Días de stock (más importante)
    if ($diasStock <= 1) return 100; // Crítico: 1 día
    if ($diasStock <= 3) return 90;  // Muy urgente: 3 días
    if ($diasStock <= 7) return 70;  // Urgente: 1 semana
    if ($diasStock <= 14) return 50; // Moderado: 2 semanas
    
    // PRIORIDAD 2: Ratio de stock mínimo (menos estricto)
    if ($ratioStock <= 0.3) return 85; // Muy bajo
    if ($ratioStock <= 0.7) return 60; // Bajo
    if ($ratioStock <= 1.2) return 35; // Cerca del mínimo
    if ($ratioStock <= 2.0) return 20; // Aceptable
    
    return 5; // Stock excelente (reducido de 10 a 5)
}

/**
 * ✅ CORREGIDO: Clasificación ABC usando principio de Pareto dinámico
 */
function calcularClaseABC($producto, $todosLosProductos) {
    // Calcular el valor de ventas total del producto (ventas * precio)
    $valorTotalProducto = ($producto['ventas_30_dias'] ?? 0) * ($producto['precio_venta'] ?? 0);
    
    // Si no hay ventas, clasificar por valor de inventario
    if ($valorTotalProducto <= 0) {
        $valorTotalProducto = $producto['valor_inventario'] ?? 0;
    }
    
    // Obtener todos los valores de productos y ordenarlos
    $valoresProductos = [];
    foreach ($todosLosProductos as $p) {
        $valor = ($p['ventas_30_dias'] ?? 0) * ($p['precio_venta'] ?? 0);
        if ($valor <= 0) {
            $valor = $p['valor_inventario'] ?? 0;
        }
        $valoresProductos[] = $valor;
    }
    
    // Ordenar de mayor a menor
    rsort($valoresProductos);
    $totalProductos = count($valoresProductos);
    
    // Aplicar principio de Pareto: 20% A, 30% B, 50% C
    $umbralA = $totalProductos * 0.20; // Top 20%
    $umbralB = $totalProductos * 0.50; // Top 50% (20% A + 30% B)
    
    // Encontrar la posición de este producto en el ranking
    $posicion = array_search($valorTotalProducto, $valoresProductos);
    
    if ($posicion !== false) {
        if ($posicion < $umbralA) return 'A';
        if ($posicion < $umbralB) return 'B';
    }
    
    return 'C';
}

/**
 * Calcular punto de reorden inteligente
 */
function calcularPuntoReorden($demandaDiaria, $tiempoEntrega, $stockMinimo) {
    $stockSeguridad = max($stockMinimo, $demandaDiaria * 3); // 3 días de seguridad
    $demandaDuranteEntrega = $demandaDiaria * $tiempoEntrega;
    
    return ceil($demandaDuranteEntrega + $stockSeguridad);
}

/**
 * Calcular cantidad óptima de pedido (EOQ simplificado)
 */
function calcularCantidadOptima($demandaMensual, $costo) {
    if ($demandaMensual <= 0) return 10;
    
    // EOQ simplificado: sqrt(2 * D * S / H)
    // D = demanda anual, S = costo de pedido, H = costo de almacenamiento
    $demandaAnual = $demandaMensual * 12;
    $costoPedido = 500; // Costo fijo estimado por pedido
    $costoAlmacenamiento = $costo * 0.25; // 25% del costo como almacenamiento anual
    
    if ($costoAlmacenamiento <= 0) return ceil($demandaMensual);
    
    $eoq = sqrt((2 * $demandaAnual * $costoPedido) / $costoAlmacenamiento);
    
    return max(ceil($eoq), ceil($demandaMensual * 0.5)); // Mínimo medio mes de demanda
}

/**
 * Determinar estado del stock
 */
function determinarEstadoStock($stock, $stockMinimo, $urgencia) {
    if ($stock <= 0) return 'sin_stock';
    if ($urgencia >= 90) return 'critico';
    if ($urgencia >= 70) return 'bajo';
    if ($urgencia >= 40) return 'medio';
    return 'optimo';
}

/**
 * NUEVO: Calcular score de performance del producto (0-100)
 */
function calcularPerformanceScore($rentabilidad, $rotacionAnual, $urgencia) {
    // Score basado en rentabilidad (40%), rotación (40%), y gestión de stock (20%)
    $scoreRentabilidad = min(100, max(0, $rentabilidad * 2)); // Hasta 50% = 100 puntos
    $scoreRotacion = min(100, max(0, $rotacionAnual * 10)); // Hasta 10x = 100 puntos
    $scoreStock = 100 - $urgencia; // Menos urgencia = mejor gestión
    
    $scoreTotal = ($scoreRentabilidad * 0.4) + ($scoreRotacion * 0.4) + ($scoreStock * 0.2);
    
    return round($scoreTotal, 1);
}

/**
 * NUEVO: Generar recomendación básica por producto
 */
function generarRecomendacionBasica($producto, $rentabilidad, $rotacionAnual, $urgencia) {
    $recomendaciones = [];
    
    // Análisis de rentabilidad
    if ($rentabilidad < 10) {
        $recomendaciones[] = "💰 PRECIO: Margen muy bajo ({$rentabilidad}%) - Revisar precio de venta";
    } elseif ($rentabilidad > 80) {
        $recomendaciones[] = "⚡ OPORTUNIDAD: Margen alto ({$rentabilidad}%) - Potenciar ventas";
    }
    
    // Análisis de rotación
    if ($rotacionAnual < 2) {
        $recomendaciones[] = "📦 STOCK: Rotación lenta ({$rotacionAnual}x) - Reducir inventario";
    } elseif ($rotacionAnual > 20) {
        $recomendaciones[] = "🚀 ÉXITO: Alta rotación ({$rotacionAnual}x) - Asegurar disponibilidad";
    }
    
    // Análisis de stock
    if ($producto['stock'] <= 0) {
        $recomendaciones[] = "🚨 CRÍTICO: Sin stock - Pedido urgente";
    } elseif ($urgencia >= 90) {
        $recomendaciones[] = "⚠️ URGENTE: Stock crítico - Programar pedido YA";
    }
    
    // Análisis ABC
    if (isset($producto['clase_abc']) && $producto['clase_abc'] === 'A' && $producto['stock'] <= ($producto['stock_minimo'] ?? 0)) {
        $recomendaciones[] = "⭐ PRIORIDAD: Producto clase A con stock bajo";
    }
    
    return empty($recomendaciones) ? 
        "✅ ÓPTIMO: Producto con buen rendimiento general" : 
        implode(" | ", $recomendaciones);
}

/**
 * ✅ OPTIMIZADO: Detectar outliers usando percentiles pre-calculados
 */
function detectarOutlierOptimizado($producto, $p95Inventario, $p95Stock, $p95Precio, $valorInventario) {
    $stock = $producto['stock'] ?? 0;
    $precioVenta = $producto['precio_venta'] ?? 0;
    
    $outliers = [];
    
    if ($valorInventario > $p95Inventario) {
        $outliers[] = 'valor_inventario_alto';
    }
    
    if ($stock > $p95Stock) {
        $outliers[] = 'stock_excesivo';
    }
    
    if ($precioVenta > $p95Precio) {
        $outliers[] = 'precio_premium';
    }
    
    return $outliers;
}

/**
 * ✅ LEGACY: Detectar productos outliers (mantenido para compatibilidad)
 */
function detectarOutlier($producto, $todosLosProductos) {
    $valorInventario = $producto['valor_inventario'] ?? 0;
    $stock = $producto['stock'] ?? 0;
    $precioVenta = $producto['precio_venta'] ?? 0;
    
    // Extraer todos los valores para calcular percentiles
    $valoresInventario = array_map(fn($p) => $p['valor_inventario'] ?? 0, $todosLosProductos);
    $valoresStock = array_map(fn($p) => $p['stock'] ?? 0, $todosLosProductos);
    $valoresPrecios = array_map(fn($p) => $p['precio_venta'] ?? 0, $todosLosProductos);
    
    // Filtrar valores > 0 para cálculos
    $valoresInventario = array_filter($valoresInventario, fn($v) => $v > 0);
    $valoresStock = array_filter($valoresStock, fn($v) => $v > 0);
    $valoresPrecios = array_filter($valoresPrecios, fn($v) => $v > 0);
    
    // Calcular percentil 95 (outliers superiores)
    $p95Inventario = calcularPercentil($valoresInventario, 95);
    $p95Stock = calcularPercentil($valoresStock, 95);
    $p95Precio = calcularPercentil($valoresPrecios, 95);
    
    $outliers = [];
    
    if ($valorInventario > $p95Inventario) {
        $outliers[] = 'valor_inventario_alto';
    }
    
    if ($stock > $p95Stock) {
        $outliers[] = 'stock_excesivo';
    }
    
    if ($precioVenta > $p95Precio) {
        $outliers[] = 'precio_premium';
    }
    
    return $outliers;
}

/**
 * ✅ NUEVO: Generar alertas de validación para el producto
 */
function generarAlertasValidacion($producto) {
    $alertas = [];
    
    $stock = $producto['stock'] ?? 0;
    $precioVenta = $producto['precio_venta'] ?? 0;
    $precioCosto = $producto['precio_costo'] ?? 0;
    $valorInventario = $producto['valor_inventario'] ?? 0;
    
    // Validación de precios
    if ($precioVenta <= $precioCosto && $precioCosto > 0) {
        $alertas[] = [
            'tipo' => 'precio_perdida',
            'mensaje' => 'Precio de venta menor al costo',
            'severidad' => 'critica'
        ];
    }
    
    // Validación de stock extremo
    if ($stock > 1000) {
        $alertas[] = [
            'tipo' => 'stock_extremo',
            'mensaje' => "Stock muy alto: {$stock} unidades",
            'severidad' => 'alta'
        ];
    }
    
    // Validación de valor de inventario
    if ($valorInventario > 1000000) { // > $1M
        $alertas[] = [
            'tipo' => 'valor_extremo',
            'mensaje' => "Valor de inventario: $" . number_format($valorInventario, 0, ',', '.'),
            'severidad' => 'media'
        ];
    }
    
    // Validación de datos faltantes
    if (empty($producto['proveedor']) || $producto['proveedor'] === 'Sin proveedor') {
        $alertas[] = [
            'tipo' => 'datos_incompletos',
            'mensaje' => 'Falta información del proveedor',
            'severidad' => 'baja'
        ];
    }
    
    return $alertas;
}

/**
 * ✅ NUEVO: Calcular percentil de un array de valores
 */
function calcularPercentil($valores, $percentil) {
    if (empty($valores)) return 0;
    
    sort($valores);
    $index = ($percentil / 100) * (count($valores) - 1);
    
    if ($index == floor($index)) {
        return $valores[$index];
    } else {
        $lower = $valores[floor($index)];
        $upper = $valores[ceil($index)];
        return $lower + ($upper - $lower) * ($index - floor($index));
    }
}

/**
 * Obtener análisis ABC completo
 */
function obtenerAnalisisABC($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.categoria,
                COUNT(*) as total_productos,
                SUM(p.stock * p.precio_costo) as valor_inventario,
                AVG(COALESCE(v.ventas_30_dias, 0)) as promedio_ventas,
                SUM(COALESCE(v.ventas_30_dias, 0) * p.precio_venta) as valor_ventas
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN cantidad ELSE 0 END) as ventas_30_dias
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            GROUP BY p.categoria
            ORDER BY valor_ventas DESC
        ");
        
        $stmt->execute();
        $analisis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular totales para porcentajes
        $valorTotalVentas = array_sum(array_column($analisis, 'valor_ventas'));
        $valorTotalInventario = array_sum(array_column($analisis, 'valor_inventario'));
        
        // Enriquecer con porcentajes y clasificación
        $analisisEnriquecido = array_map(function($item) use ($valorTotalVentas, $valorTotalInventario) {
            $porcentajeVentas = $valorTotalVentas > 0 ? ($item['valor_ventas'] / $valorTotalVentas) * 100 : 0;
            $porcentajeInventario = $valorTotalInventario > 0 ? ($item['valor_inventario'] / $valorTotalInventario) * 100 : 0;
            
            return array_merge($item, [
                'porcentaje_ventas' => round($porcentajeVentas, 2),
                'porcentaje_inventario' => round($porcentajeInventario, 2),
                'clase_abc' => $porcentajeVentas >= 70 ? 'A' : ($porcentajeVentas >= 20 ? 'B' : 'C')
            ]);
        }, $analisis);
        
        echo json_encode([
            'success' => true,
            'analisis' => $analisisEnriquecido,
            'totales' => [
                'valor_ventas' => $valorTotalVentas,
                'valor_inventario' => $valorTotalInventario
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error en análisis ABC: ' . $e->getMessage());
    }
}

/**
 * Obtener predicciones de demanda
 */
function obtenerPredicciones($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.stock,
                p.stock_minimo,
                COALESCE(v.ventas_7_dias, 0) as ventas_recientes,
                COALESCE(v.ventas_30_dias, 0) as ventas_mes,
                COALESCE(v.tendencia, 0) as tendencia
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as ventas_7_dias,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN cantidad ELSE 0 END) as ventas_30_dias,
                    (
                        SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) -
                        SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND fecha < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END)
                    ) as tendencia
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            WHERE p.stock > 0
            ORDER BY v.ventas_7_dias DESC
            LIMIT 50
        ");
        
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $predicciones = array_map(function($item) {
            $demandaDiaria = $item['ventas_7_dias'] / 7;
            $demandaProyectada = $demandaDiaria * 30; // Próximos 30 días
            $diasAgotamiento = $demandaDiaria > 0 ? ceil($item['stock'] / $demandaDiaria) : 999;
            
            return [
                'producto_id' => $item['id'],
                'producto_nombre' => $item['nombre'],
                'stock_actual' => $item['stock'],
                'demanda_diaria' => round($demandaDiaria, 2),
                'demanda_proyectada_30d' => round($demandaProyectada, 2),
                'dias_hasta_agotamiento' => $diasAgotamiento,
                'fecha_agotamiento' => date('Y-m-d', strtotime("+{$diasAgotamiento} days")),
                'tendencia' => $item['tendencia'] > 0 ? 'creciente' : ($item['tendencia'] < 0 ? 'decreciente' : 'estable'),
                'confianza' => $item['ventas_mes'] > 10 ? 'alta' : ($item['ventas_mes'] > 3 ? 'media' : 'baja')
            ];
        }, $datos);
        
        echo json_encode([
            'success' => true,
            'predicciones' => $predicciones
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error en predicciones: ' . $e->getMessage());
    }
}

/**
 * Obtener alertas inteligentes
 */
function obtenerAlertasInteligentes($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.stock,
                p.stock_minimo,
                p.categoria,
                COALESCE(v.ventas_7_dias, 0) as ventas_recientes,
                (p.stock * p.precio_costo) as valor_stock
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as ventas_7_dias
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            WHERE p.stock <= p.stock_minimo * 2 -- Solo productos con stock bajo o crítico
            ORDER BY 
                CASE 
                    WHEN p.stock = 0 THEN 1
                    WHEN p.stock <= p.stock_minimo * 0.5 THEN 2
                    WHEN p.stock <= p.stock_minimo THEN 3
                    ELSE 4
                END,
                v.ventas_7_dias DESC
        ");
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $alertas = array_map(function($producto) {
            $velocidadVenta = $producto['ventas_recientes'] / 7;
            $diasStock = $velocidadVenta > 0 ? ceil($producto['stock'] / $velocidadVenta) : 999;
            
            // Determinar tipo y prioridad de alerta
            if ($producto['stock'] == 0) {
                $tipo = 'sin_stock';
                $prioridad = 'critica';
                $mensaje = 'Producto agotado - Pérdida de ventas';
            } elseif ($producto['stock'] <= $producto['stock_minimo'] * 0.5) {
                $tipo = 'stock_critico';
                $prioridad = 'alta';
                $mensaje = "Stock crítico - Solo {$producto['stock']} unidades";
            } elseif ($diasStock <= 7 && $velocidadVenta > 0) {
                $tipo = 'agotamiento_proximo';
                $prioridad = 'media';
                $mensaje = "Se agotará en {$diasStock} días";
            } else {
                $tipo = 'stock_bajo';
                $prioridad = 'baja';
                $mensaje = "Stock por debajo del mínimo";
            }
            
            return [
                'id' => uniqid(),
                'producto_id' => $producto['id'],
                'producto_nombre' => $producto['nombre'],
                'categoria' => $producto['categoria'],
                'tipo' => $tipo,
                'prioridad' => $prioridad,
                'mensaje' => $mensaje,
                'stock_actual' => $producto['stock'],
                'stock_minimo' => $producto['stock_minimo'],
                'dias_stock_restante' => $diasStock,
                'valor_en_riesgo' => $producto['valor_stock'],
                'fecha_alerta' => date('Y-m-d H:i:s'),
                'acciones_sugeridas' => generarAccionesSugeridas($tipo, $producto)
            ];
        }, $productos);
        
        echo json_encode([
            'success' => true,
            'alertas' => $alertas,
            'resumen' => [
                'total_alertas' => count($alertas),
                'criticas' => count(array_filter($alertas, fn($a) => $a['prioridad'] === 'critica')),
                'altas' => count(array_filter($alertas, fn($a) => $a['prioridad'] === 'alta')),
                'medias' => count(array_filter($alertas, fn($a) => $a['prioridad'] === 'media'))
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error en alertas: ' . $e->getMessage());
    }
}

/**
 * Generar acciones sugeridas para alertas
 */
function generarAccionesSugeridas($tipo, $producto) {
    switch ($tipo) {
        case 'sin_stock':
            return [
                'Pedido urgente al proveedor',
                'Buscar proveedores alternativos',
                'Notificar a ventas sobre disponibilidad'
            ];
        case 'stock_critico':
            return [
                'Realizar pedido inmediato',
                'Reducir promociones del producto',
                'Activar alertas de venta'
            ];
        case 'agotamiento_proximo':
            return [
                'Programar pedido para esta semana',
                'Revisar demanda reciente',
                'Considerar ajustar stock mínimo'
            ];
        default:
            return [
                'Revisar nivel de stock mínimo',
                'Programar pedido rutinario'
            ];
    }
}

/**
 * Obtener sugerencias de pedidos optimizadas
 */
function obtenerSugerenciasPedidos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                COALESCE(v.ventas_30_dias, 0) as ventas_mes,
                COALESCE(v.ventas_7_dias, 0) as ventas_semana,
                COALESCE(prov.tiempo_entrega, 7) as tiempo_entrega,
                COALESCE(prov.pedido_minimo, 1) as pedido_minimo
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN cantidad ELSE 0 END) as ventas_30_dias,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as ventas_7_dias
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            LEFT JOIN proveedores prov ON p.proveedor = prov.nombre
            WHERE p.stock <= (p.stock_minimo * 1.5) -- Productos que necesitan pedido pronto
            ORDER BY 
                CASE WHEN p.stock = 0 THEN 1 ELSE 2 END,
                (v.ventas_7_dias / 7) DESC
        ");
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sugerencias = array_map(function($producto) {
            $demandaDiaria = ($producto['ventas_semana'] ?: 1) / 7;
            $demandaMensual = $demandaDiaria * 30;
            
            // Calcular cantidad sugerida
            $stockSeguridad = max($producto['stock_minimo'], $demandaDiaria * 7);
            $demandaDuranteEntrega = $demandaDiaria * $producto['tiempo_entrega'];
            $cantidadSugerida = ceil($demandaDuranteEntrega + $stockSeguridad + $demandaMensual);
            
            // Ajustar por pedido mínimo
            $cantidadFinal = max($cantidadSugerida, $producto['pedido_minimo']);
            
            // Calcular métricas del pedido
            $costoTotal = $cantidadFinal * $producto['precio_costo'];
            $diasCobertura = $demandaDiaria > 0 ? ceil($cantidadFinal / $demandaDiaria) : 999;
            
            return [
                'producto_id' => $producto['id'],
                'producto_nombre' => $producto['nombre'],
                'categoria' => $producto['categoria'],
                'proveedor' => $producto['proveedor'] ?: 'Sin proveedor',
                'stock_actual' => $producto['stock'],
                'stock_minimo' => $producto['stock_minimo'],
                'cantidad_sugerida' => $cantidadFinal,
                'costo_total' => $costoTotal,
                'dias_cobertura' => $diasCobertura,
                'urgencia' => calcularUrgenciaPedido($producto['stock'], $demandaDiaria, $producto['tiempo_entrega']),
                'fecha_sugerida' => date('Y-m-d', strtotime('+1 day')),
                'motivo' => generarMotivoPedido($producto['stock'], $producto['stock_minimo'], $demandaDiaria),
                'beneficios' => [
                    'Evitar quiebre de stock',
                    'Mantener nivel de servicio',
                    'Optimizar costos de pedido'
                ]
            ];
        }, $productos);
        
        // Agrupar por proveedor para optimizar pedidos
        $sugerenciasPorProveedor = [];
        foreach ($sugerencias as $sugerencia) {
            $proveedor = $sugerencia['proveedor'];
            if (!isset($sugerenciasPorProveedor[$proveedor])) {
                $sugerenciasPorProveedor[$proveedor] = [
                    'proveedor' => $proveedor,
                    'productos' => [],
                    'costo_total' => 0,
                    'productos_count' => 0
                ];
            }
            
            $sugerenciasPorProveedor[$proveedor]['productos'][] = $sugerencia;
            $sugerenciasPorProveedor[$proveedor]['costo_total'] += $sugerencia['costo_total'];
            $sugerenciasPorProveedor[$proveedor]['productos_count']++;
        }
        
        echo json_encode([
            'success' => true,
            'sugerencias' => $sugerencias,
            'por_proveedor' => array_values($sugerenciasPorProveedor),
            'resumen' => [
                'total_productos' => count($sugerencias),
                'costo_total' => array_sum(array_column($sugerencias, 'costo_total')),
                'proveedores' => count($sugerenciasPorProveedor)
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error en sugerencias de pedidos: ' . $e->getMessage());
    }
}

/**
 * Calcular urgencia del pedido
 */
function calcularUrgenciaPedido($stock, $demandaDiaria, $tiempoEntrega) {
    if ($stock <= 0) return 100;
    
    $diasStock = $demandaDiaria > 0 ? $stock / $demandaDiaria : 999;
    
    if ($diasStock <= $tiempoEntrega) return 95; // Crítico
    if ($diasStock <= $tiempoEntrega * 1.5) return 80; // Alto
    if ($diasStock <= $tiempoEntrega * 2) return 60; // Medio
    return 30; // Bajo
}

/**
 * Generar motivo del pedido
 */
function generarMotivoPedido($stock, $stockMinimo, $demandaDiaria) {
    if ($stock <= 0) return 'Producto agotado - Reposición crítica';
    if ($stock <= $stockMinimo * 0.5) return 'Stock crítico - Reposición urgente';
    if ($demandaDiaria > 0 && ($stock / $demandaDiaria) <= 7) return 'Stock para menos de una semana';
    return 'Mantenimiento de stock óptimo';
}

/**
 * Obtener métricas avanzadas del inventario
 */
function obtenerMetricasAvanzadas($pdo) {
    try {
        // Métricas generales
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_productos,
                COUNT(CASE WHEN stock > 0 THEN 1 END) as productos_con_stock,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as productos_sin_stock,
                COUNT(CASE WHEN stock <= stock_minimo THEN 1 END) as productos_stock_bajo,
                SUM(stock * precio_costo) as valor_total_inventario,
                AVG(precio_venta / NULLIF(precio_costo, 0)) as margen_promedio
            FROM productos
        ");
        $metricas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Métricas de ventas
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT dv.producto_id) as productos_vendidos_mes,
                SUM(dv.cantidad) as unidades_vendidas_mes,
                SUM(dv.cantidad * dv.precio_unitario) as valor_ventas_mes
            FROM detalle_ventas dv
            INNER JOIN ventas v ON dv.venta_id = v.id
            WHERE v.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND v.estado = 'completada'
        ");
        $ventasMes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top productos por rotación
        $stmt = $pdo->query("
            SELECT 
                p.nombre,
                SUM(dv.cantidad) as ventas_mes,
                p.stock,
                CASE WHEN p.stock > 0 THEN SUM(dv.cantidad) / p.stock ELSE 0 END as rotacion
            FROM productos p
            LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
            LEFT JOIN ventas v ON dv.venta_id = v.id
            WHERE v.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND v.estado = 'completada'
            GROUP BY p.id
            ORDER BY rotacion DESC
            LIMIT 10
        ");
        $topRotacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'metricas' => $metricas,
            'ventas_mes' => $ventasMes,
            'top_rotacion' => $topRotacion,
            'fecha_calculo' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error en métricas: ' . $e->getMessage());
    }
}

/**
 * NUEVO: Obtener datos estructurados para análisis IA
 */
function obtenerAnalisisIA($pdo) {
    try {
        // Obtener productos enriquecidos
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                COALESCE(v.ventas_total, 0) as ventas_total,
                COALESCE(v.ventas_30_dias, 0) as ventas_30_dias,
                COALESCE(v.ventas_7_dias, 0) as ventas_7_dias,
                (p.stock * p.precio_costo) as valor_inventario
            FROM productos p
            LEFT JOIN (
                SELECT 
                    producto_id,
                    SUM(cantidad) as ventas_total,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN cantidad ELSE 0 END) as ventas_30_dias,
                    SUM(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as ventas_7_dias
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE v.estado = 'completada'
                GROUP BY producto_id
            ) v ON p.id = v.producto_id
            ORDER BY p.nombre
        ");
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ✅ OPTIMIZADO: Enriquecer productos con cálculos batch para mejor rendimiento  
        $productosEnriquecidos = enriquecerProductosBatch($productos);
        
        // Calcular métricas generales
        $totalProductos = count($productosEnriquecidos);
        $valorTotal = array_sum(array_column($productosEnriquecidos, 'valor_inventario'));
        $sinStock = count(array_filter($productosEnriquecidos, fn($p) => $p['stock'] <= 0));
        $stockBajo = count(array_filter($productosEnriquecidos, fn($p) => $p['urgencia'] >= 70));
        $claseA = count(array_filter($productosEnriquecidos, fn($p) => $p['clase_abc'] === 'A'));
        $claseB = count(array_filter($productosEnriquecidos, fn($p) => $p['clase_abc'] === 'B'));
        $claseC = count(array_filter($productosEnriquecidos, fn($p) => $p['clase_abc'] === 'C'));
        
        $rotacionPromedio = 0;
        if ($totalProductos > 0) {
            $rotacionPromedio = array_sum(array_column($productosEnriquecidos, 'rotacion_anual')) / $totalProductos;
        }
        
        // Generar alertas inteligentes
        $alertas = array_filter($productosEnriquecidos, function($p) {
            return $p['urgencia'] >= 70 || $p['stock'] <= 0;
        });
        
        $datosParaIA = [
            'productos' => $productosEnriquecidos,
            'metricas' => [
                'valorTotal' => $valorTotal,
                'productosActivos' => $totalProductos - $sinStock,
                'stockBajo' => $stockBajo,
                'sinStock' => $sinStock,
                'rotacionPromedio' => round($rotacionPromedio, 2),
                'productos' => [
                    'claseA' => $claseA,
                    'claseB' => $claseB,  
                    'claseC' => $claseC
                ]
            ],
            'alertas' => array_values($alertas),
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo_negocio' => 'kiosco_argentino'
        ];
        
        echo json_encode([
            'success' => true,
            'datos_ia' => $datosParaIA,
            'resumen' => [
                'total_productos' => $totalProductos,
                'valor_inventario' => $valorTotal,
                'productos_criticos' => count($alertas),
                'rotacion_promedio' => round($rotacionPromedio, 2)
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error en análisis IA: ' . $e->getMessage());
    }
}

?> 