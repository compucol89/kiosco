<?php
// Cabeceras para CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log para depuración
$log_info = "Petición recibida: " . $_SERVER['REQUEST_METHOD'] . " - " . date('Y-m-d H:i:s') . "\n";
file_put_contents('logs.txt', $log_info, FILE_APPEND);

// Incluir archivo de configuración de la base de datos
require_once 'config.php';

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


// POST - Guardar una nueva venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recibir datos de la solicitud
        $jsonData = file_get_contents('php://input');
        file_put_contents('ultima_venta.json', $jsonData); // Guardar para depuración
        
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            throw new Exception("Error decodificando JSON: " . json_last_error_msg());
        }
        
        // Extraer datos principales
        $cliente_id = isset($data['cliente']) && isset($data['cliente']['id']) ? $data['cliente']['id'] : 1;
        $cliente_nombre = isset($data['cliente']) && isset($data['cliente']['name']) ? $data['cliente']['name'] : 'Consumidor Final';
        $metodo_pago = $data['paymentMethod'] ?? 'efectivo';
        $subtotal = $data['subtotal'] ?? 0;
        $descuento = $data['discount'] ?? 0;
        $monto_total = $data['total'] ?? $subtotal;
        // Generar ID global de venta usando el sistema bulletproof
        require_once __DIR__ . '/global_id_generator.php';
        $numero_comprobante = GlobalIdGenerator::generateSalesId();
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // 1. Insertar venta en la tabla ventas
        $stmt = $pdo->prepare("
            INSERT INTO ventas (
                cliente_id, 
                cliente_nombre, 
                metodo_pago, 
                subtotal, 
                descuento, 
                monto_total, 
                tipo_comprobante, 
                numero_comprobante,
                estado
            ) VALUES (?, ?, ?, ?, ?, ?, 'Ticket X', ?, 'completado')
        ");
        
        $stmt->execute([
            $cliente_id,
            $cliente_nombre,
            $metodo_pago,
            $subtotal,
            $descuento,
            $monto_total,
            $numero_comprobante
        ]);
        
        $venta_id = $pdo->lastInsertId();
        
        if (!$venta_id) {
            throw new Exception("Error al obtener el ID de la venta insertada");
        }
        
        // 2. Insertar detalles de venta y actualizar stock
        if (!empty($data['cart'])) {
            $stmt_detalle = $pdo->prepare("
                INSERT INTO venta_detalles (
                    venta_id, 
                    producto_id, 
                    producto_nombre, 
                    cantidad, 
                    precio_unitario, 
                    subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt_stock = $pdo->prepare("
                UPDATE productos 
                SET stock = stock - ?, stock_actual = stock_actual - ? 
                WHERE id = ?
            ");
            
            foreach ($data['cart'] as $item) {
                $producto_id = $item['id'];
                $producto_nombre = $item['name'];
                $cantidad = $item['quantity'];
                $precio_unitario = $item['price'];
                $subtotal_item = $precio_unitario * $cantidad;
                
                // Insertar detalle
                $stmt_detalle->execute([
                    $venta_id,
                    $producto_id,
                    $producto_nombre,
                    $cantidad,
                    $precio_unitario,
                    $subtotal_item
                ]);
                
                // Actualizar stock
                try {
                    $stmt_stock->execute([$cantidad, $cantidad, $producto_id]);
                } catch (Exception $e) {
                    // Si falla la actualización de stock, registrar pero continuar
                    error_log("Error actualizando stock del producto $producto_id: " . $e->getMessage());
                }
            }
        }
        
        // 3. Registrar movimiento de caja
        $stmt_caja = $pdo->prepare("
            INSERT INTO caja_movimientos (
                tipo,
                concepto,
                monto,
                venta_id,
                usuario
            ) VALUES ('ingreso', ?, ?, ?, 'Administrador')
        ");
        
        $stmt_caja->execute([
            "Venta {$numero_comprobante}",
            $monto_total,
            $venta_id
        ]);
        
        // Confirmar transacción
        $pdo->commit();
        
        // ========== 📝 LOG DE VENTA COMPLETADA ==========
        // Log de venta completada (notificaciones n8n/Telegram removidas)
        try {
            $datosVentaLog = [
                'venta_id' => $venta_id,
                'numero_comprobante' => $numero_comprobante,
                'cliente_nombre' => $cliente_nombre,
                'metodo_pago' => $metodo_pago,
                'monto_total' => $monto_total,
                'productos_count' => !empty($data['cart']) ? count($data['cart']) : 0,
                'fecha' => date('Y-m-d H:i:s')
            ];
            
            error_log("✅ Venta registrada exitosamente: " . json_encode($datosVentaLog, JSON_UNESCAPED_UNICODE));
            
        } catch (Exception $e) {
            error_log("Error en log de venta: " . $e->getMessage());
        }
        // ========== FIN NOTIFICACIÓN ==========
        
        // Devolver respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Venta registrada correctamente',
            'venta_id' => $venta_id,
            'numero_comprobante' => $numero_comprobante
        ]);
        
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Registrar error
        error_log("Error procesando venta: " . $e->getMessage());
        
        // Devolver respuesta de error
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la venta: ' . $e->getMessage()
        ]);
    }
}
// GET - Obtener ventas
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Consultar todas las ventas
        $stmt = $pdo->query("SELECT * FROM ventas ORDER BY fecha DESC");
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'items' => $ventas
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener ventas: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
} 