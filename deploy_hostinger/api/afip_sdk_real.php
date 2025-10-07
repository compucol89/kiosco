<?php
/**
 * api/afip_sdk_real.php
 * Integración real con AFIP usando librería oficial
 * MONOTRIBUTO - Factura C sin discriminación de IVA
 * RELEVANT FILES: config_afip.php, procesar_venta_ultra_rapida.php
 */

require_once __DIR__ . '/../vendor/afipsdk/afip.php/src/Afip.php';
require_once 'config_afip.php';
require_once 'bd_conexion.php';

class AFIPReal {
    
    private $afip;
    private $cuit;
    
    public function __construct() {
        global $DATOS_FISCALES;
        
        $this->cuit = $DATOS_FISCALES['cuit_empresa'];
        
        try {
            // Leer certificados
            $cert = file_get_contents(__DIR__ . '/certificados/afip.crt');
            $key = file_get_contents(__DIR__ . '/certificados/afip.key');
            
            // Inicializar AFIP SDK
            $this->afip = new Afip([
                'CUIT' => intval($this->cuit),
                'cert' => $cert,
                'key' => $key,
                'access_token' => 'ZaGIyLvwsMSiwPrwTFkA2oU2ktoDqjuQ9Gt6QN26quHvQrdcKJzc76L6ch9Wu4uW',
                'production' => true
            ]);
            
            error_log("[AFIP_REAL] ✅ Inicializado - CUIT: {$this->cuit} - PRODUCCIÓN");
            
        } catch (Exception $e) {
            error_log("[AFIP_REAL] ❌ Error inicializando: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 🧾 GENERAR COMPROBANTE FISCAL REAL
     */
    public function generarComprobante($venta_id) {
        try {
            // 1. Obtener datos de la venta
            $venta = $this->obtenerDatosVenta($venta_id);
            
            // 2. Configuración del comprobante
            $tipo_comprobante = 11; // 11 = Factura C (MONOTRIBUTO)
            $punto_venta = 3;
            
            // 3. Obtener último número
            $ultimo = $this->afip->ElectronicBilling->GetLastVoucher($punto_venta, $tipo_comprobante);
            $numero = $ultimo + 1;
            
            error_log("[AFIP_REAL] Último comprobante PV {$punto_venta}: {$ultimo} - Generando: {$numero}");
            
            // 4. Preparar datos (MONOTRIBUTO - Sin IVA)
            $data = [
                'CantReg' => 1,
                'PtoVta' => $punto_venta,
                'CbteTipo' => $tipo_comprobante,
                'Concepto' => 1,
                'DocTipo' => 99,
                'DocNro' => 0,
                'CbteDesde' => $numero,
                'CbteHasta' => $numero,
                'CbteFch' => intval(date('Ymd')),
                'ImpTotal' => floatval($venta['monto_total']),
                'ImpTotConc' => 0,
                'ImpNeto' => floatval($venta['monto_total']),
                'ImpOpEx' => 0,
                'ImpIVA' => 0,
                'ImpTrib' => 0,
                'MonId' => 'PES',
                'MonCotiz' => 1
            ];
            
            // 5. Solicitar CAE a AFIP
            error_log("[AFIP_REAL] Solicitando CAE a AFIP...");
            $response = $this->afip->ElectronicBilling->CreateVoucher($data);
            
            // 6. Procesar respuesta
            if (isset($response['CAE'])) {
                $cae = $response['CAE'];
                $vencimiento = $response['CAEFchVto'];
                $comprobante = str_pad($punto_venta, 5, '0', STR_PAD_LEFT) . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
                
                // Guardar en BD
                $pdo = Conexion::obtenerConexion();
                $stmt = $pdo->prepare("UPDATE ventas SET cae = ?, comprobante_fiscal = ?, tipo_comprobante = 'FACTURA_C' WHERE id = ?");
                $stmt->execute([$cae, $comprobante, $venta_id]);
                
                error_log("[AFIP_REAL] ✅ CAE REAL: {$cae} - Comprobante: {$comprobante}");
                
                return [
                    'success' => true,
                    'cae' => $cae,
                    'numero_comprobante' => $comprobante,
                    'fecha_vencimiento' => $vencimiento,
                    'tipo_comprobante' => 'FACTURA_C',
                    'metodo' => 'AFIP_REAL'
                ];
            } else {
                $errors = isset($response['Errors']) ? json_encode($response['Errors']) : 'Sin detalles';
                throw new Exception("AFIP rechazó: " . $errors);
            }
            
        } catch (Exception $e) {
            error_log("[AFIP_REAL] ❌ Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cae_simulado' => $this->generarCAESimulado()
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
