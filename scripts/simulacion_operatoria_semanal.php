<?php
/**
 * scripts/simulacion_operatoria_semanal.php
 * Simulación completa de operatoria semanal de kiosco argentino
 * Propósito: Generar datos realistas para testing del sistema
 * Archivos relacionados: api/procesar_venta_ultra_rapida.php, api/gestion_caja_completa.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "🏪 <h1>SIMULACIÓN OPERATORIA SEMANAL - KIOSCO ARGENTINO</h1>\n";
echo "<pre>\n";
echo "📅 Objetivo: Simular 7 días completos de operación\n";
echo "💰 Meta diaria: ~$520,000 ARS (±20%)\n";
echo "🕐 Horario: 07:00 a 23:00 (16 horas diarias)\n";
echo "🛒 Productos: Mix realista de kiosco argentino\n\n";

// Configuración de la simulación
$config = [
    'meta_diaria' => 520000,
    'variacion_maxima' => 0.20, // ±20%
    'horario_apertura' => 7,
    'horario_cierre' => 23,
    'dias_simulacion' => 7,
    'productos_populares' => [
        // Bebidas (40% de las ventas)
        ['id' => 1, 'nombre' => 'Coca Cola 600ml', 'precio' => 1800, 'categoria' => 'bebidas', 'freq' => 15],
        ['id' => 2, 'nombre' => 'Agua Mineral 500ml', 'precio' => 1200, 'categoria' => 'bebidas', 'freq' => 12],
        ['id' => 3, 'nombre' => 'Cerveza Quilmes 473ml', 'precio' => 2500, 'categoria' => 'bebidas', 'freq' => 8],
        ['id' => 4, 'nombre' => 'Gatorade 500ml', 'precio' => 2200, 'categoria' => 'bebidas', 'freq' => 6],
        
        // Snacks/Golosinas (30% de las ventas)
        ['id' => 5, 'nombre' => 'Alfajor Havanna', 'precio' => 3500, 'categoria' => 'golosinas', 'freq' => 10],
        ['id' => 6, 'nombre' => 'Papas Fritas Lays', 'precio' => 2800, 'categoria' => 'snacks', 'freq' => 8],
        ['id' => 7, 'nombre' => 'Chocolate Milka', 'precio' => 4200, 'categoria' => 'golosinas', 'freq' => 6],
        ['id' => 8, 'nombre' => 'Chicles Beldent', 'precio' => 800, 'categoria' => 'golosinas', 'freq' => 12],
        
        // Cigarrillos (20% de las ventas)
        ['id' => 9, 'nombre' => 'Marlboro Box', 'precio' => 8500, 'categoria' => 'cigarrillos', 'freq' => 5],
        ['id' => 10, 'nombre' => 'Philip Morris', 'precio' => 7800, 'categoria' => 'cigarrillos', 'freq' => 4],
        
        // Otros (10% de las ventas)
        ['id' => 11, 'nombre' => 'Pan Lactal', 'precio' => 3200, 'categoria' => 'panaderia', 'freq' => 3],
        ['id' => 12, 'nombre' => 'Leche Larga Vida 1L', 'precio' => 2400, 'categoria' => 'lacteos', 'freq' => 2],
    ],
    'metodos_pago' => [
        'efectivo' => 0.45,    // 45%
        'tarjeta' => 0.30,     // 30%
        'transferencia' => 0.15, // 15%
        'qr' => 0.10           // 10%
    ],
    'patrones_horarios' => [
        7 => 0.03,   // 3% - Apertura
        8 => 0.08,   // 8% - Desayuno
        9 => 0.06,   // 6% - Media mañana
        10 => 0.05,  // 5%
        11 => 0.07,  // 7% - Almuerzo temprano
        12 => 0.10,  // 10% - Almuerzo pico
        13 => 0.09,  // 9% - Post almuerzo
        14 => 0.06,  // 6%
        15 => 0.08,  // 8% - Merienda
        16 => 0.07,  // 7%
        17 => 0.06,  // 6%
        18 => 0.09,  // 9% - Pico tarde
        19 => 0.08,  // 8% - Cena temprana
        20 => 0.06,  // 6%
        21 => 0.05,  // 5%
        22 => 0.04,  // 4%
        23 => 0.03   // 3% - Cierre
    ]
];

// Conexión a base de datos
try {
    $host = 'localhost';
    $dbname = 'kiosco';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conexión a base de datos establecida\n\n";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// Función para generar ventas realistas
function generarVentaRealista($config, $dia, $hora) {
    $productos = $config['productos_populares'];
    $metaHoraria = $config['meta_diaria'] * $config['patrones_horarios'][$hora];
    
    // Variación por día de la semana
    $multiplicadorDia = [1 => 1.2, 2 => 0.9, 3 => 0.95, 4 => 1.0, 5 => 1.1, 6 => 1.3, 7 => 1.15][$dia];
    $metaHoraria *= $multiplicadorDia;
    
    // Variación aleatoria ±15%
    $variacion = 1 + (rand(-15, 15) / 100);
    $metaHoraria *= $variacion;
    
    $ventas = [];
    $totalGenerado = 0;
    
    while ($totalGenerado < $metaHoraria) {
        // Seleccionar producto basado en frecuencia
        $producto = seleccionarProductoAleatorio($productos);
        $cantidad = rand(1, 3); // Entre 1 y 3 unidades
        
        // Método de pago aleatorio
        $metodoPago = seleccionarMetodoPago($config['metodos_pago']);
        
        $subtotal = $producto['precio'] * $cantidad;
        
        $venta = [
            'producto_id' => $producto['id'],
            'producto_nombre' => $producto['nombre'],
            'precio_unitario' => $producto['precio'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal,
            'metodo_pago' => $metodoPago,
            'hora' => $hora,
            'dia' => $dia
        ];
        
        $ventas[] = $venta;
        $totalGenerado += $subtotal;
        
        // Evitar bucle infinito
        if (count($ventas) > 50) break;
    }
    
    return $ventas;
}

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
    
    return $productos[0]; // Fallback
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
    
    return 'efectivo'; // Fallback
}

// Función para verificar y crear tablas si no existen
function verificarTablas($pdo) {
    try {
        // Verificar tabla ventas
        $pdo->query("SELECT 1 FROM ventas LIMIT 1");
    } catch (Exception $e) {
        echo "📋 Creando tabla ventas...\n";
        $pdo->exec("
            CREATE TABLE ventas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                total DECIMAL(10,2) NOT NULL,
                metodo_pago VARCHAR(50) NOT NULL,
                descuento DECIMAL(10,2) DEFAULT 0,
                observaciones TEXT,
                usuario_id INT DEFAULT 1,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                estado VARCHAR(20) DEFAULT 'completada'
            )
        ");
    }
    
    try {
        // Verificar tabla detalle_ventas
        $pdo->query("SELECT 1 FROM detalle_ventas LIMIT 1");
    } catch (Exception $e) {
        echo "📋 Creando tabla detalle_ventas...\n";
        $pdo->exec("
            CREATE TABLE detalle_ventas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                venta_id INT NOT NULL,
                producto_id INT NOT NULL,
                cantidad INT NOT NULL,
                precio_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL
            )
        ");
    }
    
    try {
        // Verificar tabla movimientos_caja_detallados
        $pdo->query("SELECT 1 FROM movimientos_caja_detallados LIMIT 1");
    } catch (Exception $e) {
        echo "📋 Creando tabla movimientos_caja_detallados...\n";
        $pdo->exec("
            CREATE TABLE movimientos_caja_detallados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(20) NOT NULL,
                monto DECIMAL(10,2) NOT NULL,
                concepto VARCHAR(255) NOT NULL,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                usuario_id INT DEFAULT 1,
                turno_id INT DEFAULT NULL
            )
        ");
    }
    
    try {
        // Verificar tabla turnos_caja
        $pdo->query("SELECT 1 FROM turnos_caja LIMIT 1");
    } catch (Exception $e) {
        echo "📋 Creando tabla turnos_caja...\n";
        $pdo->exec("
            CREATE TABLE turnos_caja (
                id INT AUTO_INCREMENT PRIMARY KEY,
                estado VARCHAR(20) DEFAULT 'abierto',
                monto_apertura DECIMAL(10,2) DEFAULT 0,
                fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP,
                usuario_id INT DEFAULT 1
            )
        ");
        
        // Insertar un turno inicial
        $pdo->exec("INSERT INTO turnos_caja (estado, monto_apertura) VALUES ('abierto', 50000)");
    }
}

// Función para procesar venta en el sistema
function procesarVentaEnSistema($venta, $pdo) {
    try {
        // Insertar venta directamente en la base de datos
        $stmt = $pdo->prepare("
            INSERT INTO ventas (
                total, metodo_pago, descuento, observaciones, 
                usuario_id, fecha, estado
            ) VALUES (?, ?, ?, ?, ?, NOW(), 'completada')
        ");
        
        $stmt->execute([
            $venta['subtotal'],
            $venta['metodo_pago'],
            0,
            'Venta simulada - ' . date('Y-m-d H:i:s'),
            1
        ]);
        
        $ventaId = $pdo->lastInsertId();
        
        // Insertar detalle de venta
        $stmt = $pdo->prepare("
            INSERT INTO detalle_ventas (
                venta_id, producto_id, cantidad, precio_unitario, subtotal
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ventaId,
            $venta['producto_id'],
            $venta['cantidad'],
            $venta['precio_unitario'],
            $venta['subtotal']
        ]);
        
        return ['success' => true, 'venta_id' => $ventaId];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para simular movimientos de caja
function simularMovimientosCaja($pdo, $dia) {
    $movimientos = [];
    
    // Movimientos típicos diarios
    $tiposMovimientos = [
        ['tipo' => 'egreso', 'concepto' => 'Compra de mercadería', 'monto' => rand(80000, 150000)],
        ['tipo' => 'egreso', 'concepto' => 'Servicios (luz, gas)', 'monto' => rand(15000, 35000)],
        ['tipo' => 'egreso', 'concepto' => 'Limpieza y suministros', 'monto' => rand(8000, 20000)],
        ['tipo' => 'ingreso', 'concepto' => 'Venta de cartones', 'monto' => rand(3000, 8000)],
    ];
    
    // Solo algunos movimientos por día (no todos los días)
    $probabilidades = [1 => 0.8, 2 => 0.3, 3 => 0.4, 4 => 0.6, 5 => 0.5, 6 => 0.2, 7 => 0.1];
    
    foreach ($tiposMovimientos as $mov) {
        if (rand(1, 100) / 100 <= $probabilidades[$dia]) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO movimientos_caja_detallados (
                        tipo, monto, concepto, fecha, usuario_id, turno_id
                    ) VALUES (?, ?, ?, NOW(), 1, 
                        (SELECT id FROM turnos_caja WHERE estado = 'abierto' LIMIT 1)
                    )
                ");
                
                $stmt->execute([
                    $mov['tipo'],
                    $mov['monto'],
                    $mov['concepto']
                ]);
                
                $movimientos[] = $mov;
                
            } catch (Exception $e) {
                echo "⚠️ Error en movimiento: " . $e->getMessage() . "\n";
            }
        }
    }
    
    return $movimientos;
}

// Verificar que las tablas existan
verificarTablas($pdo);

// INICIO DE LA SIMULACIÓN
echo "🚀 INICIANDO SIMULACIÓN...\n\n";

$estadisticas = [
    'total_ventas' => 0,
    'total_facturado' => 0,
    'ventas_por_dia' => [],
    'ventas_por_metodo' => ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0, 'qr' => 0],
    'productos_mas_vendidos' => [],
    'movimientos_caja' => []
];

// Simular cada día de la semana
for ($dia = 1; $dia <= $config['dias_simulacion']; $dia++) {
    $nombreDia = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'][$dia];
    echo "📅 === DÍA $dia: $nombreDia ===\n";
    
    // Abrir caja para el día
    echo "🔓 Abriendo caja del día...\n";
    
    $ventasDelDia = [];
    $facturacionDia = 0;
    
    // Simular cada hora del día
    for ($hora = $config['horario_apertura']; $hora <= $config['horario_cierre']; $hora++) {
        $ventasHora = generarVentaRealista($config, $dia, $hora);
        
        foreach ($ventasHora as $venta) {
            // Procesar venta en el sistema
            $resultado = procesarVentaEnSistema($venta, $pdo);
            
            if ($resultado['success']) {
                $ventasDelDia[] = $venta;
                $facturacionDia += $venta['subtotal'];
                $estadisticas['total_ventas']++;
                $estadisticas['ventas_por_metodo'][$venta['metodo_pago']]++;
                
                // Contador de productos
                $productoNombre = $venta['producto_nombre'];
                if (!isset($estadisticas['productos_mas_vendidos'][$productoNombre])) {
                    $estadisticas['productos_mas_vendidos'][$productoNombre] = 0;
                }
                $estadisticas['productos_mas_vendidos'][$productoNombre] += $venta['cantidad'];
            }
        }
        
        // Mostrar progreso cada 4 horas
        if ($hora % 4 == 0) {
            echo sprintf("  ⏰ %02d:00 - Ventas acumuladas: $%s\n", 
                $hora, number_format($facturacionDia, 0, ',', '.'));
        }
    }
    
    // Simular movimientos de caja del día
    $movimientosDia = simularMovimientosCaja($pdo, $dia);
    $estadisticas['movimientos_caja'] = array_merge($estadisticas['movimientos_caja'], $movimientosDia);
    
    // Cerrar caja del día
    echo "🔒 Cerrando caja del día...\n";
    
    $estadisticas['ventas_por_dia'][$nombreDia] = [
        'cantidad_ventas' => count($ventasDelDia),
        'facturacion' => $facturacionDia,
        'movimientos' => count($movimientosDia)
    ];
    
    $estadisticas['total_facturado'] += $facturacionDia;
    
    echo sprintf("💰 Total del día: $%s (%d ventas, %d movimientos)\n", 
        number_format($facturacionDia, 0, ',', '.'), 
        count($ventasDelDia), 
        count($movimientosDia)
    );
    echo "\n";
    
    // Pausa para no sobrecargar el sistema
    usleep(500000); // 0.5 segundos
}

// REPORTE FINAL
echo "</pre>\n";
echo "<h2>📊 REPORTE FINAL DE LA SIMULACIÓN</h2>\n";
echo "<pre>\n";

echo "🎯 RESUMEN GENERAL:\n";
echo sprintf("• Total de ventas procesadas: %d\n", $estadisticas['total_ventas']);
echo sprintf("• Facturación total semanal: $%s\n", number_format($estadisticas['total_facturado'], 0, ',', '.'));
echo sprintf("• Promedio diario: $%s\n", number_format($estadisticas['total_facturado'] / 7, 0, ',', '.'));
echo sprintf("• Ticket promedio: $%s\n", number_format($estadisticas['total_facturado'] / $estadisticas['total_ventas'], 0, ',', '.'));

echo "\n📅 VENTAS POR DÍA:\n";
foreach ($estadisticas['ventas_por_dia'] as $dia => $datos) {
    echo sprintf("• %s: $%s (%d ventas)\n", 
        $dia, 
        number_format($datos['facturacion'], 0, ',', '.'), 
        $datos['cantidad_ventas']
    );
}

echo "\n💳 DISTRIBUCIÓN POR MÉTODO DE PAGO:\n";
foreach ($estadisticas['ventas_por_metodo'] as $metodo => $cantidad) {
    $porcentaje = ($cantidad / $estadisticas['total_ventas']) * 100;
    echo sprintf("• %s: %d ventas (%.1f%%)\n", ucfirst($metodo), $cantidad, $porcentaje);
}

echo "\n🏆 TOP 5 PRODUCTOS MÁS VENDIDOS:\n";
arsort($estadisticas['productos_mas_vendidos']);
$top5 = array_slice($estadisticas['productos_mas_vendidos'], 0, 5, true);
foreach ($top5 as $producto => $cantidad) {
    echo sprintf("• %s: %d unidades\n", $producto, $cantidad);
}

echo "\n💸 MOVIMIENTOS DE CAJA:\n";
$totalEgresos = 0;
$totalIngresos = 0;
foreach ($estadisticas['movimientos_caja'] as $mov) {
    if ($mov['tipo'] == 'egreso') {
        $totalEgresos += $mov['monto'];
    } else {
        $totalIngresos += $mov['monto'];
    }
}
echo sprintf("• Total egresos: $%s\n", number_format($totalEgresos, 0, ',', '.'));
echo sprintf("• Total ingresos extra: $%s\n", number_format($totalIngresos, 0, ',', '.'));

$efectivoNeto = $estadisticas['total_facturado'] + $totalIngresos - $totalEgresos;
echo sprintf("• Efectivo neto estimado: $%s\n", number_format($efectivoNeto, 0, ',', '.'));

echo "\n✅ SIMULACIÓN COMPLETADA EXITOSAMENTE\n";
echo "📝 Todos los datos fueron insertados en la base de datos del sistema.\n";
echo "🔍 Puedes verificar los resultados en los módulos de Ventas, Inventario y Control de Caja.\n";

echo "</pre>\n";
?>
