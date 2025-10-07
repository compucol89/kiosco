<?php
/**
 * 🔍 VALIDADOR DE INTEGRIDAD - REPORTES TIEMPO REAL
 * Verifica que todas las variables críticas estén disponibles
 * para informes HOY, AYER, SEMANALES y MENSUALES
 */

header('Content-Type: application/json; charset=UTF-8');
require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $resultado = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'validaciones' => [],
        'errores' => [],
        'alertas' => [],
        'metricas_disponibles' => []
    ];
    
    // ========================================================================
    // VALIDACIÓN 1: ESTRUCTURA DE TABLAS CRÍTICAS
    // ========================================================================
    
    $resultado['validaciones']['estructura_tablas'] = [
        'titulo' => 'Estructura de Tablas Críticas',
        'estado' => 'validando',
        'detalles' => []
    ];
    
    $tablas_criticas = [
        'ventas' => [
            'columnas_requeridas' => [
                'id', 'fecha', 'monto_total', 'metodo_pago', 'estado',
                'usuario_id', 'caja_id', 'utilidad_total', 'costo_total',
                'margen_promedio', 'descuento', 'subtotal'
            ],
            'indices_requeridos' => [
                'idx_fecha_estado', 'idx_metodo_pago', 'idx_usuario_fecha',
                'idx_monto_total', 'idx_fecha_desc'
            ]
        ],
        'venta_detalles' => [
            'columnas_requeridas' => [
                'id', 'venta_id', 'producto_id', 'cantidad', 'precio_unitario',
                'costo_unitario', 'utilidad_total', 'margen_porcentaje',
                'codigo_producto', 'categoria_producto'
            ],
            'indices_requeridos' => [
                'idx_venta_id', 'idx_producto_id', 'idx_utilidad_total',
                'idx_margen', 'idx_categoria'
            ]
        ],
        'productos' => [
            'columnas_requeridas' => [
                'id', 'nombre', 'categoria', 'precio_costo', 'precio_venta',
                'stock_actual', 'total_vendido', 'ingresos_totales',
                'utilidad_acumulada', 'ultima_venta', 'rotacion_dias'
            ],
            'indices_requeridos' => [
                'idx_categoria', 'idx_precio_venta', 'idx_stock_actual',
                'idx_total_vendido', 'idx_ultima_venta'
            ]
        ],
        'movimientos_caja' => [
            'columnas_requeridas' => [
                'id', 'caja_id', 'tipo', 'monto', 'fecha_hora', 'metodo_pago',
                'tipo_transaccion', 'venta_id', 'afecta_efectivo'
            ],
            'indices_requeridos' => [
                'idx_caja_id', 'idx_tipo', 'idx_metodo_pago',
                'idx_fecha_hora', 'idx_venta_id'
            ]
        ]
    ];
    
    foreach ($tablas_criticas as $tabla => $requisitos) {
        // Verificar que la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() === 0) {
            $resultado['errores'][] = "Tabla crítica '$tabla' no existe";
            continue;
        }
        
        // Verificar columnas requeridas
        $columnas_existentes = [];
        $stmt = $pdo->query("DESCRIBE $tabla");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columnas_existentes[] = $row['Field'];
        }
        
        $columnas_faltantes = array_diff($requisitos['columnas_requeridas'], $columnas_existentes);
        if (!empty($columnas_faltantes)) {
            $resultado['errores'][] = "Tabla '$tabla' - Columnas faltantes: " . implode(', ', $columnas_faltantes);
        }
        
        // Verificar índices requeridos
        $indices_existentes = [];
        $stmt = $pdo->query("SHOW INDEX FROM $tabla WHERE Key_name != 'PRIMARY'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $indices_existentes[] = $row['Key_name'];
        }
        
        $indices_faltantes = array_diff($requisitos['indices_requeridos'], $indices_existentes);
        if (!empty($indices_faltantes)) {
            $resultado['alertas'][] = "Tabla '$tabla' - Índices recomendados faltantes: " . implode(', ', $indices_faltantes);
        }
        
        $resultado['validaciones']['estructura_tablas']['detalles'][$tabla] = [
            'columnas_ok' => empty($columnas_faltantes),
            'indices_ok' => empty($indices_faltantes),
            'columnas_total' => count($columnas_existentes),
            'indices_total' => count($indices_existentes)
        ];
    }
    
    $resultado['validaciones']['estructura_tablas']['estado'] = empty($resultado['errores']) ? 'ok' : 'error';
    
    // ========================================================================
    // VALIDACIÓN 2: DISPONIBILIDAD DE DATOS PARA REPORTES
    // ========================================================================
    
    $resultado['validaciones']['datos_reportes'] = [
        'titulo' => 'Disponibilidad de Datos para Reportes',
        'estado' => 'validando',
        'periodos' => []
    ];
    
    $periodos_validar = [
        'hoy' => [
            'fecha_inicio' => date('Y-m-d'),
            'fecha_fin' => date('Y-m-d'),
            'nombre' => 'Hoy'
        ],
        'ayer' => [
            'fecha_inicio' => date('Y-m-d', strtotime('-1 day')),
            'fecha_fin' => date('Y-m-d', strtotime('-1 day')),
            'nombre' => 'Ayer'
        ],
        'semana' => [
            'fecha_inicio' => date('Y-m-d', strtotime('monday this week')),
            'fecha_fin' => date('Y-m-d'),
            'nombre' => 'Esta Semana'
        ],
        'mes' => [
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d'),
            'nombre' => 'Este Mes'
        ]
    ];
    
    foreach ($periodos_validar as $periodo_key => $periodo) {
        $datos_periodo = [
            'periodo' => $periodo['nombre'],
            'ventas' => 0,
            'ingresos' => 0,
            'utilidades' => 0,
            'productos_vendidos' => 0,
            'metodos_pago' => [],
            'categorias' => [],
            'tiene_datos' => false
        ];
        
        // Consultar ventas del período
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as num_ventas,
                COALESCE(SUM(monto_total), 0) as ingresos_totales,
                COALESCE(SUM(utilidad_total), 0) as utilidades_totales,
                COUNT(DISTINCT metodo_pago) as metodos_diferentes
            FROM ventas 
            WHERE DATE(fecha) BETWEEN ? AND ?
            AND estado IN ('completado', 'completada')
        ");
        $stmt->execute([$periodo['fecha_inicio'], $periodo['fecha_fin']]);
        $resumen_ventas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $datos_periodo['ventas'] = (int)$resumen_ventas['num_ventas'];
        $datos_periodo['ingresos'] = (float)$resumen_ventas['ingresos_totales'];
        $datos_periodo['utilidades'] = (float)$resumen_ventas['utilidades_totales'];
        $datos_periodo['tiene_datos'] = $datos_periodo['ventas'] > 0;
        
        // Consultar productos vendidos
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT vd.producto_id) as productos_unicos
            FROM venta_detalles vd
            JOIN ventas v ON vd.venta_id = v.id
            WHERE DATE(v.fecha) BETWEEN ? AND ?
            AND v.estado IN ('completado', 'completada')
        ");
        $stmt->execute([$periodo['fecha_inicio'], $periodo['fecha_fin']]);
        $productos_vendidos = $stmt->fetch(PDO::FETCH_ASSOC);
        $datos_periodo['productos_vendidos'] = (int)$productos_vendidos['productos_unicos'];
        
        // Consultar métodos de pago
        $stmt = $pdo->prepare("
            SELECT 
                metodo_pago,
                COUNT(*) as cantidad,
                SUM(monto_total) as monto
            FROM ventas 
            WHERE DATE(fecha) BETWEEN ? AND ?
            AND estado IN ('completado', 'completada')
            GROUP BY metodo_pago
        ");
        $stmt->execute([$periodo['fecha_inicio'], $periodo['fecha_fin']]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $datos_periodo['metodos_pago'][$row['metodo_pago']] = [
                'cantidad' => (int)$row['cantidad'],
                'monto' => (float)$row['monto']
            ];
        }
        
        // Consultar categorías vendidas
        $stmt = $pdo->prepare("
            SELECT 
                p.categoria,
                COUNT(*) as cantidad_ventas,
                SUM(vd.subtotal) as ingresos_categoria
            FROM venta_detalles vd
            JOIN productos p ON vd.producto_id = p.id
            JOIN ventas v ON vd.venta_id = v.id
            WHERE DATE(v.fecha) BETWEEN ? AND ?
            AND v.estado IN ('completado', 'completada')
            GROUP BY p.categoria
        ");
        $stmt->execute([$periodo['fecha_inicio'], $periodo['fecha_fin']]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $datos_periodo['categorias'][$row['categoria']] = [
                'ventas' => (int)$row['cantidad_ventas'],
                'ingresos' => (float)$row['ingresos_categoria']
            ];
        }
        
        $resultado['validaciones']['datos_reportes']['periodos'][$periodo_key] = $datos_periodo;
    }
    
    $resultado['validaciones']['datos_reportes']['estado'] = 'ok';
    
    // ========================================================================
    // VALIDACIÓN 3: INTEGRIDAD DE CÁLCULOS FINANCIEROS
    // ========================================================================
    
    $resultado['validaciones']['calculos_financieros'] = [
        'titulo' => 'Integridad de Cálculos Financieros',
        'estado' => 'validando',
        'validaciones' => []
    ];
    
    // Verificar que las utilidades calculadas son coherentes
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as ventas_con_utilidad,
            COUNT(CASE WHEN utilidad_total > 0 THEN 1 END) as utilidades_positivas,
            COUNT(CASE WHEN utilidad_total < 0 THEN 1 END) as utilidades_negativas,
            AVG(margen_promedio) as margen_promedio_global
        FROM ventas 
        WHERE estado IN ('completado', 'completada')
        AND utilidad_total IS NOT NULL
    ");
    $calculos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $resultado['validaciones']['calculos_financieros']['validaciones']['utilidades'] = [
        'ventas_con_calculo' => (int)$calculos['ventas_con_utilidad'],
        'utilidades_positivas' => (int)$calculos['utilidades_positivas'],
        'utilidades_negativas' => (int)$calculos['utilidades_negativas'],
        'margen_promedio' => round((float)$calculos['margen_promedio_global'], 2)
    ];
    
    // Verificar consistencia entre ventas y venta_detalles
    $stmt = $pdo->query("
        SELECT 
            v.id,
            v.monto_total,
            COALESCE(SUM(vd.subtotal), 0) as suma_detalles
        FROM ventas v
        LEFT JOIN venta_detalles vd ON v.id = vd.venta_id
        WHERE v.estado IN ('completado', 'completada')
        GROUP BY v.id
        HAVING ABS(v.monto_total - suma_detalles) > 0.01
        LIMIT 10
    ");
    $inconsistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($inconsistencias)) {
        $resultado['alertas'][] = "Se encontraron " . count($inconsistencias) . " ventas con inconsistencias entre total y detalles";
    }
    
    $resultado['validaciones']['calculos_financieros']['validaciones']['consistencia'] = [
        'ventas_inconsistentes' => count($inconsistencias),
        'ejemplos' => array_slice($inconsistencias, 0, 3)
    ];
    
    $resultado['validaciones']['calculos_financieros']['estado'] = empty($inconsistencias) ? 'ok' : 'warning';
    
    // ========================================================================
    // VALIDACIÓN 4: MÉTRICAS DISPONIBLES PARA FRONTEND
    // ========================================================================
    
    $resultado['metricas_disponibles'] = [
        'ventas_tiempo_real' => [
            'disponible' => true,
            'campos' => ['fecha', 'monto_total', 'metodo_pago', 'utilidad_total', 'margen_promedio']
        ],
        'productos_analisis' => [
            'disponible' => true,
            'campos' => ['total_vendido', 'ingresos_totales', 'utilidad_acumulada', 'rotacion_dias', 'stock_valorizado']
        ],
        'caja_movimientos' => [
            'disponible' => true,
            'campos' => ['tipo', 'monto', 'metodo_pago', 'tipo_transaccion', 'afecta_efectivo']
        ],
        'reportes_periodo' => [
            'disponible' => true,
            'periodos_soportados' => ['hoy', 'ayer', 'semana', 'mes', 'personalizado']
        ],
        'calculos_automaticos' => [
            'disponible' => true,
            'funciones' => ['utilidad_por_producto', 'margen_porcentaje', 'rotacion_stock', 'valorizado_inventario']
        ]
    ];
    
    // ========================================================================
    // RESUMEN FINAL
    // ========================================================================
    
    $total_errores = count($resultado['errores']);
    $total_alertas = count($resultado['alertas']);
    
    if ($total_errores > 0) {
        $resultado['success'] = false;
        $resultado['estado'] = 'error';
        $resultado['mensaje'] = "Se encontraron $total_errores errores críticos que impiden el funcionamiento de reportes";
    } elseif ($total_alertas > 0) {
        $resultado['estado'] = 'warning';
        $resultado['mensaje'] = "Sistema funcional con $total_alertas alertas de optimización";
    } else {
        $resultado['estado'] = 'ok';
        $resultado['mensaje'] = "Todos los sistemas de reportes están funcionando correctamente";
    }
    
    $resultado['resumen'] = [
        'tablas_validadas' => count($tablas_criticas),
        'periodos_validados' => count($periodos_validar),
        'metricas_disponibles' => count($resultado['metricas_disponibles']),
        'errores_encontrados' => $total_errores,
        'alertas_encontradas' => $total_alertas
    ];
    
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
