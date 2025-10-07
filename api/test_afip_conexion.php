<?php
/**
 * 🧪 TEST DE CONEXIÓN AFIP COMPLETO
 * Verifica paso a paso la conectividad con AFIP
 * Diagnóstico completo de certificados, autenticación y servicios
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config_afip.php';
require_once 'afip_directo.php';

class AFIPDiagnostico {
    
    private $resultados = [];
    private $config;
    private $datos_fiscales;
    
    public function __construct() {
        global $CONFIGURACION_AFIP, $DATOS_FISCALES;
        $this->config = $CONFIGURACION_AFIP;
        $this->datos_fiscales = $DATOS_FISCALES;
    }
    
    /**
     * 🔍 EJECUTAR DIAGNÓSTICO COMPLETO
     */
    public function ejecutarDiagnostico() {
        $this->resultados = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ambiente' => $this->config['ambiente'],
            'tests' => []
        ];
        
        // 1. Verificar certificados
        $this->testCertificados();
        
        // 2. Verificar configuración AFIP
        $this->testConfiguracion();
        
        // 3. Test de conectividad básica
        $this->testConectividad();
        
        // 4. Test de autenticación WSAA
        $this->testAutenticacion();
        
        // 5. Test de servicios WSFE
        $this->testServiciosWSFE();
        
        // 6. Test completo de generación de comprobante
        $this->testGeneracionComprobante();
        
        return $this->resultados;
    }
    
    /**
     * 📜 TEST 1: VERIFICAR CERTIFICADOS
     */
    private function testCertificados() {
        $test = [
            'nombre' => 'Verificación de Certificados',
            'descripcion' => 'Verifica que los certificados AFIP estén presentes y sean válidos',
            'status' => 'pending',
            'detalles' => []
        ];
        
        try {
            $cert_path = __DIR__ . '/certificados/certificado.crt';
            $key_path = __DIR__ . '/certificados/clave_privada.key';
            
            // Verificar archivos existen
            if (!file_exists($cert_path)) {
                throw new Exception("Certificado no encontrado: {$cert_path}");
            }
            
            if (!file_exists($key_path)) {
                throw new Exception("Clave privada no encontrada: {$key_path}");
            }
            
            // Verificar contenido del certificado
            $cert_content = file_get_contents($cert_path);
            if (strpos($cert_content, '-----BEGIN CERTIFICATE-----') === false) {
                throw new Exception("Formato de certificado inválido");
            }
            
            // Verificar contenido de clave privada
            $key_content = file_get_contents($key_path);
            if (strpos($key_content, '-----BEGIN') === false) {
                throw new Exception("Formato de clave privada inválido");
            }
            
            // Verificar validez del certificado
            $cert_data = openssl_x509_parse($cert_content);
            if (!$cert_data) {
                throw new Exception("Certificado corrupto o inválido");
            }
            
            $test['detalles'] = [
                'certificado_existe' => true,
                'clave_existe' => true,
                'certificado_valido' => true,
                'emisor' => $cert_data['issuer']['CN'] ?? 'N/A',
                'sujeto' => $cert_data['subject']['CN'] ?? 'N/A',
                'valido_desde' => date('Y-m-d H:i:s', $cert_data['validFrom_time_t']),
                'valido_hasta' => date('Y-m-d H:i:s', $cert_data['validTo_time_t']),
                'dias_restantes' => round(($cert_data['validTo_time_t'] - time()) / 86400)
            ];
            
            if ($cert_data['validTo_time_t'] < time()) {
                throw new Exception("Certificado vencido el " . date('Y-m-d', $cert_data['validTo_time_t']));
            }
            
            if ($cert_data['validTo_time_t'] < (time() + 86400 * 30)) {
                $test['warning'] = "Certificado vence en menos de 30 días";
            }
            
            $test['status'] = 'success';
            $test['mensaje'] = "Certificados válidos y operativos";
            
        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['error'] = $e->getMessage();
        }
        
        $this->resultados['tests']['certificados'] = $test;
    }
    
    /**
     * ⚙️ TEST 2: VERIFICAR CONFIGURACIÓN
     */
    private function testConfiguracion() {
        $test = [
            'nombre' => 'Verificación de Configuración',
            'descripcion' => 'Verifica que la configuración AFIP sea correcta',
            'status' => 'pending',
            'detalles' => []
        ];
        
        try {
            // Verificar CUIT
            if (empty($this->datos_fiscales['cuit_empresa'])) {
                throw new Exception("CUIT de empresa no configurado");
            }
            
            if (!preg_match('/^\d{11}$/', $this->datos_fiscales['cuit_empresa'])) {
                throw new Exception("CUIT inválido: " . $this->datos_fiscales['cuit_empresa']);
            }
            
            // Verificar ambiente
            if (!in_array($this->config['ambiente'], ['TESTING', 'PRODUCCION'])) {
                throw new Exception("Ambiente inválido: " . $this->config['ambiente']);
            }
            
            // Verificar URLs
            $urls = ($this->config['ambiente'] === 'PRODUCCION') 
                ? $this->config['urls_produccion'] 
                : $this->config['urls_testing'];
            
            if (empty($urls['wsaa']) || empty($urls['wsfe'])) {
                throw new Exception("URLs de servicios AFIP no configuradas");
            }
            
            $test['detalles'] = [
                'cuit_empresa' => $this->datos_fiscales['cuit_empresa'],
                'razon_social' => $this->datos_fiscales['razon_social'],
                'ambiente' => $this->config['ambiente'],
                'url_wsaa' => $urls['wsaa'],
                'url_wsfe' => $urls['wsfe'],
                'punto_venta' => $this->datos_fiscales['puntos_venta']['0001']['numero'] ?? '0001'
            ];
            
            $test['status'] = 'success';
            $test['mensaje'] = "Configuración correcta";
            
        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['error'] = $e->getMessage();
        }
        
        $this->resultados['tests']['configuracion'] = $test;
    }
    
    /**
     * 🌐 TEST 3: CONECTIVIDAD BÁSICA
     */
    private function testConectividad() {
        $test = [
            'nombre' => 'Test de Conectividad',
            'descripcion' => 'Verifica conectividad básica con servidores AFIP',
            'status' => 'pending',
            'detalles' => []
        ];
        
        try {
            $urls = ($this->config['ambiente'] === 'PRODUCCION') 
                ? $this->config['urls_produccion'] 
                : $this->config['urls_testing'];
            
            $conectividad = [];
            
            // Test WSAA
            $start_time = microtime(true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urls['wsaa']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOBODY, true); // Solo HEAD request
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            curl_close($ch);
            
            $conectividad['wsaa'] = [
                'url' => $urls['wsaa'],
                'http_code' => $http_code,
                'response_time_ms' => $response_time,
                'accesible' => ($http_code > 0 && $http_code < 500)
            ];
            
            // Test WSFE
            $start_time = microtime(true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urls['wsfe']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            curl_close($ch);
            
            $conectividad['wsfe'] = [
                'url' => $urls['wsfe'],
                'http_code' => $http_code,
                'response_time_ms' => $response_time,
                'accesible' => ($http_code > 0 && $http_code < 500)
            ];
            
            $test['detalles'] = $conectividad;
            
            if ($conectividad['wsaa']['accesible'] && $conectividad['wsfe']['accesible']) {
                $test['status'] = 'success';
                $test['mensaje'] = "Conectividad exitosa con servidores AFIP";
            } else {
                throw new Exception("Problemas de conectividad detectados");
            }
            
        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['error'] = $e->getMessage();
        }
        
        $this->resultados['tests']['conectividad'] = $test;
    }
    
    /**
     * 🔐 TEST 4: AUTENTICACIÓN WSAA
     */
    private function testAutenticacion() {
        $test = [
            'nombre' => 'Test de Autenticación WSAA',
            'descripcion' => 'Verifica autenticación con WSAA usando certificados',
            'status' => 'pending',
            'detalles' => []
        ];
        
        try {
            $afip = new AFIPDirecto();
            
            // Usar reflexión para acceder al método privado
            $reflection = new ReflectionClass($afip);
            $method = $reflection->getMethod('obtenerTicketAcceso');
            $method->setAccessible(true);
            
            $start_time = microtime(true);
            $ticket = $method->invoke($afip);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $test['detalles'] = [
                'response_time_ms' => $response_time,
                'token_obtenido' => !empty($ticket['token']),
                'sign_obtenido' => !empty($ticket['sign']),
                'token_length' => strlen($ticket['token'] ?? ''),
                'expira_timestamp' => $ticket['expira'] ?? null,
                'tiempo_restante_min' => isset($ticket['expira']) ? round(($ticket['expira'] - time()) / 60, 1) : null
            ];
            
            if (!empty($ticket['token']) && !empty($ticket['sign'])) {
                $test['status'] = 'success';
                $test['mensaje'] = "Autenticación WSAA exitosa";
            } else {
                throw new Exception("No se pudo obtener ticket de acceso válido");
            }
            
        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['error'] = $e->getMessage();
        }
        
        $this->resultados['tests']['autenticacion'] = $test;
    }
    
    /**
     * 🛠️ TEST 5: SERVICIOS WSFE
     */
    private function testServiciosWSFE() {
        $test = [
            'nombre' => 'Test de Servicios WSFE',
            'descripcion' => 'Verifica acceso a servicios de facturación electrónica',
            'status' => 'pending',
            'detalles' => []
        ];
        
        try {
            $afip = new AFIPDirecto();
            
            // Obtener ticket primero
            $reflection = new ReflectionClass($afip);
            $ticketMethod = $reflection->getMethod('obtenerTicketAcceso');
            $ticketMethod->setAccessible(true);
            $ticket = $ticketMethod->invoke($afip);
            
            // Test: Obtener último número de comprobante
            $ultimoMethod = $reflection->getMethod('obtenerUltimoNumero');
            $ultimoMethod->setAccessible(true);
            
            $start_time = microtime(true);
            $ultimo_numero = $ultimoMethod->invoke($afip, 6); // Factura B
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $test['detalles'] = [
                'response_time_ms' => $response_time,
                'ultimo_numero_factura_b' => $ultimo_numero,
                'servicio_disponible' => is_numeric($ultimo_numero)
            ];
            
            if (is_numeric($ultimo_numero)) {
                $test['status'] = 'success';
                $test['mensaje'] = "Servicios WSFE operativos";
            } else {
                throw new Exception("No se pudo acceder a servicios WSFE");
            }
            
        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['error'] = $e->getMessage();
        }
        
        $this->resultados['tests']['servicios_wsfe'] = $test;
    }
    
    /**
     * 🧾 TEST 6: GENERACIÓN DE COMPROBANTE
     */
    private function testGeneracionComprobante() {
        $test = [
            'nombre' => 'Test de Generación de Comprobante',
            'descripcion' => 'Prueba completa de generación de comprobante fiscal',
            'status' => 'pending',
            'detalles' => []
        ];
        
        try {
            // Crear venta de prueba temporal
            $pdo = Conexion::obtenerConexion();
            $stmt = $pdo->prepare("
                INSERT INTO ventas (monto_total, metodo_pago, estado, fecha, cliente_nombre, detalles_json) 
                VALUES (100.00, 'efectivo', 'completado', NOW(), 'Test AFIP', '[]')
            ");
            $stmt->execute();
            $venta_test_id = $pdo->lastInsertId();
            
            // Intentar generar comprobante
            $start_time = microtime(true);
            $resultado = generarComprobanteAFIPDirecto($venta_test_id);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            // Limpiar venta de prueba
            $stmt = $pdo->prepare("DELETE FROM ventas WHERE id = ?");
            $stmt->execute([$venta_test_id]);
            
            $test['detalles'] = [
                'response_time_ms' => $response_time,
                'success' => $resultado['success'] ?? false,
                'cae_obtenido' => !empty($resultado['cae']),
                'numero_comprobante' => $resultado['numero_comprobante'] ?? null,
                'tipo_comprobante' => $resultado['tipo_comprobante'] ?? null,
                'error' => $resultado['error'] ?? null
            ];
            
            if ($resultado['success']) {
                $test['status'] = 'success';
                $test['mensaje'] = "Generación de comprobante exitosa";
            } else {
                $test['status'] = 'warning';
                $test['mensaje'] = "Comprobante con fallback - " . ($resultado['error'] ?? 'Error desconocido');
            }
            
        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['error'] = $e->getMessage();
        }
        
        $this->resultados['tests']['generacion_comprobante'] = $test;
    }
}

// Ejecutar diagnóstico
try {
    $diagnostico = new AFIPDiagnostico();
    $resultados = $diagnostico->ejecutarDiagnostico();
    
    // Calcular resumen
    $total_tests = count($resultados['tests']);
    $success_count = 0;
    $warning_count = 0;
    $error_count = 0;
    
    foreach ($resultados['tests'] as $test) {
        switch ($test['status']) {
            case 'success':
                $success_count++;
                break;
            case 'warning':
                $warning_count++;
                break;
            case 'error':
                $error_count++;
                break;
        }
    }
    
    $resultados['resumen'] = [
        'total_tests' => $total_tests,
        'exitosos' => $success_count,
        'warnings' => $warning_count,
        'errores' => $error_count,
        'porcentaje_exito' => round(($success_count / $total_tests) * 100, 1),
        'estado_general' => ($error_count === 0) ? (($warning_count === 0) ? 'PERFECTO' : 'FUNCIONAL') : 'PROBLEMAS'
    ];
    
    echo json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error ejecutando diagnóstico: ' . $e->getMessage()
    ]);
}
?>
