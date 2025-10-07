<?php
/**
 * scripts/diagnostico_inventario_performance.php
 * Script para medir y diagnosticar el rendimiento del módulo de inventario
 * Propósito: Detectar cuellos de botella y optimizar continuamente
 * Archivos relacionados: api/inventario-inteligente.php, src/components/InventarioInteligente.jsx
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ CONFIGURACIÓN DE DIAGNÓSTICO
$config = [
    'tests' => [
        'productos' => true,
        'analisis_abc' => true,
        'predicciones' => true,
        'alertas' => true,
        'analisis_ia' => true
    ],
    'samples' => 3, // Número de muestras por test
    'timeout' => 30 // Timeout por endpoint
];

$baseUrl = 'http://localhost/kiosco/api/inventario-inteligente.php';
$resultados = [];

function medirEndpoint($url, $samples = 3) {
    $tiempos = [];
    $errores = [];
    
    for ($i = 0; $i < $samples; $i++) {
        $start = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'method' => 'GET',
                'header' => [
                    'Cache-Control: no-cache',
                    'Pragma: no-cache'
                ]
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        $end = microtime(true);
        
        if ($response === false) {
            $errores[] = "Muestra $i: Error de conexión";
        } else {
            $data = json_decode($response, true);
            if (!$data || !isset($data['success'])) {
                $errores[] = "Muestra $i: Respuesta inválida";
            } else {
                $tiempos[] = ($end - $start) * 1000; // En milisegundos
            }
        }
        
        usleep(100000); // 100ms entre muestras
    }
    
    return [
        'tiempos' => $tiempos,
        'promedio' => count($tiempos) > 0 ? array_sum($tiempos) / count($tiempos) : 0,
        'minimo' => count($tiempos) > 0 ? min($tiempos) : 0,
        'maximo' => count($tiempos) > 0 ? max($tiempos) : 0,
        'errores' => $errores,
        'exitosas' => count($tiempos),
        'fallidas' => count($errores)
    ];
}

// ✅ EJECUTAR TESTS DE RENDIMIENTO
echo "🚀 Iniciando diagnóstico de rendimiento del inventario...\n";

foreach ($config['tests'] as $endpoint => $enabled) {
    if (!$enabled) continue;
    
    $url = $baseUrl . '?action=' . $endpoint;
    echo "⏱️  Midiendo $endpoint...\n";
    
    $resultado = medirEndpoint($url, $config['samples']);
    $resultados[$endpoint] = $resultado;
    
    // Evaluación de rendimiento
    $status = 'EXCELENTE';
    if ($resultado['promedio'] > 1000) $status = 'LENTO';
    elseif ($resultado['promedio'] > 500) $status = 'ACEPTABLE';
    elseif ($resultado['promedio'] > 200) $status = 'BUENO';
    
    $resultado['evaluacion'] = $status;
    $resultado['recomendacion'] = obtenerRecomendacion($resultado['promedio'], $endpoint);
    
    echo sprintf(
        "   📊 %s: %.0fms promedio (%s)\n",
        strtoupper($endpoint),
        $resultado['promedio'],
        $status
    );
}

function obtenerRecomendacion($promedio, $endpoint) {
    if ($promedio > 1000) {
        return "⚠️ CRÍTICO: Optimización urgente requerida";
    } elseif ($promedio > 500) {
        return "🔧 Considerar optimización de consultas";
    } elseif ($promedio > 200) {
        return "✅ Rendimiento aceptable, monitorear";
    } else {
        return "🚀 Rendimiento excelente";
    }
}

// ✅ GENERAR REPORTE FINAL
$promedioGeneral = 0;
$totalTests = 0;
$problemasDetectados = [];

foreach ($resultados as $endpoint => $datos) {
    $promedioGeneral += $datos['promedio'];
    $totalTests++;
    
    if ($datos['promedio'] > 500) {
        $problemasDetectados[] = [
            'endpoint' => $endpoint,
            'problema' => 'Tiempo de respuesta alto',
            'valor' => round($datos['promedio']) . 'ms',
            'prioridad' => $datos['promedio'] > 1000 ? 'ALTA' : 'MEDIA'
        ];
    }
    
    if ($datos['fallidas'] > 0) {
        $problemasDetectados[] = [
            'endpoint' => $endpoint,
            'problema' => 'Errores de conexión',
            'valor' => $datos['fallidas'] . ' de ' . $config['samples'],
            'prioridad' => 'ALTA'
        ];
    }
}

$promedioGeneral = $totalTests > 0 ? $promedioGeneral / $totalTests : 0;

// ✅ SALIDA JSON PARA INTEGRACIÓN
$reporte = [
    'timestamp' => date('Y-m-d H:i:s'),
    'rendimiento_general' => [
        'promedio' => round($promedioGeneral),
        'status' => $promedioGeneral < 200 ? 'EXCELENTE' : ($promedioGeneral < 500 ? 'BUENO' : 'NECESITA_OPTIMIZACION'),
        'tests_ejecutados' => $totalTests
    ],
    'detalles_endpoints' => $resultados,
    'problemas_detectados' => $problemasDetectados,
    'recomendaciones' => [
        'cache' => $promedioGeneral > 300 ? 'Implementar cache Redis' : 'Cache actual suficiente',
        'database' => count($problemasDetectados) > 0 ? 'Revisar índices de base de datos' : 'Base de datos optimizada',
        'backend' => $promedioGeneral > 500 ? 'Optimizar lógica de backend' : 'Backend eficiente'
    ],
    'config_utilizada' => $config
];

echo "\n📋 REPORTE FINAL:\n";
echo json_encode($reporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ✅ GUARDAR LOG HISTÓRICO
$logFile = 'diagnostico_inventario_' . date('Y-m-d') . '.json';
file_put_contents($logFile, json_encode($reporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n💾 Reporte guardado en: $logFile\n";
echo "🎯 Diagnóstico completado.\n";

?>














