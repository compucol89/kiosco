<?php
/**
 * 🏛️ AFIP DIRECTO - SIN SDK EXTERNO
 * Integración directa con Web Services AFIP
 * Más rápido, confiable y sin dependencias
 * 
 * RELEVANT FILES: config_afip.php, procesar_venta_ultra_rapida.php
 */

require_once 'config_afip.php';
require_once 'bd_conexion.php';

class AFIPDirecto {
    
    private $config;
    private $ambiente;
    private $datos_fiscales;
    private $ticket_acceso = null;
    
    public function __construct() {
        global $CONFIGURACION_AFIP, $DATOS_FISCALES;
        
        $this->config = $CONFIGURACION_AFIP;
        $this->datos_fiscales = $DATOS_FISCALES;
        $this->ambiente = $this->config['ambiente'];
        
        // Log de inicialización
        error_log("[AFIP_DIRECTO] Inicializando en ambiente: " . $this->ambiente);
    }
    
    /**
     * 🔐 OBTENER TICKET DE ACCESO (TA) DE AFIP
     */
    private function obtenerTicketAcceso() {
        if ($this->ticket_acceso && $this->ticket_acceso['expira'] > time()) {
            return $this->ticket_acceso;
        }
        
        try {
            // 1. Crear TRA (Ticket de Requerimiento de Acceso)
            $tra = $this->crearTRA();
            
            // 2. Firmar TRA con certificado
            $cms = $this->firmarTRA($tra);
            
            // 3. Enviar a WSAA para obtener TA
            $ta = $this->enviarWSAA($cms);
            
            // 4. Guardar ticket para reutilizar
            $this->ticket_acceso = [
                'token' => $ta['token'],
                'sign' => $ta['sign'],
                'expira' => strtotime($ta['expirationTime']) - 300 // 5 min antes
            ];
            
            error_log("[AFIP_DIRECTO] Ticket de acceso obtenido exitosamente");
            return $this->ticket_acceso;
            
        } catch (Exception $e) {
            error_log("[AFIP_DIRECTO] Error obteniendo ticket: " . $e->getMessage());
            throw new Exception("Error de autenticación AFIP: " . $e->getMessage());
        }
    }
    
    /**
     * 📝 CREAR TRA (TICKET DE REQUERIMIENTO DE ACCESO)
     */
    private function crearTRA() {
        $uniqueId = date('U');
        $generationTime = date('c', $uniqueId - 60);
        $expirationTime = date('c', $uniqueId + 60);
        
        $tra = '<?xml version="1.0" encoding="UTF-8"?>
        <loginTicketRequest version="1.0">
            <header>
                <uniqueId>' . $uniqueId . '</uniqueId>
                <generationTime>' . $generationTime . '</generationTime>
                <expirationTime>' . $expirationTime . '</expirationTime>
            </header>
            <service>wsfe</service>
        </loginTicketRequest>';
        
        return $tra;
    }
    
    /**
     * ✍️ FIRMAR TRA CON CERTIFICADO (PHP NATIVO)
     */
    private function firmarTRA($tra) {
        $cert_path = __DIR__ . '/certificados/cert.pem';
        $key_path = __DIR__ . '/certificados/clave.key';
        
        if (!file_exists($cert_path) || !file_exists($key_path)) {
            throw new Exception("Certificados AFIP no encontrados en: " . dirname($cert_path));
        }
        
        // Leer certificado y clave privada
        $cert_content = file_get_contents($cert_path);
        $key_content = file_get_contents($key_path);
        
        if (!$cert_content || !$key_content) {
            throw new Exception("Error leyendo certificados AFIP");
        }
        
        // Crear archivo temporal para TRA
        $tra_file = tempnam(sys_get_temp_dir(), 'tra_');
        file_put_contents($tra_file, $tra);
        
        // Crear archivo temporal para CMS firmado
        $cms_file = tempnam(sys_get_temp_dir(), 'cms_');
        
        // Firmar con funciones PHP nativas
        $result = openssl_pkcs7_sign(
            $tra_file,          // archivo a firmar
            $cms_file,          // archivo de salida
            $cert_content,      // certificado
            $key_content,       // clave privada
            [],                 // headers adicionales
            PKCS7_BINARY | PKCS7_NOATTR  // flags
        );
        
        if (!$result) {
            // Limpiar archivos temporales en caso de error
            if (file_exists($tra_file)) unlink($tra_file);
            if (file_exists($cms_file)) unlink($cms_file);
            throw new Exception("Error firmando TRA: " . openssl_error_string());
        }
        
        // Leer el CMS firmado
        $cms = file_get_contents($cms_file);
        
        if (!$cms) {
            throw new Exception("Error leyendo CMS firmado");
        }
        
        // Limpiar archivos temporales
        unlink($tra_file);
        unlink($cms_file);
        
        // Convertir a DER format (binario)
        $cms_der = $this->convertirPEMaDER($cms);
        
        return base64_encode($cms_der);
    }
    
    /**
     * 🔄 CONVERTIR CMS DE PEM A DER
     */
    private function convertirPEMaDER($cms_pem) {
        // Extraer solo el contenido base64 del PEM
        $lines = explode("\n", $cms_pem);
        $base64_content = '';
        $in_content = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '-----BEGIN') !== false) {
                $in_content = true;
                continue;
            }
            if (strpos($line, '-----END') !== false) {
                break;
            }
            if ($in_content && !empty($line)) {
                $base64_content .= $line;
            }
        }
        
        // Decodificar base64 para obtener DER
        return base64_decode($base64_content);
    }
    
    /**
     * 🌐 ENVIAR CMS A WSAA PARA OBTENER TA
     */
    private function enviarWSAA($cms) {
        $wsaa_url = ($this->ambiente === 'PRODUCCION') 
            ? $this->config['urls_produccion']['wsaa']
            : $this->config['urls_testing']['wsaa'];
        
        $soap_request = '<?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:wsaa="https://wsaahomo.afip.gov.ar/ws/services/LoginCms">
            <soap:Header/>
            <soap:Body>
                <wsaa:loginCms>
                    <wsaa:in0>' . $cms . '</wsaa:in0>
                </wsaa:loginCms>
            </soap:Body>
        </soap:Envelope>';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wsaa_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/soap+xml; charset=utf-8',
            'Content-Length: ' . strlen($soap_request)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("Error HTTP {$http_code} en WSAA");
        }
        
        // Parsear respuesta XML
        $xml = simplexml_load_string($response);
        $xml->registerXPathNamespace('a', 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms');
        
        $loginCmsReturn = $xml->xpath('//a:loginCmsReturn');
        if (empty($loginCmsReturn)) {
            throw new Exception("Respuesta WSAA inválida");
        }
        
        $credentials = simplexml_load_string($loginCmsReturn[0]);
        
        return [
            'token' => (string)$credentials->credentials->token,
            'sign' => (string)$credentials->credentials->sign,
            'expirationTime' => (string)$credentials->header->expirationTime
        ];
    }
    
    /**
     * 🧾 GENERAR COMPROBANTE FISCAL
     */
    public function generarComprobante($venta_id) {
        try {
            // 1. Obtener ticket de acceso
            $ticket = $this->obtenerTicketAcceso();
            
            // 2. Obtener datos de la venta
            $venta = $this->obtenerDatosVenta($venta_id);
            
            // 3. Determinar tipo de comprobante
            $tipo_cbte = $this->determinarTipoComprobante($venta['monto_total']);
            
            // 4. Obtener último número de comprobante
            $ultimo_numero = $this->obtenerUltimoNumero($tipo_cbte);
            
            // 5. Solicitar CAE
            $cae_data = $this->solicitarCAE($ticket, $venta, $tipo_cbte, $ultimo_numero + 1);
            
            // 6. Guardar comprobante en BD
            $this->guardarComprobante($venta_id, $cae_data);
            
            error_log("[AFIP_DIRECTO] Comprobante generado: CAE {$cae_data['cae']} para venta {$venta_id}");
            
            return [
                'success' => true,
                'cae' => $cae_data['cae'],
                'numero_comprobante' => $cae_data['numero_comprobante'],
                'fecha_vencimiento' => $cae_data['fecha_vencimiento'],
                'tipo_comprobante' => $tipo_cbte
            ];
            
        } catch (Exception $e) {
            error_log("[AFIP_DIRECTO] Error generando comprobante: " . $e->getMessage());
            
            // Fallback: CAE simulado
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cae_simulado' => $this->generarCAESimulado(),
                'numero_comprobante' => 'SIM-' . date('YmdHis') . '-' . $venta_id
            ];
        }
    }
    
    /**
     * 📊 OBTENER DATOS DE VENTA
     */
    private function obtenerDatosVenta($venta_id) {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("
            SELECT * FROM ventas 
            WHERE id = ? 
            LIMIT 1
        ");
        $stmt->execute([$venta_id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venta) {
            throw new Exception("Venta {$venta_id} no encontrada");
        }
        
        return $venta;
    }
    
    /**
     * 🎯 DETERMINAR TIPO DE COMPROBANTE
     */
    private function determinarTipoComprobante($monto) {
        // Lógica simple: Factura B para todo
        // Puedes personalizar según tus necesidades
        if ($monto <= 1000) {
            return 83; // Ticket Fiscal
        } else {
            return 6;  // Factura B
        }
    }
    
    /**
     * 🔢 OBTENER ÚLTIMO NÚMERO DE COMPROBANTE
     */
    private function obtenerUltimoNumero($tipo_cbte) {
        $ticket = $this->ticket_acceso;
        $wsfe_url = ($this->ambiente === 'PRODUCCION') 
            ? $this->config['urls_produccion']['wsfe']
            : $this->config['urls_testing']['wsfe'];
        
        $soap_request = '<?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ar="http://ar.gov.afip.dif.FEV1/">
            <soap:Header/>
            <soap:Body>
                <ar:FECompUltimoAutorizado>
                    <ar:Auth>
                        <ar:Token>' . $ticket['token'] . '</ar:Token>
                        <ar:Sign>' . $ticket['sign'] . '</ar:Sign>
                        <ar:Cuit>' . $this->datos_fiscales['cuit_empresa'] . '</ar:Cuit>
                    </ar:Auth>
                    <ar:PtoVta>3</ar:PtoVta>
                    <ar:CbteTipo>' . $tipo_cbte . '</ar:CbteTipo>
                </ar:FECompUltimoAutorizado>
            </soap:Body>
        </soap:Envelope>';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wsfe_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ""'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Parsear respuesta para obtener último número
        $xml = simplexml_load_string($response);
        $xml->registerXPathNamespace('a', 'http://ar.gov.afip.dif.FEV1/');
        
        $cbte_nro = $xml->xpath('//a:CbteNro');
        return !empty($cbte_nro) ? (int)$cbte_nro[0] : 0;
    }
    
    /**
     * 📋 SOLICITAR CAE
     */
    private function solicitarCAE($ticket, $venta, $tipo_cbte, $numero_cbte) {
        $wsfe_url = ($this->ambiente === 'PRODUCCION') 
            ? $this->config['urls_produccion']['wsfe']
            : $this->config['urls_testing']['wsfe'];
        
        $fecha_cbte = date('Ymd');
        $imp_total = number_format($venta['monto_total'], 2, '.', '');
        $imp_neto = number_format($venta['monto_total'] / 1.21, 2, '.', '');
        $imp_iva = number_format($venta['monto_total'] - $imp_neto, 2, '.', '');
        
        $soap_request = '<?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ar="http://ar.gov.afip.dif.FEV1/">
            <soap:Header/>
            <soap:Body>
                <ar:FECAESolicitar>
                    <ar:Auth>
                        <ar:Token>' . $ticket['token'] . '</ar:Token>
                        <ar:Sign>' . $ticket['sign'] . '</ar:Sign>
                        <ar:Cuit>' . $this->datos_fiscales['cuit_empresa'] . '</ar:Cuit>
                    </ar:Auth>
                    <ar:FeCAEReq>
                        <ar:FeCabReq>
                            <ar:CantReg>1</ar:CantReg>
                            <ar:PtoVta>3</ar:PtoVta>
                            <ar:CbteTipo>' . $tipo_cbte . '</ar:CbteTipo>
                        </ar:FeCabReq>
                        <ar:FeDetReq>
                            <ar:FECAEDetRequest>
                                <ar:Concepto>1</ar:Concepto>
                                <ar:DocTipo>99</ar:DocTipo>
                                <ar:DocNro>0</ar:DocNro>
                                <ar:CbteDesde>' . $numero_cbte . '</ar:CbteDesde>
                                <ar:CbteHasta>' . $numero_cbte . '</ar:CbteHasta>
                                <ar:CbteFch>' . $fecha_cbte . '</ar:CbteFch>
                                <ar:ImpTotal>' . $imp_total . '</ar:ImpTotal>
                                <ar:ImpTotConc>0.00</ar:ImpTotConc>
                                <ar:ImpNeto>' . $imp_neto . '</ar:ImpNeto>
                                <ar:ImpOpEx>0.00</ar:ImpOpEx>
                                <ar:ImpIVA>' . $imp_iva . '</ar:ImpIVA>
                                <ar:ImpTrib>0.00</ar:ImpTrib>
                                <ar:MonId>PES</ar:MonId>
                                <ar:MonCotiz>1</ar:MonCotiz>
                                <ar:Iva>
                                    <ar:AlicIva>
                                        <ar:Id>5</ar:Id>
                                        <ar:BaseImp>' . $imp_neto . '</ar:BaseImp>
                                        <ar:Importe>' . $imp_iva . '</ar:Importe>
                                    </ar:AlicIva>
                                </ar:Iva>
                            </ar:FECAEDetRequest>
                        </ar:FeDetReq>
                    </ar:FeCAEReq>
                </ar:FECAESolicitar>
            </soap:Body>
        </soap:Envelope>';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wsfe_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ""'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Parsear respuesta para obtener CAE
        $xml = simplexml_load_string($response);
        $xml->registerXPathNamespace('a', 'http://ar.gov.afip.dif.FEV1/');
        
        $cae = $xml->xpath('//a:CAE');
        $fecha_vto = $xml->xpath('//a:CAEFchVto');
        
        if (empty($cae)) {
            throw new Exception("No se pudo obtener CAE de AFIP");
        }
        
        return [
            'cae' => (string)$cae[0],
            'numero_comprobante' => $numero_cbte,
            'fecha_vencimiento' => !empty($fecha_vto) ? (string)$fecha_vto[0] : date('Ymd', strtotime('+10 days')),
            'tipo_comprobante' => $tipo_cbte
        ];
    }
    
    /**
     * 💾 GUARDAR COMPROBANTE EN BD
     */
    private function guardarComprobante($venta_id, $cae_data) {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("
            UPDATE ventas SET 
                cae = ?,
                numero_comprobante_fiscal = ?,
                fecha_vencimiento_cae = ?,
                estado_afip = 'APROBADO'
            WHERE id = ?
        ");
        
        $stmt->execute([
            $cae_data['cae'],
            $cae_data['numero_comprobante'],
            $cae_data['fecha_vencimiento'],
            $venta_id
        ]);
    }
    
    /**
     * 🎲 GENERAR CAE SIMULADO PARA FALLBACK
     */
    private function generarCAESimulado() {
        return '1234567890123' . rand(0, 9);
    }
}

/**
 * 🚀 FUNCIÓN PRINCIPAL PARA USAR EN EL SISTEMA
 */
function generarComprobanteAFIPDirecto($venta_id) {
    try {
        $afip = new AFIPDirecto();
        return $afip->generarComprobante($venta_id);
    } catch (Exception $e) {
        error_log("[AFIP_DIRECTO] Error en función principal: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

?>
