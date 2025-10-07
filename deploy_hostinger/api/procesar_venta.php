<?php
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

// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una solicitud OPTIONS (preflight), responder exitosamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivo de configuración
require_once 'config.php';

// 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


/**
 * Registrar venta en caja usando la API optimizada
 */
function registrarVentaEnCajaOptimizada($pdo, $datos) {
    try {
        // Verificar que hay una caja abierta
        $stmt = $pdo->prepare("SELECT id FROM caja WHERE estado = 'abierta'");
        $stmt->execute();
        $caja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$caja) {
            throw new Exception('No hay una caja abierta para registrar la venta');
        }
        
        // Determinar si afecta efectivo físico
        $afecta_efectivo = in_array(strtolower($datos['metodo_pago']), ['efectivo', 'cash']) ? 1 : 0;
        
        // Insertar movimiento en la tabla optimizada
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
        
        $descripcion = sprintf(
            'Venta #%s - Pago con %s',
            $datos['venta_id'],
            ucfirst($datos['metodo_pago'])
        );
        
        if (isset($datos['numero_comprobante'])) {
            $descripcion .= ' (' . $datos['numero_comprobante'] . ')';
        }
        
        $stmt->execute([
            'caja_id' => $caja['id'],
            'monto' => (float)$datos['monto_total'],
            'descripcion' => $descripcion,
            'usuario_id' => (int)$datos['usuario_id'],
            'metodo_pago' => strtolower($datos['metodo_pago']),
            'venta_id' => (int)$datos['venta_id'],
            'afecta_efectivo' => $afecta_efectivo,
            'numero_comprobante' => $datos['numero_comprobante'] ?? null
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        throw new Exception('Error al registrar venta en caja: ' . $e->getMessage());
    }
}

// Guardar un registro de la solicitud para depuración
error_log("Solicitud recibida en procesar_venta.php: " . $_SERVER['REQUEST_METHOD']);

// Verificar si las tablas necesarias existen y crearlas si no
function verificarTablas($pdo) {
    try {
        // Verificar tabla ventas
        $pdo->query("SELECT 1 FROM ventas LIMIT 1");
    } catch (PDOException $e) {
        // Crear tabla ventas si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS ventas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_nombre VARCHAR(255),
            metodo_pago VARCHAR(50),
            subtotal DECIMAL(10,2),
            descuento DECIMAL(10,2),
            monto_total DECIMAL(10,2),
            numero_comprobante VARCHAR(50),
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            estado VARCHAR(20) DEFAULT 'completado',
            detalles_json TEXT,
            caja_id INT NULL
        )");
        error_log("Tabla ventas creada");
    }
    
    // Verificar si es necesario agregar la columna caja_id a la tabla ventas
    try {
        $columnas = $pdo->query("SHOW COLUMNS FROM ventas LIKE 'caja_id'");
        if ($columnas->rowCount() === 0) {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN caja_id INT NULL");
            error_log("Columna caja_id agregada a la tabla ventas");
        }
    } catch (PDOException $e) {
        error_log("Error al verificar/agregar columna caja_id: " . $e->getMessage());
    }
    
    try {
        // Verificar tabla venta_detalles
        $pdo->query("SELECT 1 FROM venta_detalles LIMIT 1");
    } catch (PDOException $e) {
        // Crear tabla venta_detalles si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS venta_detalles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venta_id INT,
            producto_id INT,
            producto_nombre VARCHAR(255),
            cantidad INT,
            precio_unitario DECIMAL(10,2),
            subtotal DECIMAL(10,2),
            FOREIGN KEY (venta_id) REFERENCES ventas(id)
        )");
        error_log("Tabla venta_detalles creada");
    }
    
    // === NUEVO: asegurar tabla movimientos_caja utilizada por el módulo de Caja ===
    try {
        $pdo->query("SELECT 1 FROM movimientos_caja LIMIT 1");
    } catch (PDOException $e) {
        // Crear la tabla requerida por api/caja.php si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS movimientos_caja (
            id INT AUTO_INCREMENT PRIMARY KEY,
            caja_id INT NULL,
            tipo VARCHAR(20) NOT NULL, -- 'entrada' | 'salida'
            monto DECIMAL(12,2) NOT NULL DEFAULT 0,
            descripcion VARCHAR(255) NULL,
            usuario_id INT NULL,
            fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            metodo_pago VARCHAR(50) NULL, -- efectivo | tarjeta | transferencia | mercadopago | qr
            tipo_transaccion VARCHAR(50) NULL, -- venta | operacion | ajuste | retiro
            venta_id INT NULL,
            afecta_efectivo TINYINT(1) NOT NULL DEFAULT 0,
            numero_comprobante VARCHAR(100) NULL,
            categoria VARCHAR(100) NULL,
            observaciones_extra TEXT NULL,
            estado VARCHAR(50) NULL DEFAULT 'confirmado',
            INDEX idx_caja_id (caja_id),
            INDEX idx_tipo (tipo),
            INDEX idx_metodo_pago (metodo_pago),
            INDEX idx_tipo_transaccion (tipo_transaccion),
            INDEX idx_fecha (fecha_hora)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        error_log("Tabla movimientos_caja creada");
        
        // Intentar migrar datos básicos desde la tabla antigua si existe
        try {
            $pdo->query("SELECT 1 FROM caja_movimientos LIMIT 1");
            $pdo->exec("INSERT INTO movimientos_caja (caja_id, tipo, monto, descripcion, usuario_id, fecha_hora, metodo_pago, tipo_transaccion, venta_id, afecta_efectivo, numero_comprobante, categoria, estado)
                        SELECT caja_id, tipo, monto, concepto, NULL, fecha, 'efectivo', 'operacion', venta_id, 1, NULL, 'operacion', 'confirmado' FROM caja_movimientos");
            error_log('Datos migrados desde caja_movimientos a movimientos_caja (básico)');
        } catch (PDOException $e2) {
            // Tabla antigua no existe, continuar
        }
    }
}

/**
 * 🎯 CALCULAR DESCUENTOS CONDICIONADOS POR PRODUCTO
 * Aplica descuentos solo a productos elegibles según su configuración
 */
function calcularDescuentosCondicionados($cart, $descuento_total, $subtotal_total) {
    $subtotal_elegible = 0;
    $subtotal_exento = 0;
    $productos_elegibles = [];
    $productos_exentos = [];
    
    // Separar productos elegibles y exentos
    foreach ($cart as $item) {
        $precio_linea = floatval($item['price']) * intval($item['quantity']);
        $es_elegible = !isset($item['aplica_descuento_forma_pago']) || $item['aplica_descuento_forma_pago'] !== false;
        
        if ($es_elegible) {
            $subtotal_elegible += $precio_linea;
            $productos_elegibles[] = [
                'nombre' => $item['name'],
                'cantidad' => $item['quantity'],
                'precio_unitario' => $item['price'],
                'subtotal_linea' => $precio_linea,
                'elegible_descuento' => true
            ];
        } else {
            $subtotal_exento += $precio_linea;
            $productos_exentos[] = [
                'nombre' => $item['name'],
                'cantidad' => $item['quantity'],
                'precio_unitario' => $item['price'],
                'subtotal_linea' => $precio_linea,
                'elegible_descuento' => false
            ];
        }
    }
    
    // Calcular descuento aplicado solo al subtotal elegible
    $descuento_aplicado = 0;
    if ($subtotal_elegible > 0 && $descuento_total > 0) {
        // Calcular proporción del descuento
        $porcentaje_descuento = ($descuento_total / $subtotal_total) * 100;
        $descuento_aplicado = $subtotal_elegible * ($porcentaje_descuento / 100);
    }
    
    $total_final = $subtotal_total - $descuento_aplicado;
    
    return [
        'subtotal_elegible' => $subtotal_elegible,
        'subtotal_exento' => $subtotal_exento,
        'descuento_aplicado' => $descuento_aplicado,
        'total_final' => $total_final,
        'porcentaje_descuento' => $subtotal_total > 0 ? ($descuento_aplicado / $subtotal_total) * 100 : 0,
        'productos_elegibles' => $productos_elegibles,
        'productos_exentos' => $productos_exentos,
        'lineas_detalle' => array_merge($productos_elegibles, $productos_exentos)
    ];
}

// Verificar tablas al inicio
verificarTablas($pdo);

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recibir datos del cuerpo de la solicitud
        $jsonData = file_get_contents('php://input');
        // Guardar para depuración
        file_put_contents('ultima_venta.json', $jsonData);
        
        error_log("Datos recibidos: " . $jsonData);
        
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            throw new Exception("Error decodificando JSON: " . json_last_error_msg());
        }
        
        // Verificar datos requeridos
        if (empty($data['cart'])) {
            throw new Exception("No hay productos en el carrito");
        }
        
        // Preparar datos para la venta
        $cliente_id = isset($data['cliente']) && isset($data['cliente']['id']) ? $data['cliente']['id'] : 1;
        $cliente_nombre = isset($data['cliente']) && isset($data['cliente']['name']) ? $data['cliente']['name'] : 'Consumidor Final';
        $metodo_pago = $data['paymentMethod'] ?? 'efectivo';
        $subtotal = $data['subtotal'] ?? 0;
        $descuento = $data['discount'] ?? 0;
        $monto_total = $data['total'] ?? $subtotal;
        
        // 🎯 CALCULAR DESCUENTOS CONDICIONADOS
        $desglose_descuentos = calcularDescuentosCondicionados($data['cart'], $descuento, $subtotal);
        error_log("Desglose de descuentos: " . json_encode($desglose_descuentos));
        // Obtener ID de caja si existe
        $caja_id = $data['caja_id'] ?? null;
        // Generar ID global de venta usando el sistema bulletproof
        require_once __DIR__ . '/global_id_generator.php';
        $numero_comprobante = GlobalIdGenerator::generateSalesId();
        $tipo_comprobante = 'Ticket X';
        $usuario = 'Administrador';
        
        // Crear JSON de detalles para almacenar en la columna detalles_json
        $detalles_json = json_encode([
            'cliente' => [
                'name' => $cliente_nombre,
                'id' => '0',
                'cuit' => ''
            ],
            'cart' => $data['cart']
        ]);
        
        // Mostrar datos para depuración
        error_log("Datos de venta preparados: " . json_encode([
            'cliente_id' => $cliente_id,
            'cliente_nombre' => $cliente_nombre,
            'metodo_pago' => $metodo_pago,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'monto_total' => $monto_total
        ]));
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Insertar en la tabla ventas
        $stmt = $pdo->prepare("
            INSERT INTO ventas (
                cliente_nombre, 
                metodo_pago, 
                subtotal, 
                descuento, 
                monto_total, 
                numero_comprobante, 
                estado,
                detalles_json,
                caja_id
            ) VALUES (?, ?, ?, ?, ?, ?, 'completado', ?, ?)
        ");
        
        $params = [
            $cliente_nombre,
            $metodo_pago,
            $subtotal,
            $descuento,
            $monto_total,
            $numero_comprobante,
            $detalles_json,
            $caja_id
        ];
        
        error_log("Ejecutando inserción en ventas con parámetros: " . json_encode($params));
        
        if (!$stmt->execute($params)) {
            throw new Exception("Error al insertar venta: " . implode(", ", $stmt->errorInfo()));
        }
        
        // Obtener el ID de la venta
        $venta_id = $pdo->lastInsertId();
        error_log("Venta creada con ID: " . $venta_id);
        
        if (!$venta_id) {
            throw new Exception("No se pudo obtener el ID de la venta");
        }
        
        // No usar la tabla venta_detalles porque tiene una restricción de clave foránea que apunta a ventas_old
        // En su lugar, incluir toda la información en el JSON de la venta principal
        
        // Procesar cada ítem del carrito y actualizar stock
        foreach ($data['cart'] as $item) {
            $producto_id = $item['id'];
            $cantidad = $item['quantity'];
            
            // Actualizar stock del producto (ambos campos: stock y stock_actual)
            $stmt_stock = $pdo->prepare("
                UPDATE productos 
                SET stock = stock - ?, stock_actual = stock_actual - ? 
                WHERE id = ?
            ");
            
            if (!$stmt_stock->execute([$cantidad, $cantidad, $producto_id])) {
                error_log("Error al actualizar stock del producto " . $producto_id . ": " . implode(", ", $stmt_stock->errorInfo()));
            }
        }
        
        // Registrar en caja usando la API optimizada
        try {
            registrarVentaEnCajaOptimizada($pdo, [
                'venta_id' => $venta_id,
                'metodo_pago' => $metodo_pago,
                'monto_total' => $monto_total,
                'numero_comprobante' => $numero_comprobante,
                'usuario_id' => 1 // Usuario por defecto, se puede mejorar
            ]);
            
            error_log("Venta registrada en caja optimizada: venta_id={$venta_id}, metodo={$metodo_pago}, monto={$monto_total}");
            
        } catch (Exception $e) {
            // CRÍTICO: Si falla la inserción en caja, hacer rollback de toda la transacción
            error_log("CRÍTICO - Error al registrar venta en caja optimizada: " . $e->getMessage());
            $pdo->rollBack();
            throw new Exception("Error crítico: La venta no pudo ser registrada en el control de caja. " . $e->getMessage());
        }
        
        // Confirmar la transacción
        $pdo->commit();
        
        // ========== 🧾 GENERAR COMPROBANTE FISCAL AFIP ==========
        $comprobante_fiscal = null;
        try {
            // Incluir el servicio AFIP
            require_once 'afip_service.php';
            
            // Generar comprobante fiscal automáticamente
            $resultado_afip = generarComprobanteFiscalDesdVenta($venta_id);
            
            if ($resultado_afip['success']) {
                $comprobante_fiscal = [
                    'cae' => $resultado_afip['comprobante']['comprobante']['cae'],
                    'numero_comprobante_fiscal' => $resultado_afip['comprobante']['comprobante']['numero_comprobante'],
                    'tipo_comprobante' => $resultado_afip['comprobante']['comprobante']['tipo_comprobante'],
                    'codigo_barras' => $resultado_afip['comprobante']['comprobante']['codigo_barras'],
                    'qr_data' => $resultado_afip['comprobante']['comprobante']['qr_data']
                ];
                error_log("✅ Comprobante fiscal AFIP generado: " . $resultado_afip['comprobante']['comprobante']['numero_comprobante']);
            } else {
                error_log("⚠️ Error generando comprobante AFIP: " . $resultado_afip['error']);
            }
        } catch (Exception $e) {
            error_log("⚠️ Error en servicio AFIP: " . $e->getMessage());
        }
        // ========== FIN COMPROBANTE FISCAL ==========
        
        // ========== 📧 NOTIFICACIONES ELIMINADAS ==========
        // Las notificaciones ahora se procesan en background para mayor velocidad
        error_log("✅ Venta completada: ID {$venta_id} - Notificaciones en background");
        // ========== FIN NOTIFICACIONES ==========
        
        // ========== FACTURACIÓN ELECTRÓNICA AFIP - ASÍNCRONA ⚡ ==========
        $datos_fiscales = null;
        try {
            require_once 'afip_async_processor.php';
            
            error_log("🚀 Encolando facturación electrónica AFIP asíncrona para venta ID: {$venta_id}");
            
            $async_processor = new AFIPAsyncProcessor();
            $resultado_queue = $async_processor->enqueueFacturacion($venta_id, 'normal');
            
            if ($resultado_queue['success']) {
                $datos_fiscales = [
                    'estado_fiscal' => 'PROCESSING',
                    'mensaje' => 'Factura en proceso de generación',
                    'tiempo_estimado' => $resultado_queue['estimated_time'],
                    'queue_id' => $resultado_queue['queue_id'] ?? null,
                    'procesamiento_asincrono' => true
                ];
                
                error_log("✅ Venta encolada exitosamente para facturación AFIP - Queue ID: " . ($resultado_queue['queue_id'] ?? 'N/A'));
                
            } else {
                error_log("❌ Error encolando venta para facturación AFIP: " . $resultado_queue['error']);
                // Fallback: No interrumpir el flujo de venta
                $datos_fiscales = [
                    'estado_fiscal' => 'QUEUE_ERROR',
                    'error_mensaje' => $resultado_queue['error'],
                    'procesamiento_asincrono' => false
                ];
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en procesador asíncrono AFIP: " . $e->getMessage());
            // Fallback: No interrumpir el flujo de venta
            $datos_fiscales = [
                'estado_fiscal' => 'SYSTEM_ERROR',
                'error_mensaje' => $e->getMessage(),
                'procesamiento_asincrono' => false
            ];
        }
        // ========== FIN FACTURACIÓN AFIP ASÍNCRONA ==========
        
        // Devolver respuesta exitosa
        $response = [
            'success' => true,
            'message' => 'Venta registrada correctamente',
            'venta_id' => $venta_id,
            'numero_comprobante' => $numero_comprobante,
            'comprobante_fiscal' => $comprobante_fiscal,
            'desglose_descuentos' => $desglose_descuentos,
            'datos_fiscales' => $datos_fiscales
        ];
        
        error_log("Venta completada exitosamente: " . json_encode($response));
        
        // Limpiar buffer y enviar respuesta JSON limpia
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
        
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Error procesando venta: " . $e->getMessage());
        file_put_contents('error_ventas.txt', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
        
        // Devolver respuesta de error limpia
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la venta: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
} 
// Solicitud GET para obtener ventas
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Consultar todas las ventas
        $stmt = $pdo->query("SELECT * FROM ventas ORDER BY fecha DESC");
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'items' => $ventas
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    } catch (Exception $e) {
        // Registrar error para depuración
        error_log("Error obteniendo ventas: " . $e->getMessage());
        
        // Respuesta de error limpia
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener ventas: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }
} else {
    // Método no permitido
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
} 