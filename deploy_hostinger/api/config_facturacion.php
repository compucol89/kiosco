<?php
/**
 * api/config_facturacion.php
 * Gestión de configuración de facturación por método de pago
 * Permite configurar qué métodos requieren factura AFIP
 * RELEVANT FILES: procesar_venta_ultra_rapida.php, afip_sdk_optimizado.php, configuracion_facturacion (tabla)
 */

require_once 'bd_conexion.php';

/**
 * Verificar si un método de pago requiere factura AFIP
 */
function requiereFacturaAFIP($metodo_pago) {
    try {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("
            SELECT requiere_factura 
            FROM configuracion_facturacion 
            WHERE metodo_pago = ? AND activo = 1
            LIMIT 1
        ");
        $stmt->execute([strtolower($metodo_pago)]);
        $config = $stmt->fetch();
        
        // Por defecto, solo QR y transferencia facturan
        return $config ? (bool)$config['requiere_factura'] : false;
        
    } catch (Exception $e) {
        error_log("[CONFIG_FACTURACION] Error: " . $e->getMessage());
        // Fallback seguro: solo facturar QR y transferencia
        return in_array(strtolower($metodo_pago), ['qr', 'transferencia']);
    }
}

/**
 * Obtener configuración completa de facturación
 */
function obtenerConfiguracionFacturacion() {
    try {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->query("SELECT * FROM configuracion_facturacion ORDER BY metodo_pago");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("[CONFIG_FACTURACION] Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Actualizar configuración de facturación
 */
function actualizarConfiguracionFacturacion($metodo_pago, $requiere_factura) {
    try {
        $pdo = Conexion::obtenerConexion();
        $stmt = $pdo->prepare("
            UPDATE configuracion_facturacion 
            SET requiere_factura = ?, updated_at = NOW()
            WHERE metodo_pago = ?
        ");
        $stmt->execute([$requiere_factura ? 1 : 0, strtolower($metodo_pago)]);
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("[CONFIG_FACTURACION] Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>


