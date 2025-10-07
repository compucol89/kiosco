<?php
/**
 * 🔬 DIAGNÓSTICO PROFUNDO AFIP
 * Investigación exhaustiva de problemas de conexión
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'config_afip.php';

class DiagnosticoProfundoAFIP {
    
    private $config;
    private $datos_fiscales;
    
    public function __construct() {
        global $CONFIGURACION_AFIP, $DATOS_FISCALES;
        $this->config = $CONFIGURACION_AFIP;
        $this->datos_fiscales = $DATOS_FISCALES;
    }
    
    public function ejecutarDiagnostico() {
        echo "🔬 DIAGNÓSTICO PROFUNDO AFIP\n";
        echo "==================================\n\n";
        
        $this->verificarCertificadoDetallado();
        $this->probarFirmadoTRA();
        $this->probarWSAADirecto();
        $this->verificarCUITEnAFIP();
    }
    
    /**
     * 📜 VERIFICAR CERTIFICADO EN DETALLE
     */
    private function verificarCertificadoDetallado() {
        echo "📜 ANÁLISIS DETALLADO DEL CERTIFICADO:\n";
        echo "=====================================\n";
        
        $cert_path = __DIR__ . '/certificados/cert.pem';
        $key_path = __DIR__ . '/certificados/clave.key';
        
        if (!file_exists($cert_path)) {
            echo "❌ Certificado no encontrado: {$cert_path}\n";
            return;
        }
        
        if (!file_exists($key_path)) {
            echo "❌ Clave privada no encontrada: {$key_path}\n";
            return;
        }
        
        // Analizar certificado
        $cert_content = file_get_contents($cert_path);
        $cert_data = openssl_x509_parse($cert_content);
        
        if (!$cert_data) {
            echo "❌ Error parseando certificado\n";
            return;
        }
        
        echo "✅ Certificado parseado correctamente\n";
        echo "📋 Detalles del certificado:\n";
        echo "- Emisor: " . ($cert_data['issuer']['CN'] ?? 'N/A') . "\n";
        echo "- Sujeto: " . ($cert_data['subject']['CN'] ?? 'N/A') . "\n";
        echo "- Serial: " . ($cert_data['serialNumber'] ?? 'N/A') . "\n";
        echo "- Algoritmo: " . ($cert_data['signatureTypeSN'] ?? 'N/A') . "\n";
        echo "- Válido desde: " . date('Y-m-d H:i:s', $cert_data['validFrom_time_t']) . "\n";
        echo "- Válido hasta: " . date('Y-m-d H:i:s', $cert_data['validTo_time_t']) . "\n";
        
        $dias_restantes = round(($cert_data['validTo_time_t'] - time()) / 86400);
        echo "- Días restantes: {$dias_restantes}\n";
        
        // Verificar si el CUIT está en el certificado
        $cert_text = $cert_content;
        if (strpos($cert_text, $this->datos_fiscales['cuit_empresa']) !== false) {
            echo "✅ CUIT encontrado en el certificado\n";
        } else {
            echo "⚠️ CUIT NO encontrado en el certificado\n";
        }
        
        // Verificar clave privada
        $key_content = file_get_contents($key_path);
        $private_key = openssl_pkey_get_private($key_content);
        
        if ($private_key) {
            echo "✅ Clave privada válida\n";
            
            // Verificar que la clave corresponda al certificado
            if (openssl_x509_check_private_key($cert_content, $private_key)) {
                echo "✅ Clave privada corresponde al certificado\n";
            } else {
                echo "❌ Clave privada NO corresponde al certificado\n";
            }
            
            openssl_pkey_free($private_key);
        } else {
            echo "❌ Clave privada inválida\n";
        }
        
        echo "\n";
    }
    
    /**
     * ✍️ PROBAR FIRMADO DE TRA
     */
    private function probarFirmadoTRA() {
        echo "✍️ TEST DE FIRMADO TRA:\n";
        echo "======================\n";
        
        try {
            $cert_path = __DIR__ . '/certificados/cert.pem';
            $key_path = __DIR__ . '/certificados/clave.key';
            
            $cert_content = file_get_contents($cert_path);
            $key_content = file_get_contents($key_path);
            
            // Crear TRA simple para testing
            $tra = '<?xml version="1.0" encoding="UTF-8"?>
            <loginTicketRequest version="1.0">
                <header>
                    <uniqueId>' . time() . '</uniqueId>
                    <generationTime>' . date('c', time() - 60) . '</generationTime>
                    <expirationTime>' . date('c', time() + 60) . '</expirationTime>
                </header>
                <service>wsfe</service>
            </loginTicketRequest>';
            
            echo "📝 TRA creado correctamente\n";
            
            // Intentar firmar
            $tra_file = tempnam(sys_get_temp_dir(), 'tra_');
            file_put_contents($tra_file, $tra);
            
            $cms_file = tempnam(sys_get_temp_dir(), 'cms_');
            
            $result = openssl_pkcs7_sign(
                $tra_file,
                $cms_file,
                $cert_content,
                $key_content,
                [],
                PKCS7_BINARY | PKCS7_NOATTR
            );
            
            if ($result) {
                echo "✅ TRA firmado exitosamente\n";
                
                $cms = file_get_contents($cms_file);
                if ($cms && strlen($cms) > 100) {
                    echo "✅ CMS generado correctamente (tamaño: " . strlen($cms) . " bytes)\n";
                } else {
                    echo "⚠️ CMS muy pequeño o vacío\n";
                }
            } else {
                echo "❌ Error firmando TRA: " . openssl_error_string() . "\n";
            }
            
            // Limpiar
            if (file_exists($tra_file)) unlink($tra_file);
            if (file_exists($cms_file)) unlink($cms_file);
            
        } catch (Exception $e) {
            echo "❌ Error en test de firmado: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * 🌐 PROBAR WSAA DIRECTO
     */
    private function probarWSAADirecto() {
        echo "🌐 TEST WSAA DIRECTO:\n";
        echo "====================\n";
        
        try {
            $wsaa_url = $this->config['urls_produccion']['wsaa'];
            
            // Crear request SOAP simple para test
            $soap_test = '<?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                <soap:Header/>
                <soap:Body>
                    <dummy>test</dummy>
                </soap:Body>
            </soap:Envelope>';
            
            echo "📡 Enviando request de prueba a WSAA...\n";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $wsaa_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_test);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/soap+xml; charset=utf-8',
                'Content-Length: ' . strlen($soap_test)
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            
            $verbose_output = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose_output);
            
            $start_time = microtime(true);
            $response = curl_exec($ch);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            rewind($verbose_output);
            $verbose_info = stream_get_contents($verbose_output);
            fclose($verbose_output);
            
            curl_close($ch);
            
            echo "📊 Resultados:\n";
            echo "- HTTP Code: {$http_code}\n";
            echo "- Tiempo respuesta: {$response_time}ms\n";
            echo "- Error cURL: " . ($curl_error ?: 'Ninguno') . "\n";
            echo "- Tamaño respuesta: " . strlen($response) . " bytes\n";
            
            if ($http_code === 500) {
                echo "\n🔍 Análisis HTTP 500:\n";
                if (strpos($response, 'soap:Fault') !== false) {
                    echo "- Tipo: Error SOAP (servidor procesó request pero hay error)\n";
                    echo "- Posible causa: Formato de request incorrecto\n";
                } else {
                    echo "- Tipo: Error interno del servidor\n";
                    echo "- Posible causa: Problema de configuración AFIP\n";
                }
            }
            
            if (strlen($response) > 0 && strlen($response) < 1000) {
                echo "\n📄 Respuesta del servidor:\n";
                echo substr($response, 0, 500) . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error en test WSAA: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * 🆔 VERIFICAR CUIT EN AFIP
     */
    private function verificarCUITEnAFIP() {
        echo "🆔 VERIFICACIÓN CUIT EN AFIP:\n";
        echo "=============================\n";
        
        $cuit = $this->datos_fiscales['cuit_empresa'];
        
        echo "🔍 Verificando CUIT: {$cuit}\n";
        
        // Verificar formato CUIT
        if (!preg_match('/^\d{11}$/', $cuit)) {
            echo "❌ Formato CUIT inválido\n";
            return;
        }
        
        echo "✅ Formato CUIT correcto\n";
        
        // Verificar dígito verificador
        $digitos = str_split($cuit);
        $multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $suma += (int)$digitos[$i] * $multiplicadores[$i];
        }
        
        $resto = $suma % 11;
        $dv_calculado = ($resto < 2) ? $resto : 11 - $resto;
        $dv_real = (int)$digitos[10];
        
        if ($dv_calculado === $dv_real) {
            echo "✅ Dígito verificador correcto\n";
        } else {
            echo "❌ Dígito verificador incorrecto (esperado: {$dv_calculado}, actual: {$dv_real})\n";
        }
        
        echo "\n📋 RECOMENDACIONES:\n";
        echo "1. Verificar en AFIP que el CUIT {$cuit} esté:\n";
        echo "   - Habilitado para facturación electrónica\n";
        echo "   - Con punto de venta 3 dado de alta\n";
        echo "   - En estado activo\n";
        echo "2. Confirmar que el certificado sea específico para este CUIT\n";
        echo "3. Verificar que el certificado sea de PRODUCCIÓN (no testing)\n";
        
        echo "\n";
    }
}

// Ejecutar diagnóstico
try {
    $diagnostico = new DiagnosticoProfundoAFIP();
    $diagnostico->ejecutarDiagnostico();
    
    echo "🎯 CONCLUSIÓN FINAL:\n";
    echo "===================\n";
    echo "Si todos los tests anteriores son exitosos pero AFIP sigue fallando,\n";
    echo "el problema está en la habilitación del CUIT en AFIP.\n\n";
    echo "💡 SOLUCIÓN ALTERNATIVA:\n";
    echo "Usar el token de AFIP SDK que mencionaste.\n";
    echo "Esto evita problemas de certificados y conecta directamente.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
}
?>
