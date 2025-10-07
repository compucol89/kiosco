<?php
/**
 * 🚀 PROCESAMIENTO DE VENTAS ULTRA-RÁPIDO
 * Target: <500ms response time
 * Optimización: Procesos críticos síncronos, todo lo demás asíncrono
 */

// CONFIGURACIÓN AGRESIVA PARA JSON LIMPIO
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar cualquier output previo
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// CORS optimizado
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Preflight optimizado
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

/**
 * 🏁 REGISTRO RÁPIDO EN CAJA - SOLO LO ESENCIAL
 */
function registrarVentaRapida($pdo, $datos) {
    try {
        // ⚡ BUSCAR TURNO ACTIVO (Nueva lógica mejorada)
        $stmt = $pdo->prepare("SELECT id FROM turnos_caja WHERE estado = 'abierto' LIMIT 1");
        $stmt->execute();
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$turno) {
            error_log("Advertencia: No hay turno abierto, venta registrada sin movimiento de caja");
            return null;
        }
        
        // 🔄 REGISTRAR VENTA EN EL TURNO
        if (strtolower($datos['metodo_pago']) === 'efectivo') {
            // Actualizar ventas en efectivo
            $stmt = $pdo->prepare("
                UPDATE turnos_caja SET
                    ventas_efectivo = ventas_efectivo + ?,
                    cantidad_ventas = cantidad_ventas + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$datos['monto_total'], $turno['id']]);
        } else {
            // Actualizar otros métodos de pago
            $campo = match(strtolower($datos['metodo_pago'])) {
                'transferencia' => 'ventas_transferencia',
                'tarjeta' => 'ventas_tarjeta',
                'qr', 'mercadopago' => 'ventas_qr',
                default => 'ventas_transferencia'
            };
            
            $stmt = $pdo->prepare("
                UPDATE turnos_caja SET
                    {$campo} = {$campo} + ?,
                    cantidad_ventas = cantidad_ventas + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$datos['monto_total'], $turno['id']]);
        }
        
        return $turno['id'];
        
    } catch (Exception $e) {
        error_log("Error registro rápido caja: " . $e->getMessage());
        return false; // No bloquear la venta
    }
}

/**
 * 🚀 PROCESAMIENTO ULTRA-RÁPIDO
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startTime = microtime(true);
    
    try {
        // Recibir datos (sin logging pesado)
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data || (empty($data['cart']) && empty($data['items']))) {
            throw new Exception("Datos inválidos");
        }
        
        // Compatibilidad: items -> cart
        if (empty($data['cart']) && !empty($data['items'])) {
            $data['cart'] = $data['items'];
        }
        
        // ========== DATOS ESENCIALES ==========
        $cliente_nombre = $data['cliente'] ?? 'Consumidor Final';
        $metodo_pago = $data['paymentMethod'] ?? 'efectivo';
        
        // CALCULAR TOTALES DESDE ITEMS SI NO ESTÁN DISPONIBLES
        $subtotal_calculado = 0;
        $items = $data['cart'] ?? $data['items'] ?? [];
        
        if (is_array($items)) {
            foreach ($items as $item) {
                $precio = floatval($item['precio'] ?? $item['price'] ?? 0);
                $cantidad = floatval($item['cantidad'] ?? $item['quantity'] ?? 1);
                $subtotal_calculado += $precio * $cantidad;
            }
        }
        
        // Usar totales de la interfaz o calcular automáticamente
        $subtotal = $data['subtotal'] ?? $data['totals']['subtotal'] ?? $subtotal_calculado;
        $descuento = $data['discount'] ?? $data['totals']['descuento'] ?? 0;
        $monto_total = $data['total'] ?? $data['totals']['finalTotal'] ?? ($subtotal - $descuento);
        
        // VALIDACIÓN CRÍTICA: Asegurar que el monto no sea $0.00
        if ($monto_total <= 0 && $subtotal_calculado > 0) {
            $monto_total = $subtotal_calculado - $descuento;
            $subtotal = $subtotal_calculado;
            error_log("CORRECCIÓN AUTO: Monto recalculado desde items - Subtotal: {$subtotal}, Total: {$monto_total}");
        }
        // Generar ID global de venta usando el sistema bulletproof
        require_once __DIR__ . '/global_id_generator.php';
        $numero_comprobante = GlobalIdGenerator::generateSalesId();
        
        // ========== TRANSACCIÓN RÁPIDA ==========
        $pdo->beginTransaction();
        
        // 1. INSERTAR VENTA - QUERY OPTIMIZADA
        $stmt = $pdo->prepare("
            INSERT INTO ventas (
                cliente_nombre, metodo_pago, subtotal, descuento, monto_total, 
                numero_comprobante, estado, detalles_json
            ) VALUES (?, ?, ?, ?, ?, ?, 'completado', ?)
        ");
        
        $detalles_json = json_encode([
            'cliente' => ['name' => $cliente_nombre, 'id' => '0'],
            'cart' => $data['cart'],
            'items' => $data['items'] ?? $data['cart']
        ]);
        
        $stmt->execute([
            $cliente_nombre, $metodo_pago, $subtotal, $descuento, 
            $monto_total, $numero_comprobante, $detalles_json
        ]);
        
        $venta_id = $pdo->lastInsertId();
        
        // 2. ACTUALIZAR STOCK - BATCH OPTIMIZADO (SEGURO)
        foreach ($data['cart'] as $item) {
            $producto_id = $item['id'] ?? $item['codigo'] ?? null;
            $cantidad = floatval($item['quantity'] ?? $item['cantidad'] ?? 1);
            
            if ($producto_id) {
                // Verificar si el producto existe antes de actualizar
                $stmt_check = $pdo->prepare("SELECT id, stock FROM productos WHERE id = ? LIMIT 1");
                $stmt_check->execute([$producto_id]);
                $producto = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if ($producto) {
                    // Actualizar solo stock principal (seguro)
                    $stmt_stock = $pdo->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $stmt_stock->execute([$cantidad, $producto_id]);
                }
            }
        }
        
        // 3. REGISTRO RÁPIDO EN CAJA
        registrarVentaRapida($pdo, [
            'venta_id' => $venta_id,
            'metodo_pago' => $metodo_pago,
            'monto_total' => $monto_total
        ]);
        
        // ========== COMMIT INMEDIATO ==========
        $pdo->commit();
        
        // ========== FACTURACIÓN FISCAL SELECTIVA ==========
        $comprobante_fiscal = null;
        $fiscal_start = microtime(true);
        
        // Verificar si este método de pago requiere factura REAL
        require_once 'config_facturacion.php';
        $debe_facturar_real = requiereFacturaAFIP($metodo_pago);
        
        error_log("[FACTURACION] Método: {$metodo_pago} - Factura REAL: " . ($debe_facturar_real ? 'SÍ (AFIP SDK)' : 'NO (CAE Simulado)'));
        
        if ($debe_facturar_real) {
            try {
                // ✅ AFIP SDK REAL - Para métodos configurados (QR, Transferencia)
                require_once 'afip_sdk_real.php';
                $afip_real = new AFIPReal();
                $resultado_afip = $afip_real->generarComprobante($venta_id);
            
            if ($resultado_afip['success']) {
                $comprobante_fiscal = [
                    'cae' => $resultado_afip['cae'],
                    'numero_comprobante' => $resultado_afip['numero_comprobante'],
                    'tipo_comprobante' => $resultado_afip['tipo_comprobante'] ?? 'FACTURA_C',
                    'fecha_vencimiento' => $resultado_afip['fecha_vencimiento'],
                    'estado_afip' => 'APROBADO_REAL',
                    'metodo' => 'AFIP_SDK_REAL',
                    'monto_total' => $monto_total
                ];
                error_log("✅ AFIP SDK REAL - CAE generado para venta {$venta_id}: {$comprobante_fiscal['cae']}");
            } else {
                // Fallback: Generar comprobante local temporal
                $comprobante_fiscal = [
                    'estado_afip' => 'PENDIENTE',
                    'numero_comprobante_fiscal' => $numero_comprobante . '-TEMP',
                    'tipo_comprobante' => 'TICKET_FISCAL_TEMP',
                    'mensaje' => 'Comprobante fiscal en proceso de validación AFIP'
                ];
                error_log("⚠️ AFIP temporalmente no disponible para venta {$venta_id}");
            }
            } catch (Exception $e) {
                // Fallback si AFIP falla - generar comprobante temporal
                $comprobante_fiscal = [
                    'estado_afip' => 'ERROR_TEMPORAL',
                    'numero_comprobante_fiscal' => $numero_comprobante . '-OFFLINE',
                    'tipo_comprobante' => 'TICKET_FISCAL_OFFLINE',
                    'mensaje' => 'Sistema fiscal temporalmente no disponible - Comprobante válido será generado automáticamente'
                ];
                error_log("❌ Error AFIP: " . $e->getMessage());
            }
        } else {
            // ❌ AFIP SIMPLE (CAE Simulado) - Para métodos sin factura real (Efectivo, Tarjeta)
            try {
                require_once 'afip_simple.php';
                $resultado_simulado = generarComprobanteFiscalDesdVenta($venta_id);
                
                if ($resultado_simulado['success']) {
                    $comprobante_fiscal = [
                        'cae' => $resultado_simulado['comprobante']['comprobante']['cae'],
                        'numero_comprobante' => $resultado_simulado['comprobante']['comprobante']['numero_comprobante'],
                        'tipo_comprobante' => 'TICKET_INTERNO',
                        'fecha_vencimiento' => $resultado_simulado['comprobante']['comprobante']['fecha_vencimiento'],
                        'codigo_barras' => $resultado_simulado['comprobante']['comprobante']['codigo_barras'],
                        'qr_data' => $resultado_simulado['comprobante']['comprobante']['qr_data'],
                        'estado_afip' => 'SIMULADO',
                        'metodo' => 'CAE_INTERNO',
                        'monto_total' => $monto_total
                    ];
                    error_log("📋 CAE Simulado generado para venta {$venta_id} - Método: {$metodo_pago}");
                }
            } catch (Exception $e) {
                error_log("⚠️ Error generando CAE simulado: " . $e->getMessage());
            }
        }
        
        $fiscal_time = (microtime(true) - $fiscal_start) * 1000;
        
        // ========== RESPUESTA ULTRA-RÁPIDA CON DATOS FISCALES ==========
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        $response = [
            'success' => true,
            'message' => 'Venta procesada exitosamente',
            'venta_id' => $venta_id,
            'numero_comprobante' => $numero_comprobante,
            'execution_time_ms' => round($executionTime, 2),
            'fiscal_time_ms' => round($fiscal_time, 2),
            'fast_mode' => true,
            'comprobante_fiscal' => $comprobante_fiscal,
            'background_tasks' => [
                'notifications' => 'queued',
                'additional_processing' => 'queued'
            ]
        ];
        
        // ========== LANZAR PROCESOS BACKGROUND (NO BLOQUEAN) ==========
        // Guardar datos para procesamiento asíncrono
        try {
            if (!is_dir('queue')) {
                mkdir('queue', 0755, true);
            }
            
            file_put_contents('queue/venta_' . $venta_id . '.json', json_encode([
                'venta_id' => $venta_id,
                'timestamp' => time(),
                'data' => $data,
                'numero_comprobante' => $numero_comprobante,
                'cliente_nombre' => $cliente_nombre,
                'metodo_pago' => $metodo_pago,
                'monto_total' => $monto_total
            ], JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            error_log("Error guardando en cola: " . $e->getMessage());
        }
        
        // ========== RESPUESTA LIMPIA INMEDIATA ==========
        // Limpiar completamente el buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Enviar respuesta JSON limpia
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Cerrar conexión inmediatamente
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        // ========== PROCESOS ASÍNCRONOS POST-RESPUESTA ==========
        // Ejecutar en background (no afecta tiempo de respuesta)
        try {
            // Los procesos pesados se manejan en background_processor.php
            error_log("✅ Venta {$venta_id} encolada para procesamiento background");
            
        } catch (Exception $e) {
            error_log("Error en background: " . $e->getMessage());
        }
        
        exit;
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // Limpiar buffer completamente
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la venta: ' . $e->getMessage(),
            'execution_time_ms' => round($executionTime, 2),
            'error_code' => 'PROCESSING_ERROR'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        error_log("Error en venta ultra-rápida: " . $e->getMessage());
        exit;
    }
}

// Método no permitido
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
?>
