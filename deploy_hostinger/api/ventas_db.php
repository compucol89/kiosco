<?php
require_once 'bd_conexion.php';

// Función para registrar la venta en la tabla de movimientos de caja
function registrarVentaEnCaja($pdo, $datos) {
    try {
        // Asegurarse de que hay una caja abierta
        $stmt_caja = $pdo->prepare("SELECT id FROM caja WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
        $stmt_caja->execute();
        $caja = $stmt_caja->fetch(PDO::FETCH_ASSOC);

        if (!$caja) {
            // No interrumpir la venta, solo registrar el log
            error_log("ADVERTENCIA: No se encontró una caja abierta para registrar la venta ID: " . ($datos['venta_id'] ?? 'N/A'));
            return null;
        }
        $caja_id = $caja['id'];

        // Validar datos esenciales
        $venta_id = $datos['venta_id'] ?? null;
        $metodo_pago = $datos['metodo_pago'] ?? 'indefinido';
        $monto_total = $datos['monto_total'] ?? 0;
        $usuario_id = $datos['usuario_id'] ?? 1; // Default a 1 si no se provee

        if (!$venta_id) {
            throw new Exception('Falta el ID de la venta para registrarla en caja.');
        }

        // Determinar si afecta al efectivo físico
        $afecta_efectivo = in_array(strtolower($metodo_pago), ['efectivo', 'cash']) ? 1 : 0;

        // Crear descripción del movimiento
        $descripcion = sprintf('Venta #%s - %s', $venta_id, ucfirst($metodo_pago));
        if (!empty($datos['numero_comprobante'])) {
            $descripcion .= ' (Comp: ' . $datos['numero_comprobante'] . ')';
        }

        // Preparar la inserción en movimientos_caja
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_caja (
                caja_id, tipo, monto, descripcion, usuario_id, fecha_hora,
                metodo_pago, tipo_transaccion, venta_id, afecta_efectivo,
                numero_comprobante, categoria, estado
            ) VALUES (
                :caja_id, 'entrada', :monto, :descripcion, :usuario_id, NOW(),
                :metodo_pago, 'venta', :venta_id, :afecta_efectivo,
                :numero_comprobante, 'venta', 'confirmado'
            )
        ");

        $stmt->execute([
            'caja_id' => $caja_id,
            'monto' => $monto_total,
            'descripcion' => $descripcion,
            'usuario_id' => $usuario_id,
            'metodo_pago' => $metodo_pago,
            'venta_id' => $venta_id,
            'afecta_efectivo' => $afecta_efectivo,
            'numero_comprobante' => $datos['numero_comprobante'] ?? null
        ]);

        return $pdo->lastInsertId();
    } catch (Exception $e) {
        // Loguear el error pero no detener el flujo principal de la venta
        error_log('Error en registrarVentaEnCaja: ' . $e->getMessage() . ' - Datos: ' . json_encode($datos));
        // Devolver null o false para indicar que el registro en caja falló pero la venta puede continuar
        return null;
    }
}
?>