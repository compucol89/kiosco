<?php
// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una solicitud OPTIONS (preflight), respondemos exitosamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivo de configuración
require_once 'config.php';

// Comprobar si la base de datos tiene las tablas necesarias
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ventas'");
    if ($stmt->rowCount() == 0) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'La base de datos no tiene la estructura necesaria para ventas',
            'setup_required' => true
        ]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar estructura de la base de datos: ' . $e->getMessage()
    ]);
    exit;
}

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Ruta del endpoint (para expandir la API en el futuro)
$request = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';

// Procesar según el método HTTP
switch ($method) {
    case 'GET':
        try {
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $porPagina = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
            $offset = ($pagina - 1) * $porPagina;
            
            // 🌍 CONFIGURAR ZONA HORARIA ARGENTINA
            date_default_timezone_set('America/Argentina/Buenos_Aires');
            
            // Parámetros de filtrado
            $fecha_inicio = $_GET['fecha_inicio'] ?? null;
            $fecha_fin = $_GET['fecha_fin'] ?? null;
            $cliente_id = $_GET['cliente_id'] ?? null;
            $metodo_pago = $_GET['metodo_pago'] ?? null;
            $estado = $_GET['estado'] ?? null;
            
            // 🔥 FIX: Si no se especifican fechas, filtrar solo por HOY (turno actual)
            // Esto evita que se muestren ventas de días anteriores
            if (!$fecha_inicio && !$fecha_fin) {
                $fecha_inicio = date('Y-m-d');
                $fecha_fin = date('Y-m-d');
            }
            
            // Construir la consulta base
            $sql = "SELECT * FROM ventas WHERE 1=1";
            $params = [];
            
            // Agregar filtros si existen
            if ($fecha_inicio) {
                $sql .= " AND fecha >= ?";
                $params[] = $fecha_inicio . ' 00:00:00';
            }
            
            if ($fecha_fin) {
                $sql .= " AND fecha <= ?";
                $params[] = $fecha_fin . ' 23:59:59';
            }
            
            if ($cliente_id) {
                $sql .= " AND cliente_id = ?";
                $params[] = $cliente_id;
            }
            
            if ($metodo_pago) {
                $sql .= " AND metodo_pago = ?";
                $params[] = $metodo_pago;
            }
            
            if ($estado) {
                $sql .= " AND estado = ?";
                $params[] = $estado;
            }
            
            // Contar total de registros con los filtros aplicados
            $sqlCount = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
            $stmtCount = $pdo->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = $stmtCount->fetchColumn();
            
            // Ordenar por fecha descendente y paginar
            $sql .= " ORDER BY fecha DESC LIMIT ? OFFSET ?";
            $params[] = $porPagina;
            $params[] = $offset;
            
            // Ejecutar consulta
            $stmt = $pdo->prepare($sql);
            
            // Asignar los tipos correctos para LIMIT y OFFSET
            for ($i = 0; $i < count($params); $i++) {
                $paramType = PDO::PARAM_STR;
                if ($i >= count($params) - 2) {
                    $paramType = PDO::PARAM_INT;
                }
                $stmt->bindValue($i + 1, $params[$i], $paramType);
            }
            
            $stmt->execute();
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Siempre devolver solo datos reales de la base de datos
            $response = [
                    'success' => true,
                    'total' => $total,
                    'pagina' => $pagina,
                    'por_pagina' => $porPagina,
                    'total_paginas' => ceil($total / $porPagina),
                    'items' => $ventas
                ];
            
            echo json_encode($response);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener ventas: ' . $e->getMessage()
            ]);
        }
        break;
    
    case 'POST':
        // Crear una nueva venta
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos requeridos
        if (!isset($data['monto_total']) || !isset($data['items']) || empty($data['items'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Faltan datos requeridos']);
            exit;
        }
        
        try {
            // Iniciar transacción
            $pdo->beginTransaction();
            
            // Preparar datos para insertar la venta
            $venta = [
                'cliente_nombre' => $data['cliente_nombre'] ?? 'Consumidor Final',
                'cliente_id' => $data['cliente_id'] ?? null,
                'cliente_cuit' => $data['cliente_cuit'] ?? null,
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'Ticket',
                'numero_comprobante' => $data['numero_comprobante'] ?? null,
                'monto_total' => $data['monto_total'],
                'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                'descuento' => $data['descuento'] ?? 0,
                'impuestos' => $data['impuestos'] ?? 0,
                'estado' => $data['estado'] ?? 'completado'
            ];
            
            // Insertar la venta
            $stmt = $pdo->prepare("INSERT INTO ventas (
                cliente_nombre, cliente_id, cliente_cuit, tipo_comprobante, 
                numero_comprobante, monto_total, metodo_pago, descuento, impuestos, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $venta['cliente_nombre'],
                $venta['cliente_id'],
                $venta['cliente_cuit'],
                $venta['tipo_comprobante'],
                $venta['numero_comprobante'],
                $venta['monto_total'],
                $venta['metodo_pago'],
                $venta['descuento'],
                $venta['impuestos'],
                $venta['estado']
            ]);
            
            $ventaId = $pdo->lastInsertId();
            
            // Insertar los detalles de la venta
            $stmtDetalle = $pdo->prepare("INSERT INTO detalle_ventas (
                venta_id, producto_id, producto_codigo, producto_nombre,
                cantidad, precio_unitario, subtotal
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($data['items'] as $item) {
                $stmtDetalle->execute([
                    $ventaId,
                    $item['producto_id'],
                    $item['producto_codigo'],
                    $item['producto_nombre'],
                    $item['cantidad'],
                    $item['precio_unitario'],
                    $item['subtotal']
                ]);
                
                // Actualizar stock del producto (si está completado)
                if ($venta['estado'] === 'completado') {
                    $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - ?, stock_actual = stock_actual - ? WHERE id = ?");
                    $stmtStock->execute([$item['cantidad'], $item['cantidad'], $item['producto_id']]);
                }
            }
            
            // Confirmar transacción
            $pdo->commit();
            
            // Devolver la venta creada
            $venta['id'] = $ventaId;
            $venta['fecha'] = date('Y-m-d H:i:s');
            $venta['items'] = $data['items'];
            
            http_response_code(201);
            echo json_encode($venta);
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $pdo->rollBack();
            
            http_response_code(500);
            echo json_encode(['message' => 'Error al crear la venta: ' . $e->getMessage()]);
        }
        break;
    
    case 'PUT':
        // Actualizar el estado de una venta (ejemplo: cancelar)
        if (empty($request) || !is_numeric($request)) {
            http_response_code(400);
            echo json_encode(['message' => 'ID de venta inválido']);
            exit;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['estado'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Se requiere el campo estado']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Obtener la venta actual
            $stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ?");
            $stmt->execute([$request]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$venta) {
                http_response_code(404);
                echo json_encode(['message' => 'Venta no encontrada']);
                $pdo->rollBack();
                exit;
            }
            
            // Si estamos cambiando a cancelado y antes estaba completado, debemos restaurar stock
            if ($data['estado'] === 'cancelado' && $venta['estado'] === 'completado') {
                // Obtener los items de la venta
                $stmt = $pdo->prepare("SELECT * FROM detalle_ventas WHERE venta_id = ?");
                $stmt->execute([$request]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Restaurar stock
                $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock + ?, stock_actual = stock_actual + ? WHERE id = ?");
                foreach ($items as $item) {
                    $stmtStock->execute([$item['cantidad'], $item['cantidad'], $item['producto_id']]);
                }
            }
            
            // Actualizar estado de la venta
            $stmt = $pdo->prepare("UPDATE ventas SET estado = ? WHERE id = ?");
            $stmt->execute([$data['estado'], $request]);
            
            $pdo->commit();
            
            http_response_code(200);
            echo json_encode(['message' => 'Estado de la venta actualizado correctamente']);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            
            http_response_code(500);
            echo json_encode(['message' => 'Error al actualizar la venta: ' . $e->getMessage()]);
        }
        break;
    
    case 'DELETE':
        // Esta operación no está permitida para ventas (por motivos contables)
        http_response_code(405);
        echo json_encode(['message' => 'No se permite eliminar ventas']);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método no permitido']);
        break;
}

 