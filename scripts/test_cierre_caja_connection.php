<?php
/**
 * 🧪 TEST DE CONEXIÓN PARA CIERRE DE CAJA
 * Diagnostica problemas de conexión y valida el endpoint
 */

echo "🔧 DIAGNOSTICANDO PROBLEMA DE CIERRE DE CAJA\n";
echo "=" . str_repeat("=", 60) . "\n";

// Test 1: Verificar que el archivo existe
echo "📂 TEST 1: Verificando archivos...\n";
$archivo_gestion = __DIR__ . '/../api/gestion_caja_completa.php';
if (file_exists($archivo_gestion)) {
    echo "   ✅ gestion_caja_completa.php existe\n";
} else {
    echo "   ❌ gestion_caja_completa.php NO ENCONTRADO\n";
    exit(1);
}

// Test 2: Verificar conexión a base de datos
echo "\n🗄️ TEST 2: Verificando conexión BD...\n";
try {
    require_once __DIR__ . '/../api/bd_conexion.php';
    $pdo = Conexion::obtenerConexion();
    echo "   ✅ Conexión BD exitosa\n";
} catch (Exception $e) {
    echo "   ❌ Error BD: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verificar turnos activos
echo "\n💼 TEST 3: Verificando turnos activos...\n";
try {
    $stmt = $pdo->query("SELECT * FROM turnos_caja WHERE estado = 'abierto' LIMIT 5");
    $turnos = $stmt->fetchAll();
    echo "   📊 Turnos abiertos: " . count($turnos) . "\n";
    
    if (count($turnos) > 0) {
        foreach ($turnos as $turno) {
            echo "      • Turno ID: {$turno['id']}, Usuario: {$turno['usuario_id']}, Apertura: {$turno['monto_apertura']}\n";
        }
    } else {
        echo "   ⚠️  No hay turnos abiertos actualmente\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error consultando turnos: " . $e->getMessage() . "\n";
}

// Test 4: Simular llamada de cierre
echo "\n🔒 TEST 4: Simulando cierre de caja...\n";
if (count($turnos) > 0) {
    $turno = $turnos[0];
    
    try {
        // Simular los datos que envía el frontend
        $datos_cierre = [
            'usuario_id' => (int)$turno['usuario_id'],
            'monto_cierre' => 13000.00,
            'notas' => 'Test cierre automático - ' . date('Y-m-d H:i:s')
        ];
        
        echo "   📝 Datos de prueba:\n";
        echo "      • Usuario ID: {$datos_cierre['usuario_id']}\n";
        echo "      • Monto cierre: {$datos_cierre['monto_cierre']}\n";
        echo "      • Notas: {$datos_cierre['notas']}\n";
        
        // Simular la función de cierre
        $stmt = $pdo->prepare("
            UPDATE turnos_caja SET
                fecha_cierre = NOW(),
                monto_cierre = ?,
                diferencia = (? - efectivo_teorico),
                estado = 'cerrado',
                notas = CONCAT(COALESCE(notas, ''), ?)
            WHERE id = ? AND estado = 'abierto'
        ");
        
        $notas_cierre = "\n[TEST] " . date('Y-m-d H:i:s') . ": " . $datos_cierre['notas'];
        $result = $stmt->execute([
            $datos_cierre['monto_cierre'],
            $datos_cierre['monto_cierre'],
            $notas_cierre,
            $turno['id']
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "   ✅ Cierre simulado exitosamente\n";
            echo "   📊 Filas afectadas: " . $stmt->rowCount() . "\n";
            
            // Revertir el cambio
            $stmt_revert = $pdo->prepare("
                UPDATE turnos_caja SET
                    fecha_cierre = NULL,
                    monto_cierre = NULL,
                    diferencia = NULL,
                    estado = 'abierto',
                    notas = REPLACE(notas, ?, '')
                WHERE id = ?
            ");
            $stmt_revert->execute([$notas_cierre, $turno['id']]);
            echo "   🔄 Estado revertido para continuar testing\n";
            
        } else {
            echo "   ❌ Fallo en cierre simulado\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error en cierre simulado: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠️  No se puede simular cierre: no hay turnos abiertos\n";
}

// Test 5: Verificar acceso HTTP
echo "\n🌐 TEST 5: Verificando acceso HTTP...\n";
$urls_test = [
    'http://localhost/kiosco/api/gestion_caja_completa.php?accion=validar_turno_unico',
    'http://127.0.0.1/kiosco/api/gestion_caja_completa.php?accion=validar_turno_unico'
];

foreach ($urls_test as $url) {
    echo "   🔗 Probando: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'header' => 'Accept: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && !isset($data['error'])) {
            echo "      ✅ Acceso HTTP exitoso\n";
        } else {
            echo "      ⚠️  Respuesta con errores: " . ($data['error'] ?? 'Desconocido') . "\n";
        }
    } else {
        echo "      ❌ Error de conexión HTTP\n";
    }
}

// Test 6: Generar URLs correctas para frontend
echo "\n📱 TEST 6: URLs para frontend...\n";
$base_urls = [
    'http://localhost/kiosco',
    'http://127.0.0.1/kiosco',
    'http://localhost:3000' // React dev server
];

foreach ($base_urls as $base) {
    $full_url = $base . '/api/gestion_caja_completa.php?accion=cerrar_caja';
    echo "   🔗 Frontend debería usar: $full_url\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 RECOMENDACIONES:\n";
echo "   1. Verificar que React apunte a 'http://localhost/kiosco'\n";
echo "   2. Asegurarse de que Laragon esté ejecutándose\n";
echo "   3. Verificar configuración CORS en PHP\n";
echo "   4. Comprobar que no hay proxies bloqueando la conexión\n";
echo "   5. Usar herramientas de desarrollo del navegador para ver errores\n";

echo "\n🔧 SOLUCIÓN INMEDIATA:\n";
echo "   • Modifique CONFIG.API_URL en src/config/config.js\n";
echo "   • Asegúrese de que apunte a 'http://localhost/kiosco'\n";
echo "   • Reinicie el servidor de React (npm start)\n";

echo str_repeat("=", 60) . "\n";
?>























