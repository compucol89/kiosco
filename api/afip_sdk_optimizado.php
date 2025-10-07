<?php
/**
 * 🚀 AFIP SDK OPTIMIZADO
 * Usa la librería oficial de AFIP SDK para facturación real
 * MONOTRIBUTO - Factura C sin discriminación de IVA
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config_afip.php';
require_once 'bd_conexion.php';

use Afipsdk\Afip;

class AFIPSDKOptimizado {
    
    private $afip;
    private $cuit;
    
    public function __construct() {
        global $DATOS_FISCALES;
        
        $this->cuit = $DATOS_FISCALES['cuit_empresa'];
        
        // Leer certificados
        $cert = file_get_contents(__DIR__ . '/certificados/afip.crt');
        $key = file_get_contents(__DIR__ . '/certificados/afip.key');
        
        // Inicializar AFIP SDK
        $this->afip = new Afip([
            'CUIT' => intval($this->cuit),
            'cert' => $cert,
            'key' => $key,
            'access_token' => 'ZaGIyLvwsMSiwPrwTFkA2oU2ktoDqjuQ9Gt6QN26quHvQrdcKJzc76L6ch9Wu4uW',
            'production' => true // ✅ PRODUCCIÓN
        ]);
        
        error_log("[AFIP_SDK] Inicializado con librería oficial - CUIT: {$this->cuit} - PRODUCCIÓN");
    }
    
    /**
     * 🧾 GENERAR COMPROBANTE FISCAL
     */
    public function generarComprobante($venta_id) {
        try {
            // 1. Obtener datos de la venta
            $venta = $this->obtenerDatosVenta($venta_id);
            
            // 2. Determinar tipo de comprobante (MONOTRIBUTO = Factura C)
            $tipo_comprobante = 11; // 11 = Factura C
            $punto_venta = 3;
            
            // 3. Obtener último número de comprobante
            $ultimo_comprobante = $this->afip->ElectronicBilling->GetLastVoucher($punto_venta, $tipo_comprobante);
            $numero_comprobante = $ultimo_comprobante + 1;
            
            error_log("[AFIP_SDK] Último comprobante: $ultimo_comprobante - Nuevo: $numero_comprobante");
            
            // 4. Preparar datos del comprobante (MONOTRIBUTO - Sin IVA)
            $fecha = date('Ymd');
            
            $data = [
                'CantReg' => 1,
                'PtoVta' => $punto_venta,
                'CbteTipo' => $tipo_comprobante,
                'Concepto' => 1, // Productos
                'DocTipo' => 99, // Consumidor Final
                'DocNro' => 0,
                'CbteDesde' => $numero_comprobante,
                'CbteHasta' => $numero_comprobante,
                'CbteFch' => $fecha,
                'ImpTotal' => floatval($venta['monto_total']),
                'ImpTotConc' => 0,
                'ImpNeto' => floatval($venta['monto_total']),
                'ImpOpEx' => 0,
                'ImpIVA' => 0, // MONOTRIBUTO: Sin IVA
                'ImpTrib' => 0,
                'MonId' => 'PES',
                'MonCotiz' => 1
            ];
            
            // 5. Solicitar CAE a AFIP
            $response = $this->afip->ElectronicBilling->CreateVoucher($data);
            
            // 6. Procesar respuesta de AFIP
            if ($response && isset($response['CAE'])) {
                $cae = $response['CAE'];
                $cae_vencimiento = $response['CAEFchVto'];
                $comprobante_numero = str_pad($punto_venta, 5, '0', STR_PAD_LEFT) . '-' . str_pad($numero_comprobante, 8, '0', STR_PAD_LEFT);
                
                // Guardar en BD
                $pdo = Conexion::obtenerConexion();
                $stmt = $pdo->prepare("UPDATE ventas SET cae = ?, comprobante_fiscal = ?, tipo_comprobante = 'FACTURA_C' WHERE id = ?");
                $stmt->execute([$cae, $comprobante_numero, $venta_id]);
                
                error_log("[AFIP_SDK] ✅ CAE REAL generado - CAE: {$cae} - Venta: {$venta_id}");
                
                return [
                    'success' => true,
                    'cae' => $cae,
                    'numero_comprobante' => $comprobante_numero,
                    'fecha_vencimiento' => $cae_vencimiento,
                    'tipo_comprobante' => 'FACTURA_C',
                    'metodo' => 'AFIP_SDK_REAL'
                ];
            } else {
                $error_msg = isset($response['Errors']) ? json_encode($response['Errors']) : 'Respuesta inválida';
                throw new Exception("Error AFIP: " . $error_msg);
            }
            
        } catch (Exception $e) {
            error_log("[AFIP_SDK] ❌ Error: " . $e->getMessage());
            
            // Fallback con CAE simulado
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cae_simulado' => $this->generarCAESimulado(),
                'numero_comprobante' => 'SDK-ERR-' . date('YmdHis')
            ];
        }
    }
    
    /**
     * 📊 OBTENER DATOS DE VENTA
     */
    private function obtenerDatosVenta($venta_id) {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ? LIMIT 1");
        $stmt->execute([$venta_id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venta) {
            throw new Exception("Venta {$venta_id} no encontrada");
        }
        
        return $venta;
    }
    
    /**
     * 🎲 GENERAR CAE SIMULADO (Fallback)
     */
    private function generarCAESimulado() {
        return date('Ymd') . rand(10000000, 99999999);
    }
}
?>
        // MONOTRIBUTO: Usar Factura C (código 11) o Ticket C (código 15)
        $document_type = ($venta['monto_total'] <= 1000) ? 15 : 11; // 15=Ticket C, 11=Factura C
        
        // MONOTRIBUTO: NO se discrimina IVA
        // El monto total ya incluye todo, sin desglose
        
        // Preparar items
        $items = [];
        
        // Si hay detalles JSON, usarlos
        if (!empty($venta['detalles_json'])) {
            $detalles = json_decode($venta['detalles_json'], true);
            if (isset($detalles['cart']) && is_array($detalles['cart'])) {
                foreach ($detalles['cart'] as $item) {
                    $items[] = [
                        'description' => $item['nombre'] ?? $item['name'] ?? 'Producto',
                        'quantity' => $item['cantidad'] ?? $item['quantity'] ?? 1,
                        'unit_price' => $item['precio'] ?? $item['price'] ?? $venta['monto_total']
                        // MONOTRIBUTO: Sin vat_rate
                    ];
                }
            }
        }
        
        // Si no hay detalles, crear item genérico
        if (empty($items)) {
            $items[] = [
                'description' => 'Venta general',
                'quantity' => 1,
                'unit_price' => $venta['monto_total']
                // MONOTRIBUTO: Sin vat_rate
            ];
        }
        
        global $DATOS_FISCALES;
        
        return [
            'document_type' => $document_type,
            'point_of_sale' => 3, // Punto de venta habilitado
            'company' => [
                'cuit' => $DATOS_FISCALES['cuit_empresa'],
                'name' => $DATOS_FISCALES['razon_social'],
                'fantasy_name' => $DATOS_FISCALES['nombre_fantasia'],
                'address' => $DATOS_FISCALES['domicilio']['calle'] . ' ' . $DATOS_FISCALES['domicilio']['numero'],
                'city' => 'CABA',
                'state' => 'Capital Federal',
                'postal_code' => $DATOS_FISCALES['domicilio']['codigo_postal'],
                'vat_condition' => 'MONOTRIBUTO' // ✅ MONOTRIBUTO
            ],
            'customer' => [
                'name' => $venta['cliente_nombre'] ?? 'Consumidor Final',
                'document_type' => 99, // Sin identificar
                'document_number' => 0,
                'vat_condition' => 'CF' // Consumidor Final
            ],
            'items' => $items,
            'payment_methods' => [
                [
                    'method' => $this->mapearMetodoPago($venta['metodo_pago']),
                    'amount' => $venta['monto_total']
                ]
            ],
            'totals' => [
                'total_amount' => $venta['monto_total']
                // MONOTRIBUTO: Sin net_amount ni vat_amount
            ]
        ];
    }
    
    /**
     * 🔄 MAPEAR MÉTODO DE PAGO
     */
    private function mapearMetodoPago($metodo) {
        $mapeo = [
            'efectivo' => 'CASH',
            'tarjeta' => 'DEBIT_CARD',
            'transferencia' => 'BANK_TRANSFER',
            'mercadopago' => 'DIGITAL_WALLET',
            'qr' => 'QR_CODE'
        ];
        
        return $mapeo[$metodo] ?? 'CASH';
    }
    
    /**
     * 🌐 EJECUTAR MÉTODO DE WEB SERVICE
     */
    private function ejecutarMetodoWS($metodo, $data, $ta) {
        $url = 'https://app.afipsdk.com/api/v1/afip/exec';
        
        $payload = [
            'environment' => $this->ambiente,
            'method' => $metodo,
            'tax_id' => $this->cuit,
            'wsid' => 'wsfe',
            'ta' => $ta,
            'params' => $data
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->access_token
        ];
        
        error_log("[AFIP_SDK] Ejecutando {$metodo} - CUIT: {$this->cuit}");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        error_log("[AFIP_SDK] Response HTTP: {$http_code}");
        
        if ($curl_error) {
            throw new Exception("Error cURL: {$curl_error}");
        }
        
        if ($http_code !== 200 && $http_code !== 201) {
            throw new Exception("Error HTTP {$http_code}: {$response}");
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error JSON: " . json_last_error_msg());
        }
        
        return $result;
    }
    
    /**
     * 🌐 ENVIAR REQUEST A AFIP SDK (LEGACY - Mantener por compatibilidad)
     */
    private function enviarAFIPSDK($endpoint, $data) {
        $url = $this->base_url . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->access_token,
            'X-CUIT: ' . $this->cuit,
            'X-Environment: ' . $this->ambiente,
            'User-Agent: TayronaKiosco-POS/1.0'
        ];
        
        $payload = json_encode($data);
        
        error_log("[AFIP_SDK] Request a {$url} - CUIT: {$this->cuit}");
        error_log("[AFIP_SDK] Payload: " . $payload);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        error_log("[AFIP_SDK] Response - HTTP: {$http_code} - Tiempo: {$response_time}ms");
        
        if ($curl_error) {
            throw new Exception("Error cURL: {$curl_error}");
        }
        
        if ($http_code !== 200 && $http_code !== 201) {
            error_log("[AFIP_SDK] Error HTTP {$http_code}: {$response}");
            throw new Exception("Error HTTP {$http_code}: {$response}");
        }
        
        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Respuesta JSON inválida: " . json_last_error_msg());
        }
        
        return $decoded_response;
    }
    
    /**
     * 💾 GUARDAR COMPROBANTE EN BD
     */
    private function guardarComprobante($venta_id, $afip_data) {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("
            UPDATE ventas SET 
                cae = ?,
                numero_comprobante = ?,
                comprobante_fiscal = ?,
                tipo_comprobante = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $afip_data['cae'],
            $afip_data['invoice_number'] ?? $afip_data['numero_comprobante'],
            'CAE: ' . $afip_data['cae'] . ' - AFIP SDK REAL - Vto: ' . ($afip_data['cae_due_date'] ?? date('Ymd', strtotime('+10 days'))),
            $afip_data['document_type'] ?? '6',
            $venta_id
        ]);
    }
    
    /**
     * 🎲 GENERAR CAE SIMULADO
     */
    private function generarCAESimulado() {
        return date('YmdHis') . sprintf('%02d', rand(10, 99));
    }
}

/**
 * 🚀 FUNCIÓN PRINCIPAL PARA USAR EN EL SISTEMA
 */
function generarComprobanteAFIPSDK($venta_id) {
    try {
        $afip = new AFIPSDKOptimizado();
        return $afip->generarComprobante($venta_id);
    } catch (Exception $e) {
        error_log("[AFIP_SDK] Error en función principal: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

?>
