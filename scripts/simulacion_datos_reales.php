<?php
/**
 * scripts/simulacion_datos_reales.php
 * Simulación completa de operatoria semanal con datos realistas
 * Propósito: Generar reportes y archivos de datos sin depender de BD
 * Archivos relacionados: Genera CSVs y JSONs con datos simulados
 */

echo "🏪 SIMULACIÓN OPERATORIA SEMANAL - KIOSCO ARGENTINO\n";
echo "==================================================\n\n";
echo "📅 Objetivo: Simular 7 días completos de operación\n";
echo "💰 Meta diaria: ~\$520,000 ARS (±20%)\n";
echo "🕐 Horario: 07:00 a 23:00 (16 horas diarias)\n";
echo "🛒 Productos: Mix realista de kiosco argentino\n\n";

// Configuración de la simulación
$config = [
    'meta_diaria' => 520000,
    'variacion_maxima' => 0.20,
    'horario_apertura' => 7,
    'horario_cierre' => 23,
    'dias_simulacion' => 7,
    'productos_populares' => [
        // Bebidas (40% de las ventas)
        ['id' => 1, 'nombre' => 'Coca Cola 600ml', 'precio' => 1800, 'categoria' => 'bebidas', 'freq' => 15, 'costo' => 1200],
        ['id' => 2, 'nombre' => 'Agua Mineral 500ml', 'precio' => 1200, 'categoria' => 'bebidas', 'freq' => 12, 'costo' => 800],
        ['id' => 3, 'nombre' => 'Cerveza Quilmes 473ml', 'precio' => 2500, 'categoria' => 'bebidas', 'freq' => 8, 'costo' => 1800],
        ['id' => 4, 'nombre' => 'Gatorade 500ml', 'precio' => 2200, 'categoria' => 'bebidas', 'freq' => 6, 'costo' => 1500],
        ['id' => 5, 'nombre' => 'Sprite 600ml', 'precio' => 1750, 'categoria' => 'bebidas', 'freq' => 10, 'costo' => 1150],
        
        // Snacks/Golosinas (30% de las ventas)
        ['id' => 6, 'nombre' => 'Alfajor Havanna', 'precio' => 3500, 'categoria' => 'golosinas', 'freq' => 10, 'costo' => 2200],
        ['id' => 7, 'nombre' => 'Papas Fritas Lays', 'precio' => 2800, 'categoria' => 'snacks', 'freq' => 8, 'costo' => 1900],
        ['id' => 8, 'nombre' => 'Chocolate Milka', 'precio' => 4200, 'categoria' => 'golosinas', 'freq' => 6, 'costo' => 2800],
        ['id' => 9, 'nombre' => 'Chicles Beldent', 'precio' => 800, 'categoria' => 'golosinas', 'freq' => 12, 'costo' => 500],
        ['id' => 10, 'nombre' => 'Oreo Original', 'precio' => 2200, 'categoria' => 'galletitas', 'freq' => 7, 'costo' => 1400],
        
        // Cigarrillos (20% de las ventas)
        ['id' => 11, 'nombre' => 'Marlboro Box', 'precio' => 8500, 'categoria' => 'cigarrillos', 'freq' => 5, 'costo' => 7200],
        ['id' => 12, 'nombre' => 'Philip Morris', 'precio' => 7800, 'categoria' => 'cigarrillos', 'freq' => 4, 'costo' => 6500],
        ['id' => 13, 'nombre' => 'Lucky Strike', 'precio' => 7500, 'categoria' => 'cigarrillos', 'freq' => 3, 'costo' => 6200],
        
        // Otros (10% de las ventas)
        ['id' => 14, 'nombre' => 'Pan Lactal', 'precio' => 3200, 'categoria' => 'panaderia', 'freq' => 3, 'costo' => 2000],
        ['id' => 15, 'nombre' => 'Leche Larga Vida 1L', 'precio' => 2400, 'categoria' => 'lacteos', 'freq' => 2, 'costo' => 1800],
        ['id' => 16, 'nombre' => 'Yerba Mate 500g', 'precio' => 3800, 'categoria' => 'almacen', 'freq' => 2, 'costo' => 2500],
    ],
    'metodos_pago' => [
        'efectivo' => 0.45,
        'tarjeta' => 0.30,
        'transferencia' => 0.15,
        'qr' => 0.10
    ],
    'patrones_horarios' => [
        7 => 0.03, 8 => 0.08, 9 => 0.06, 10 => 0.05, 11 => 0.07, 12 => 0.10,
        13 => 0.09, 14 => 0.06, 15 => 0.08, 16 => 0.07, 17 => 0.06, 18 => 0.09,
        19 => 0.08, 20 => 0.06, 21 => 0.05, 22 => 0.04, 23 => 0.03
    ]
];

// Funciones auxiliares
function seleccionarProductoAleatorio($productos) {
    $total = array_sum(array_column($productos, 'freq'));
    $random = rand(1, $total);
    $accumulator = 0;
    
    foreach ($productos as $producto) {
        $accumulator += $producto['freq'];
        if ($random <= $accumulator) {
            return $producto;
        }
    }
    return $productos[0];
}

function seleccionarMetodoPago($metodos) {
    $random = rand(1, 100) / 100;
    $accumulator = 0;
    
    foreach ($metodos as $metodo => $probabilidad) {
        $accumulator += $probabilidad;
        if ($random <= $accumulator) {
            return $metodo;
        }
    }
    return 'efectivo';
}

// Inicializar estadísticas
$estadisticas = [
    'total_ventas' => 0,
    'total_facturado' => 0,
    'total_costo' => 0,
    'ventas_por_dia' => [],
    'ventas_por_metodo' => ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0, 'qr' => 0],
    'ventas_por_hora' => [],
    'productos_vendidos' => [],
    'movimientos_caja' => []
];

$todasLasVentas = [];
$todosLosMovimientos = [];

echo "🚀 INICIANDO SIMULACIÓN...\n\n";

// Simular cada día de la semana
for ($dia = 1; $dia <= $config['dias_simulacion']; $dia++) {
    $nombreDia = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'][$dia];
    echo "📅 === DÍA $dia: $nombreDia ===\n";
    
    $ventasDelDia = [];
    $facturacionDia = 0;
    $costosDia = 0;
    
    // Multiplicador por día de la semana
    $multiplicadorDia = [1 => 1.2, 2 => 0.9, 3 => 0.95, 4 => 1.0, 5 => 1.1, 6 => 1.3, 7 => 1.15][$dia];
    
    // Simular cada hora del día
    for ($hora = $config['horario_apertura']; $hora <= $config['horario_cierre']; $hora++) {
        $metaHoraria = $config['meta_diaria'] * $config['patrones_horarios'][$hora] * $multiplicadorDia;
        $variacion = 1 + (rand(-15, 15) / 100);
        $metaHoraria *= $variacion;
        
        $ventasHora = [];
        $totalGenerado = 0;
        
        // Generar ventas para alcanzar la meta horaria
        while ($totalGenerado < $metaHoraria && count($ventasHora) < 30) {
            $producto = seleccionarProductoAleatorio($config['productos_populares']);
            $cantidad = rand(1, 3);
            $metodoPago = seleccionarMetodoPago($config['metodos_pago']);
            $subtotal = $producto['precio'] * $cantidad;
            $costoVenta = $producto['costo'] * $cantidad;
            
            $venta = [
                'id' => $estadisticas['total_ventas'] + count($ventasHora) + 1,
                'dia' => $dia,
                'hora' => $hora,
                'fecha' => date('Y-m-d'),
                'hora_completa' => sprintf('%02d:00', $hora),
                'producto_id' => $producto['id'],
                'producto_nombre' => $producto['nombre'],
                'categoria' => $producto['categoria'],
                'precio_unitario' => $producto['precio'],
                'precio_costo' => $producto['costo'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
                'costo_total' => $costoVenta,
                'margen' => $subtotal - $costoVenta,
                'metodo_pago' => $metodoPago
            ];
            
            $ventasHora[] = $venta;
            $totalGenerado += $subtotal;
        }
        
        $ventasDelDia = array_merge($ventasDelDia, $ventasHora);
        $facturacionDia += $totalGenerado;
        $costosDia += array_sum(array_column($ventasHora, 'costo_total'));
        
        // Actualizar estadísticas por hora
        if (!isset($estadisticas['ventas_por_hora'][$hora])) {
            $estadisticas['ventas_por_hora'][$hora] = 0;
        }
        $estadisticas['ventas_por_hora'][$hora] += $totalGenerado;
        
        // Mostrar progreso cada 4 horas
        if ($hora % 4 == 0) {
            echo sprintf("  ⏰ %02d:00 - Ventas: \$%s (%d operaciones)\n", 
                $hora, number_format($totalGenerado, 0, ',', '.'), count($ventasHora));
        }
    }
    
    // Simular movimientos de caja del día
    $movimientosDia = [];
    $movimientosTipos = [
        ['tipo' => 'egreso', 'concepto' => 'Compra de mercadería', 'monto' => rand(80000, 150000)],
        ['tipo' => 'egreso', 'concepto' => 'Servicios públicos', 'monto' => rand(15000, 35000)],
        ['tipo' => 'egreso', 'concepto' => 'Limpieza y suministros', 'monto' => rand(8000, 20000)],
        ['tipo' => 'ingreso', 'concepto' => 'Venta de cartones', 'monto' => rand(3000, 8000)],
    ];
    
    $probabilidades = [1 => 0.8, 2 => 0.3, 3 => 0.4, 4 => 0.6, 5 => 0.5, 6 => 0.2, 7 => 0.1];
    
    foreach ($movimientosTipos as $mov) {
        if (rand(1, 100) / 100 <= $probabilidades[$dia]) {
            $movimiento = [
                'dia' => $dia,
                'fecha' => date('Y-m-d'),
                'tipo' => $mov['tipo'],
                'concepto' => $mov['concepto'],
                'monto' => $mov['monto']
            ];
            $movimientosDia[] = $movimiento;
        }
    }
    
    // Actualizar estadísticas
    $estadisticas['ventas_por_dia'][$nombreDia] = [
        'cantidad_ventas' => count($ventasDelDia),
        'facturacion' => $facturacionDia,
        'costos' => $costosDia,
        'margen_bruto' => $facturacionDia - $costosDia,
        'movimientos' => count($movimientosDia)
    ];
    
    // Contar por método de pago
    foreach ($ventasDelDia as $venta) {
        $estadisticas['ventas_por_metodo'][$venta['metodo_pago']]++;
        
        // Contar productos
        $nombreProducto = $venta['producto_nombre'];
        if (!isset($estadisticas['productos_vendidos'][$nombreProducto])) {
            $estadisticas['productos_vendidos'][$nombreProducto] = [
                'cantidad' => 0,
                'facturacion' => 0,
                'categoria' => $venta['categoria']
            ];
        }
        $estadisticas['productos_vendidos'][$nombreProducto]['cantidad'] += $venta['cantidad'];
        $estadisticas['productos_vendidos'][$nombreProducto]['facturacion'] += $venta['subtotal'];
    }
    
    $estadisticas['total_ventas'] += count($ventasDelDia);
    $estadisticas['total_facturado'] += $facturacionDia;
    $estadisticas['total_costo'] += $costosDia;
    $estadisticas['movimientos_caja'] = array_merge($estadisticas['movimientos_caja'], $movimientosDia);
    
    // Guardar ventas del día
    $todasLasVentas = array_merge($todasLasVentas, $ventasDelDia);
    $todosLosMovimientos = array_merge($todosLosMovimientos, $movimientosDia);
    
    echo sprintf("💰 Total del día: \$%s (%d ventas, \$%s margen bruto)\n", 
        number_format($facturacionDia, 0, ',', '.'), 
        count($ventasDelDia),
        number_format($facturacionDia - $costosDia, 0, ',', '.')
    );
    echo "\n";
}

// GENERAR REPORTES FINALES
echo "📊 GENERANDO REPORTES...\n\n";

// Reporte de resumen
echo "🎯 RESUMEN GENERAL:\n";
echo sprintf("• Total de ventas procesadas: %d\n", $estadisticas['total_ventas']);
echo sprintf("• Facturación total semanal: \$%s\n", number_format($estadisticas['total_facturado'], 0, ',', '.'));
echo sprintf("• Costos totales: \$%s\n", number_format($estadisticas['total_costo'], 0, ',', '.'));
echo sprintf("• Margen bruto semanal: \$%s (%.1f%%)\n", 
    number_format($estadisticas['total_facturado'] - $estadisticas['total_costo'], 0, ',', '.'),
    (($estadisticas['total_facturado'] - $estadisticas['total_costo']) / $estadisticas['total_facturado']) * 100
);
echo sprintf("• Promedio diario: \$%s\n", number_format($estadisticas['total_facturado'] / 7, 0, ',', '.'));
echo sprintf("• Ticket promedio: \$%s\n", number_format($estadisticas['total_facturado'] / $estadisticas['total_ventas'], 0, ',', '.'));

echo "\n📅 VENTAS POR DÍA:\n";
foreach ($estadisticas['ventas_por_dia'] as $dia => $datos) {
    echo sprintf("• %s: \$%s (%d ventas, %.1f%% margen)\n", 
        $dia, 
        number_format($datos['facturacion'], 0, ',', '.'), 
        $datos['cantidad_ventas'],
        ($datos['margen_bruto'] / $datos['facturacion']) * 100
    );
}

echo "\n💳 DISTRIBUCIÓN POR MÉTODO DE PAGO:\n";
foreach ($estadisticas['ventas_por_metodo'] as $metodo => $cantidad) {
    $porcentaje = ($cantidad / $estadisticas['total_ventas']) * 100;
    echo sprintf("• %s: %d ventas (%.1f%%)\n", ucfirst($metodo), $cantidad, $porcentaje);
}

echo "\n🏆 TOP 10 PRODUCTOS MÁS VENDIDOS:\n";
uasort($estadisticas['productos_vendidos'], function($a, $b) {
    return $b['facturacion'] <=> $a['facturacion'];
});
$top10 = array_slice($estadisticas['productos_vendidos'], 0, 10, true);
foreach ($top10 as $producto => $datos) {
    echo sprintf("• %s: %d unidades - \$%s\n", 
        $producto, 
        $datos['cantidad'], 
        number_format($datos['facturacion'], 0, ',', '.')
    );
}

// Guardar archivos
echo "\n💾 GUARDANDO ARCHIVOS...\n";

// CSV de ventas
$csvVentas = fopen('simulacion_ventas.csv', 'w');
fputcsv($csvVentas, ['ID', 'Día', 'Hora', 'Producto', 'Categoría', 'Cantidad', 'Precio Unit.', 'Subtotal', 'Método Pago']);
foreach ($todasLasVentas as $venta) {
    fputcsv($csvVentas, [
        $venta['id'], $venta['dia'], $venta['hora_completa'], $venta['producto_nombre'],
        $venta['categoria'], $venta['cantidad'], $venta['precio_unitario'], 
        $venta['subtotal'], $venta['metodo_pago']
    ]);
}
fclose($csvVentas);

// JSON completo
file_put_contents('simulacion_completa.json', json_encode([
    'resumen' => $estadisticas,
    'ventas' => $todasLasVentas,
    'movimientos' => $todosLosMovimientos,
    'fecha_simulacion' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// CSV de resumen diario
$csvResumen = fopen('simulacion_resumen_diario.csv', 'w');
fputcsv($csvResumen, ['Día', 'Cantidad Ventas', 'Facturación', 'Costos', 'Margen Bruto', '% Margen']);
foreach ($estadisticas['ventas_por_dia'] as $dia => $datos) {
    fputcsv($csvResumen, [
        $dia, $datos['cantidad_ventas'], $datos['facturacion'], 
        $datos['costos'], $datos['margen_bruto'], 
        round(($datos['margen_bruto'] / $datos['facturacion']) * 100, 1)
    ]);
}
fclose($csvResumen);

echo "✅ Archivos generados:\n";
echo "  📄 simulacion_ventas.csv - Detalle de todas las ventas\n";
echo "  📄 simulacion_resumen_diario.csv - Resumen por día\n";
echo "  📄 simulacion_completa.json - Datos completos en JSON\n";

echo "\n🎉 SIMULACIÓN COMPLETADA EXITOSAMENTE\n";
echo "📊 Los datos generados representan una operatoria realista de 7 días\n";
echo "💰 Meta objetivo: \$" . number_format($config['meta_diaria'] * 7, 0, ',', '.') . " semanal\n";
echo "🎯 Logrado: \$" . number_format($estadisticas['total_facturado'], 0, ',', '.') . " semanal\n";
echo "📈 Diferencia: " . (($estadisticas['total_facturado'] / ($config['meta_diaria'] * 7)) * 100 - 100 > 0 ? "+" : "") . 
    round(($estadisticas['total_facturado'] / ($config['meta_diaria'] * 7)) * 100 - 100, 1) . "%\n";

?>














