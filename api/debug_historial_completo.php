<?php
/**
 * Debug del endpoint historial_completo
 * Identificar por qué se queda colgado en producción
 */

set_time_limit(30); // Máximo 30 segundos
ini_set('max_execution_time', 30);

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

require_once 'bd_conexion.php';

$pasos = [];
$tiempos = [];

try {
    $inicio = microtime(true);
    
    $pdo = Conexion::obtenerConexion();
    $tiempos['conexion'] = round((microtime(true) - $inicio) * 1000, 2);
    $pasos[] = '1. Conexión OK';
    
    // Test 1: ¿Existe la tabla historial_turnos_caja?
    $stmt = $pdo->query("SHOW TABLES LIKE 'historial_turnos_caja'");
    $tieneHistorial = $stmt->rowCount() > 0;
    $tiempos['verificar_tabla'] = round((microtime(true) - $inicio) * 1000, 2);
    $pasos[] = "2. Tabla historial existe: " . ($tieneHistorial ? 'Sí' : 'No');
    
    if (!$tieneHistorial) {
        throw new Exception('Tabla historial_turnos_caja NO EXISTE - hay que crearla');
    }
    
    // Test 2: Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) FROM historial_turnos_caja");
    $totalRegistros = $stmt->fetchColumn();
    $tiempos['count'] = round((microtime(true) - $inicio) * 1000, 2);
    $pasos[] = "3. Total registros: {$totalRegistros}";
    
    // Test 3: Obtener últimos 5 registros (SIN joins complejos)
    $stmt = $pdo->query("
        SELECT id, numero_turno, tipo_evento, cajero_nombre, fecha_hora 
        FROM historial_turnos_caja 
        ORDER BY fecha_hora DESC 
        LIMIT 5
    ");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tiempos['select_simple'] = round((microtime(true) - $inicio) * 1000, 2);
    $pasos[] = "4. Select simple OK - " . count($registros) . " registros";
    
    // Test 4: Ver si hay joins problemáticos
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    $tieneUsuarios = $stmt->rowCount() > 0;
    $pasos[] = "5. Tabla usuarios existe: " . ($tieneUsuarios ? 'Sí' : 'No');
    
    echo json_encode([
        'success' => true,
        'diagnostico' => 'Endpoint funcional',
        'pasos_completados' => $pasos,
        'tiempos_ms' => $tiempos,
        'total_tiempo_ms' => round((microtime(true) - $inicio) * 1000, 2),
        'registros_ejemplo' => $registros,
        'problema_identificado' => $totalRegistros == 0 
            ? '⚠️ Historial vacío - ejecuta sincronizar_historial_turnos.php'
            : ($tiempos['select_simple'] > 2000 ? '⚠️ Consulta lenta' : '✅ Todo OK')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'pasos_completados' => $pasos,
        'tiempos_ms' => $tiempos,
        'tiempo_total_hasta_error' => round((microtime(true) - $inicio) * 1000, 2)
    ], JSON_PRETTY_PRINT);
}
?>

