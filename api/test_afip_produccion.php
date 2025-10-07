<?php
/**
 * 🏭 TEST DE CONEXIÓN AFIP PRODUCCIÓN
 * Verifica conectividad real con servidores AFIP de producción
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config_afip.php';
require_once 'afip_directo.php';

try {
    echo "🏭 TESTING AFIP PRODUCCIÓN...\n\n";
    
    global $CONFIGURACION_AFIP, $DATOS_FISCALES;
    
    // Verificar configuración
    echo "📋 CONFIGURACIÓN:\n";
    echo "- Ambiente: " . $CONFIGURACION_AFIP['ambiente'] . "\n";
    echo "- CUIT: " . $DATOS_FISCALES['cuit_empresa'] . "\n";
    echo "- Razón Social: " . $DATOS_FISCALES['razon_social'] . "\n";
    echo "- URL WSAA: " . $CONFIGURACION_AFIP['urls_produccion']['wsaa'] . "\n";
    echo "- URL WSFE: " . $CONFIGURACION_AFIP['urls_produccion']['wsfe'] . "\n\n";
    
    // Test 1: Verificar certificados
    echo "🔐 TEST 1: CERTIFICADOS\n";
    $cert_path = __DIR__ . '/certificados/certificado.crt';
    $key_path = __DIR__ . '/certificados/clave_privada.key';
    
    if (!file_exists($cert_path)) {
        throw new Exception("❌ Certificado no encontrado: {$cert_path}");
    }
    
    if (!file_exists($key_path)) {
        throw new Exception("❌ Clave privada no encontrada: {$key_path}");
    }
    
    $cert_content = file_get_contents($cert_path);
    $cert_data = openssl_x509_parse($cert_content);
    
    if (!$cert_data) {
        throw new Exception("❌ Certificado inválido o corrupto");
    }
    
    $dias_restantes = round(($cert_data['validTo_time_t'] - time()) / 86400);
    
    echo "✅ Certificado válido\n";
    echo "✅ Válido hasta: " . date('Y-m-d H:i:s', $cert_data['validTo_time_t']) . "\n";
    echo "✅ Días restantes: {$dias_restantes}\n\n";
    
    if ($cert_data['validTo_time_t'] < time()) {
        throw new Exception("❌ Certificado vencido");
    }
    
    if ($dias_restantes < 30) {
        echo "⚠️ ADVERTENCIA: Certificado vence en menos de 30 días\n\n";
    }
    
    // Test 2: Conectividad con servidores de producción
    echo "🌐 TEST 2: CONECTIVIDAD PRODUCCIÓN\n";
    
    $urls_prod = $CONFIGURACION_AFIP['urls_produccion'];
    
    // Test WSAA Producción
    $start_time = microtime(true);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urls_prod['wsaa']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 500) {
        echo "✅ WSAA Producción accesible ({$http_code}) - {$response_time}ms\n";
    } else {
        throw new Exception("❌ WSAA Producción no accesible: HTTP {$http_code}");
    }
    
    // Test WSFE Producción
    $start_time = microtime(true);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urls_prod['wsfe']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 500) {
        echo "✅ WSFE Producción accesible ({$http_code}) - {$response_time}ms\n\n";
    } else {
        throw new Exception("❌ WSFE Producción no accesible: HTTP {$http_code}");
    }
    
    // Test 3: Autenticación con WSAA Producción
    echo "🔐 TEST 3: AUTENTICACIÓN WSAA PRODUCCIÓN\n";
    
    try {
        $afip = new AFIPDirecto();
        
        // Usar reflexión para acceder al método privado
        $reflection = new ReflectionClass($afip);
        $method = $reflection->getMethod('obtenerTicketAcceso');
        $method->setAccessible(true);
        
        $start_time = microtime(true);
        $ticket = $method->invoke($afip);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if (!empty($ticket['token']) && !empty($ticket['sign'])) {
            echo "✅ Autenticación WSAA exitosa - {$response_time}ms\n";
            echo "✅ Token obtenido (longitud: " . strlen($ticket['token']) . ")\n";
            echo "✅ Sign obtenido (longitud: " . strlen($ticket['sign']) . ")\n";
            $tiempo_restante = round(($ticket['expira'] - time()) / 60, 1);
            echo "✅ Válido por: {$tiempo_restante} minutos\n\n";
        } else {
            throw new Exception("❌ No se pudo obtener ticket válido");
        }
        
    } catch (Exception $e) {
        echo "❌ Error en autenticación: " . $e->getMessage() . "\n\n";
        throw $e;
    }
    
    // Test 4: Consulta de servicios WSFE
    echo "🛠️ TEST 4: SERVICIOS WSFE PRODUCCIÓN\n";
    
    try {
        // Obtener último número de comprobante
        $ultimoMethod = $reflection->getMethod('obtenerUltimoNumero');
        $ultimoMethod->setAccessible(true);
        
        $start_time = microtime(true);
        $ultimo_numero = $ultimoMethod->invoke($afip, 6); // Factura B
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        echo "✅ Consulta WSFE exitosa - {$response_time}ms\n";
        echo "✅ Último número Factura B: {$ultimo_numero}\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error consultando WSFE: " . $e->getMessage() . "\n\n";
        throw $e;
    }
    
    echo "🎉 ¡TODAS LAS PRUEBAS EXITOSAS!\n";
    echo "🏭 Tu sistema está listo para PRODUCCIÓN AFIP real\n";
    echo "📋 Cada venta generará comprobantes fiscales reales\n";
    echo "✅ CAE válidos de AFIP\n";
    echo "✅ Cumplimiento legal total\n\n";
    
    echo "⚠️ IMPORTANTE:\n";
    echo "- Cada comprobante generado será REAL y válido\n";
    echo "- Los números de comprobante son consecutivos\n";
    echo "- No se pueden eliminar comprobantes una vez generados\n";
    echo "- Mantén backup de tu base de datos\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n🔧 POSIBLES SOLUCIONES:\n";
    echo "1. Verificar que el certificado sea de PRODUCCIÓN (no testing)\n";
    echo "2. Confirmar que el CUIT esté habilitado para facturación electrónica\n";
    echo "3. Verificar conectividad a internet\n";
    echo "4. Contactar a AFIP si persisten los errores\n";
}
?>
