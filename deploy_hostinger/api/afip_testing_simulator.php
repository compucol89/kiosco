<?php
/**
 * 🧪 SIMULADOR AFIP PARA TESTING
 * Simula respuestas AFIP en ambiente de testing
 * Mantiene la estructura real pero sin depender de WSAA
 */

require_once 'config_afip.php';
require_once 'bd_conexion.php';

class AFIPTestingSimulator {
    
    private $config;
    private $datos_fiscales;
    
    public function __construct() {
        global $CONFIGURACION_AFIP, $DATOS_FISCALES;
        $this->config = $CONFIGURACION_AFIP;
        $this->datos_fiscales = $DATOS_FISCALES;
    }
    
    /**
     * 🧾 GENERAR COMPROBANTE SIMULADO
     */
    public function generarComprobante($venta_id) {
        try {
            // Obtener datos de la venta
            $venta = $this->obtenerDatosVenta($venta_id);
            
            // Determinar tipo de comprobante
            $tipo_cbte = $this->determinarTipoComprobante($venta['monto_total']);
            
            // Generar CAE simulado pero realista
            $cae = $this->generarCAESimulado();
            $numero_comprobante = $this->generarNumeroComprobante($tipo_cbte);
            $fecha_vencimiento = date('Ymd', strtotime('+10 days'));
            
            // Guardar comprobante en BD
            $this->guardarComprobante($venta_id, [
                'cae' => $cae,
                'numero_comprobante' => $numero_comprobante,
                'fecha_vencimiento' => $fecha_vencimiento,
                'tipo_comprobante' => $tipo_cbte
            ]);
            
            error_log("[AFIP_TESTING] Comprobante simulado generado: CAE {$cae} para venta {$venta_id}");
            
            return [
                'success' => true,
                'cae' => $cae,
                'numero_comprobante' => $numero_comprobante,
                'fecha_vencimiento' => $fecha_vencimiento,
                'tipo_comprobante' => $tipo_cbte,
                'simulado' => true,
                'ambiente' => 'TESTING'
            ];
            
        } catch (Exception $e) {
            error_log("[AFIP_TESTING] Error generando comprobante simulado: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cae_simulado' => $this->generarCAESimulado(),
                'numero_comprobante' => 'ERR-' . date('YmdHis') . '-' . $venta_id
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
     * 🎯 DETERMINAR TIPO DE COMPROBANTE
     */
    private function determinarTipoComprobante($monto) {
        if ($monto <= 1000) {
            return 83; // Ticket Fiscal
        } else {
            return 6;  // Factura B
        }
    }
    
    /**
     * 🎲 GENERAR CAE SIMULADO REALISTA
     */
    private function generarCAESimulado() {
        // CAE tiene 14 dígitos, formato: YYYYMMDDHHMISS
        return date('YmdHis') . sprintf('%02d', rand(10, 99));
    }
    
    /**
     * 🔢 GENERAR NÚMERO DE COMPROBANTE
     */
    private function generarNumeroComprobante($tipo_cbte) {
        // Obtener último número de este tipo desde BD
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(numero_comprobante, '-', -1) AS UNSIGNED)) as ultimo_numero 
            FROM ventas 
            WHERE tipo_comprobante = ? 
            AND numero_comprobante REGEXP '^[0-9]+-[0-9]+$'
        ");
        $stmt->execute([$tipo_cbte]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $ultimo_numero = $resultado['ultimo_numero'] ?? 0;
        $siguiente_numero = $ultimo_numero + 1;
        
        return sprintf('0001-%08d', $siguiente_numero);
    }
    
    /**
     * 💾 GUARDAR COMPROBANTE EN BD
     */
    private function guardarComprobante($venta_id, $cae_data) {
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
            $cae_data['cae'],
            $cae_data['numero_comprobante'],
            'CAE: ' . $cae_data['cae'] . ' - Vto: ' . $cae_data['fecha_vencimiento'],
            $cae_data['tipo_comprobante'],
            $venta_id
        ]);
    }
}

/**
 * 🚀 FUNCIÓN PRINCIPAL PARA TESTING
 */
function generarComprobanteAFIPTesting($venta_id) {
    try {
        $afip = new AFIPTestingSimulator();
        return $afip->generarComprobante($venta_id);
    } catch (Exception $e) {
        error_log("[AFIP_TESTING] Error en función principal: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

?>
