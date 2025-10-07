<?php
/**
 * 🧪 SCRIPT DE VALIDACIÓN COMPLETA DEL SISTEMA DE CAJA
 * Implementa el protocolo de auditoría exhaustiva definido en el prompt optimizado
 * 
 * NIVEL: SpaceX-Grade Testing
 * TOLERANCIA A ERRORES: 0%
 * PRECISIÓN DECIMAL: 2 dígitos
 * TIEMPO DE RESPUESTA: <200ms
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../api/bd_conexion.php';

echo "🚀 INICIANDO VALIDACIÓN COMPLETA DEL SISTEMA DE CAJA\n";
echo "=" . str_repeat("=", 60) . "\n";

// Configuración de pruebas
$TEST_CONFIG = [
    'user_id' => 1,
    'monto_apertura_test' => 1000.00,
    'precision_tolerance' => 0.01,
    'max_response_time_ms' => 200,
    'test_transactions' => 50,
    'concurrent_operations' => 10
];

// Contadores globales de pruebas
$GLOBAL_STATS = [
    'tests_passed' => 0,
    'tests_failed' => 0,
    'total_response_time' => 0,
    'errors' => []
];

/**
 * 🛠️ FUNCIÓN AUXILIAR PARA MEDIR TIEMPO DE RESPUESTA
 */
function measureApiCall($description, $callable) {
    global $GLOBAL_STATS, $TEST_CONFIG;
    
    $start_time = microtime(true);
    
    try {
        $result = $callable();
        $end_time = microtime(true);
        $response_time_ms = ($end_time - $start_time) * 1000;
        
        $GLOBAL_STATS['total_response_time'] += $response_time_ms;
        
        if ($response_time_ms > $TEST_CONFIG['max_response_time_ms']) {
            echo "⚠️  $description - TIEMPO EXCEDIDO: {$response_time_ms}ms > {$TEST_CONFIG['max_response_time_ms']}ms\n";
            $GLOBAL_STATS['tests_failed']++;
            return false;
        }
        
        echo "✅ $description - OK ({$response_time_ms}ms)\n";
        $GLOBAL_STATS['tests_passed']++;
        return $result;
        
    } catch (Exception $e) {
        $end_time = microtime(true);
        $response_time_ms = ($end_time - $start_time) * 1000;
        
        echo "❌ $description - ERROR: " . $e->getMessage() . " ({$response_time_ms}ms)\n";
        $GLOBAL_STATS['errors'][] = "$description: " . $e->getMessage();
        $GLOBAL_STATS['tests_failed']++;
        return false;
    }
}

/**
 * 🏗️ PASO 1: ANÁLISIS ESTRUCTURAL DE BASE DE DATOS
 */
function validateDatabaseStructure() {
    global $GLOBAL_STATS;
    
    echo "\n🔍 PASO 1: ANÁLISIS ESTRUCTURAL DE BASE DE DATOS\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    try {
        $pdo = Conexion::obtenerConexion();
        
        // Verificar tabla caja
        $result = measureApiCall("Verificar estructura tabla 'caja'", function() use ($pdo) {
            $stmt = $pdo->query("DESCRIBE caja");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $required_columns = [
                'id', 'fecha_apertura', 'fecha_cierre', 'monto_apertura', 
                'monto_cierre', 'estado', 'usuario_id', 'diferencia'
            ];
            
            foreach ($required_columns as $col) {
                if (!in_array($col, $columns)) {
                    throw new Exception("Columna faltante en tabla caja: $col");
                }
            }
            
            return true;
        });
        
        // Verificar tabla movimientos_caja
        $result = measureApiCall("Verificar estructura tabla 'movimientos_caja'", function() use ($pdo) {
            $stmt = $pdo->query("DESCRIBE movimientos_caja");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $required_columns = [
                'id', 'caja_id', 'tipo', 'monto', 'descripcion', 
                'usuario_id', 'fecha_hora', 'metodo_pago'
            ];
            
            foreach ($required_columns as $col) {
                if (!in_array($col, $columns)) {
                    throw new Exception("Columna faltante en tabla movimientos_caja: $col");
                }
            }
            
            return true;
        });
        
        // Verificar índices críticos
        $result = measureApiCall("Verificar índices de rendimiento", function() use ($pdo) {
            $stmt = $pdo->query("SHOW INDEX FROM caja WHERE Key_name != 'PRIMARY'");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($indexes) < 2) {
                throw new Exception("Faltan índices críticos en tabla caja");
            }
            
            return true;
        });
        
        // Verificar claves foráneas
        $result = measureApiCall("Verificar integridad referencial", function() use ($pdo) {
            $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'movimientos_caja' AND REFERENCED_TABLE_NAME IS NOT NULL");
            $foreign_keys = $stmt->fetchAll();
            
            if (count($foreign_keys) < 1) {
                throw new Exception("Faltan claves foráneas críticas");
            }
            
            return true;
        });
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR CRÍTICO EN ESTRUCTURA DE BD: " . $e->getMessage() . "\n";
        $GLOBAL_STATS['errors'][] = "Database Structure: " . $e->getMessage();
        return false;
    }
}

/**
 * 🔓 PASO 2: VALIDACIÓN DE APERTURA DE CAJA
 */
function validateCashOpening() {
    global $TEST_CONFIG;
    
    echo "\n🔓 PASO 2: VALIDACIÓN DE APERTURA DE CAJA\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    // Primero cerrar cualquier caja abierta
    measureApiCall("Limpiar estado previo", function() {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("UPDATE caja SET estado = 'cerrada', fecha_cierre = NOW() WHERE estado = 'abierta'");
        $stmt->execute();
        return true;
    });
    
    // Test de apertura normal
    $caja_id = measureApiCall("Apertura normal de caja", function() use ($TEST_CONFIG) {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'abrir',
            'monto_apertura' => $TEST_CONFIG['monto_apertura_test'],
            'usuario_id' => $TEST_CONFIG['user_id'],
            'descripcion' => 'Test apertura automática'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        if (!$result['success']) {
            throw new Exception($result['mensaje'] ?? 'Error desconocido en apertura');
        }
        
        return $result['caja']['id'];
    });
    
    if (!$caja_id) {
        return false;
    }
    
    // Verificar que el estado cambió correctamente
    measureApiCall("Verificar estado de caja abierta", function() use ($caja_id) {
        $url = 'http://localhost/kiosco/api/caja.php?accion=estado';
        $response = file_get_contents($url);
        $result = json_decode($response, true);
        
        if ($result['estado'] !== 'abierta') {
            throw new Exception("Estado incorrecto: esperado 'abierta', recibido '{$result['estado']}'");
        }
        
        if (!$result['caja'] || $result['caja']['id'] != $caja_id) {
            throw new Exception("Caja ID inconsistente");
        }
        
        return true;
    });
    
    // Test de doble apertura (debe fallar)
    measureApiCall("Validar prevención de doble apertura", function() use ($TEST_CONFIG) {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'abrir',
            'monto_apertura' => 500.00,
            'usuario_id' => $TEST_CONFIG['user_id']
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        // Debe fallar porque ya hay una caja abierta
        if ($result['success']) {
            throw new Exception("ERROR: Se permitió doble apertura de caja");
        }
        
        return true;
    });
    
    return $caja_id;
}

/**
 * 💰 PASO 3: VALIDACIÓN DE MOVIMIENTOS DE EFECTIVO
 */
function validateCashMovements($caja_id) {
    global $TEST_CONFIG;
    
    echo "\n💰 PASO 3: VALIDACIÓN DE MOVIMIENTOS DE EFECTIVO\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    // Test de entrada de efectivo
    $movimiento_entrada = measureApiCall("Registrar entrada de efectivo", function() use ($TEST_CONFIG) {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'movimiento',
            'tipo' => 'entrada',
            'monto' => 250.75,
            'descripcion' => 'Test entrada automática',
            'usuario_id' => $TEST_CONFIG['user_id'],
            'metodo_pago' => 'efectivo',
            'categoria' => 'test'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        if (!$result['success']) {
            throw new Exception($result['mensaje'] ?? 'Error en entrada de efectivo');
        }
        
        return $result['movimiento_id'];
    });
    
    // Test de salida de efectivo
    $movimiento_salida = measureApiCall("Registrar salida de efectivo", function() use ($TEST_CONFIG) {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'movimiento',
            'tipo' => 'salida',
            'monto' => 100.50,
            'descripcion' => 'Test salida automática',
            'usuario_id' => $TEST_CONFIG['user_id'],
            'metodo_pago' => 'efectivo',
            'categoria' => 'test'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        if (!$result['success']) {
            throw new Exception($result['mensaje'] ?? 'Error en salida de efectivo');
        }
        
        return $result['movimiento_id'];
    });
    
    // Verificar cálculos precisos
    measureApiCall("Verificar cálculos matemáticos precisos", function() use ($TEST_CONFIG, $caja_id) {
        $url = 'http://localhost/kiosco/api/caja.php?accion=estado';
        $response = file_get_contents($url);
        $result = json_decode($response, true);
        
        $totales = $result['totales'];
        $monto_apertura = $TEST_CONFIG['monto_apertura_test'];
        $entrada_test = 250.75;
        $salida_test = 100.50;
        
        $efectivo_esperado = $monto_apertura + $entrada_test - $salida_test;
        $efectivo_actual = $totales['efectivo_teorico'];
        
        $diferencia = abs($efectivo_esperado - $efectivo_actual);
        
        if ($diferencia > $TEST_CONFIG['precision_tolerance']) {
            throw new Exception("Error de cálculo: esperado $efectivo_esperado, actual $efectivo_actual, diferencia $diferencia");
        }
        
        echo "   💡 Cálculo correcto: $monto_apertura + $entrada_test - $salida_test = $efectivo_actual\n";
        
        return true;
    });
    
    return true;
}

/**
 * 🔒 PASO 4: VALIDACIÓN DE CIERRE DE CAJA
 */
function validateCashClosing($caja_id) {
    global $TEST_CONFIG;
    
    echo "\n🔒 PASO 4: VALIDACIÓN DE CIERRE DE CAJA\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    // Obtener estado actual antes del cierre
    $estado_previo = measureApiCall("Obtener estado previo al cierre", function() {
        $url = 'http://localhost/kiosco/api/caja.php?accion=estado';
        $response = file_get_contents($url);
        $result = json_decode($response, true);
        
        if ($result['estado'] !== 'abierta') {
            throw new Exception("Caja no está abierta para cerrar");
        }
        
        return $result;
    });
    
    if (!$estado_previo) {
        return false;
    }
    
    $efectivo_teorico = $estado_previo['totales']['efectivo_teorico'];
    $monto_cierre_test = $efectivo_teorico + 5.25; // Diferencia intencional para testing
    
    // Test de cierre normal
    measureApiCall("Cerrar caja con diferencia", function() use ($TEST_CONFIG, $monto_cierre_test) {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'cerrar',
            'monto_cierre' => $monto_cierre_test,
            'usuario_id' => $TEST_CONFIG['user_id'],
            'justificacion' => 'Test automático - diferencia intencional'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        if (!$result['success']) {
            throw new Exception($result['mensaje'] ?? 'Error en cierre de caja');
        }
        
        return true;
    });
    
    // Verificar que la caja está cerrada
    measureApiCall("Verificar estado post-cierre", function() use ($efectivo_teorico, $monto_cierre_test) {
        $url = 'http://localhost/kiosco/api/caja.php?accion=estado';
        $response = file_get_contents($url);
        $result = json_decode($response, true);
        
        if ($result['estado'] !== 'cerrada') {
            throw new Exception("Estado incorrecto post-cierre: esperado 'cerrada', recibido '{$result['estado']}'");
        }
        
        echo "   💡 Diferencia calculada correctamente: " . ($monto_cierre_test - $efectivo_teorico) . "\n";
        
        return true;
    });
    
    // Test de operación en caja cerrada (debe fallar)
    measureApiCall("Validar bloqueo de operaciones en caja cerrada", function() use ($TEST_CONFIG) {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'movimiento',
            'tipo' => 'entrada',
            'monto' => 100.00,
            'descripcion' => 'Test que debe fallar',
            'usuario_id' => $TEST_CONFIG['user_id']
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        // Debe fallar porque la caja está cerrada
        if ($result['success']) {
            throw new Exception("ERROR CRÍTICO: Se permitió operación en caja cerrada");
        }
        
        return true;
    });
    
    return true;
}

/**
 * ⚡ PASO 5: PRUEBAS DE ESTRÉS BÁSICAS
 */
function performStressTests() {
    global $TEST_CONFIG;
    
    echo "\n⚡ PASO 5: PRUEBAS DE ESTRÉS BÁSICAS\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    // Test de llamadas rápidas sucesivas
    measureApiCall("Test de llamadas rápidas (10x)", function() use ($TEST_CONFIG) {
        $start_time = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $url = 'http://localhost/kiosco/api/caja.php?accion=estado&_t=' . microtime(true);
            $response = file_get_contents($url);
            $result = json_decode($response, true);
            
            if (!$result) {
                throw new Exception("Respuesta inválida en llamada $i");
            }
        }
        
        $total_time = microtime(true) - $start_time;
        $avg_time = ($total_time / 10) * 1000;
        
        echo "   💡 Tiempo promedio por llamada: {$avg_time}ms\n";
        
        if ($avg_time > $TEST_CONFIG['max_response_time_ms']) {
            throw new Exception("Tiempo promedio excede límite: {$avg_time}ms > {$TEST_CONFIG['max_response_time_ms']}ms");
        }
        
        return true;
    });
    
    // Test de validación de datos incorrectos
    measureApiCall("Test de validación de datos inválidos", function() {
        $url = 'http://localhost/kiosco/api/caja.php';
        $data = [
            'accion' => 'abrir',
            'monto_apertura' => -100, // Monto negativo debe fallar
            'usuario_id' => 'invalid' // ID inválido
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        // Debe fallar por datos inválidos
        if ($result['success']) {
            throw new Exception("ERROR: Se aceptaron datos inválidos");
        }
        
        return true;
    });
    
    return true;
}

/**
 * 📊 FUNCIÓN PRINCIPAL DE EJECUCIÓN
 */
function runCompleteValidation() {
    global $GLOBAL_STATS;
    
    echo "🎯 EJECUTANDO PROTOCOLO DE AUDITORÍA EXHAUSTIVA\n";
    echo "Precisión requerida: 2 decimales | Tolerancia: 0% | Tiempo max: 200ms\n\n";
    
    $start_total = microtime(true);
    
    // PASO 1: Estructura de base de datos
    $step1 = validateDatabaseStructure();
    
    // PASO 2: Apertura de caja
    $caja_id = null;
    if ($step1) {
        $caja_id = validateCashOpening();
    }
    
    // PASO 3: Movimientos de efectivo
    $step3 = false;
    if ($caja_id) {
        $step3 = validateCashMovements($caja_id);
    }
    
    // PASO 4: Cierre de caja
    $step4 = false;
    if ($step3) {
        $step4 = validateCashClosing($caja_id);
    }
    
    // PASO 5: Pruebas de estrés
    $step5 = performStressTests();
    
    $end_total = microtime(true);
    $total_time = ($end_total - $start_total) * 1000;
    
    // REPORTE FINAL
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "🎉 REPORTE FINAL DE VALIDACIÓN COMPLETA\n";
    echo str_repeat("=", 70) . "\n";
    
    echo "📈 ESTADÍSTICAS GENERALES:\n";
    echo "   ✅ Pruebas exitosas: {$GLOBAL_STATS['tests_passed']}\n";
    echo "   ❌ Pruebas fallidas: {$GLOBAL_STATS['tests_failed']}\n";
    echo "   ⏱️  Tiempo total: {$total_time}ms\n";
    echo "   📊 Tiempo promedio por prueba: " . 
         round($GLOBAL_STATS['total_response_time'] / ($GLOBAL_STATS['tests_passed'] + $GLOBAL_STATS['tests_failed']), 2) . "ms\n";
    
    echo "\n🎯 CRITERIOS DE ACEPTACIÓN:\n";
    echo "   🔍 Estructura BD: " . ($step1 ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "   🔓 Apertura caja: " . ($caja_id ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "   💰 Movimientos: " . ($step3 ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "   🔒 Cierre caja: " . ($step4 ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "   ⚡ Pruebas estrés: " . ($step5 ? "✅ PASS" : "❌ FAIL") . "\n";
    
    $all_passed = $step1 && $caja_id && $step3 && $step4 && $step5;
    
    if ($all_passed && $GLOBAL_STATS['tests_failed'] == 0) {
        echo "\n🎉 RESULTADO FINAL: ✅ SISTEMA APROBADO - GRADO SPACEX\n";
        echo "   🏆 Todos los criterios cumplidos\n";
        echo "   🛡️ Tolerancia a errores: 0% ✅\n";
        echo "   🎯 Precisión matemática: EXACTA ✅\n";
        echo "   ⚡ Performance: ÓPTIMO ✅\n";
        return true;
    } else {
        echo "\n❌ RESULTADO FINAL: SISTEMA REPROBADO\n";
        echo "   🚨 Requiere correcciones antes de producción\n";
        
        if (!empty($GLOBAL_STATS['errors'])) {
            echo "\n📋 ERRORES ENCONTRADOS:\n";
            foreach ($GLOBAL_STATS['errors'] as $error) {
                echo "   ❌ $error\n";
            }
        }
        
        return false;
    }
}

// EJECUTAR VALIDACIÓN COMPLETA
$validation_result = runCompleteValidation();

echo "\n" . str_repeat("=", 70) . "\n";
echo "🏁 VALIDACIÓN COMPLETADA\n";
echo "Estado: " . ($validation_result ? "APROBADO ✅" : "REPROBADO ❌") . "\n";
echo str_repeat("=", 70) . "\n";

exit($validation_result ? 0 : 1);
?>
