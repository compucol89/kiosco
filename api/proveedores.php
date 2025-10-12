<?php
/**
 * api/proveedores.php
 * API completa para gestión de proveedores
 * CRUD de proveedores para sistema de pedidos inteligentes
 * RELEVANT FILES: api/productos.php, src/components/GestionProveedores.jsx
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear tabla de proveedores si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS proveedores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(200) NOT NULL,
            razon_social VARCHAR(200),
            cuit VARCHAR(20),
            telefono VARCHAR(50),
            whatsapp VARCHAR(50),
            email VARCHAR(100),
            direccion TEXT,
            categoria VARCHAR(100) COMMENT 'Ej: Panadería, Bebidas, Snacks',
            dias_entrega VARCHAR(100) COMMENT 'Ej: Lunes y Jueves',
            monto_minimo DECIMAL(10,2) DEFAULT 0,
            tiempo_entrega_dias INT DEFAULT 2,
            notas TEXT,
            activo BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_activo (activo),
            KEY idx_categoria (categoria)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Proveedores para sistema de pedidos'
    ");
    
    $metodo = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;
    
    switch ($metodo) {
        case 'GET':
            if ($id) {
                obtenerProveedor($pdo, $id);
            } else {
                listarProveedores($pdo);
            }
            break;
            
        case 'POST':
            crearProveedor($pdo);
            break;
            
        case 'PUT':
            actualizarProveedor($pdo, $id);
            break;
            
        case 'DELETE':
            eliminarProveedor($pdo, $id);
            break;
            
        default:
            throw new Exception('Método HTTP no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Listar todos los proveedores
 */
function listarProveedores($pdo) {
    $soloActivos = $_GET['activos'] ?? 'true';
    
    $sql = "SELECT * FROM proveedores";
    if ($soloActivos === 'true') {
        $sql .= " WHERE activo = 1";
    }
    $sql .= " ORDER BY nombre ASC";
    
    $stmt = $pdo->query($sql);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar productos por proveedor
    foreach ($proveedores as &$proveedor) {
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM productos 
            WHERE proveedor = ? AND activo = 1
        ");
        $stmtCount->execute([$proveedor['nombre']]);
        $proveedor['total_productos'] = (int)$stmtCount->fetchColumn();
    }
    
    echo json_encode([
        'success' => true,
        'proveedores' => $proveedores,
        'total' => count($proveedores)
    ]);
}

/**
 * Obtener un proveedor específico
 */
function obtenerProveedor($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
    $stmt->execute([$id]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$proveedor) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Proveedor no encontrado'
        ]);
        return;
    }
    
    // Obtener productos de este proveedor
    $stmtProductos = $pdo->prepare("
        SELECT id, codigo, nombre, stock, stock_minimo, precio_costo
        FROM productos 
        WHERE proveedor = ? AND activo = 1
        ORDER BY nombre ASC
    ");
    $stmtProductos->execute([$proveedor['nombre']]);
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'proveedor' => $proveedor,
        'productos' => $productos,
        'total_productos' => count($productos)
    ]);
}

/**
 * Crear nuevo proveedor
 */
function crearProveedor($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['nombre'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El nombre del proveedor es requerido'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO proveedores (
            nombre, razon_social, cuit, telefono, whatsapp, email,
            direccion, categoria, dias_entrega, monto_minimo, 
            tiempo_entrega_dias, notas, activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $input['nombre'],
        $input['razon_social'] ?? null,
        $input['cuit'] ?? null,
        $input['telefono'] ?? null,
        $input['whatsapp'] ?? null,
        $input['email'] ?? null,
        $input['direccion'] ?? null,
        $input['categoria'] ?? null,
        $input['dias_entrega'] ?? null,
        $input['monto_minimo'] ?? 0,
        $input['tiempo_entrega_dias'] ?? 2,
        $input['notas'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Proveedor creado exitosamente',
        'proveedor_id' => $pdo->lastInsertId()
    ]);
}

/**
 * Actualizar proveedor
 */
function actualizarProveedor($pdo, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de proveedor requerido'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("
        UPDATE proveedores SET
            nombre = ?,
            razon_social = ?,
            cuit = ?,
            telefono = ?,
            whatsapp = ?,
            email = ?,
            direccion = ?,
            categoria = ?,
            dias_entrega = ?,
            monto_minimo = ?,
            tiempo_entrega_dias = ?,
            notas = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['nombre'] ?? '',
        $input['razon_social'] ?? null,
        $input['cuit'] ?? null,
        $input['telefono'] ?? null,
        $input['whatsapp'] ?? null,
        $input['email'] ?? null,
        $input['direccion'] ?? null,
        $input['categoria'] ?? null,
        $input['dias_entrega'] ?? null,
        $input['monto_minimo'] ?? 0,
        $input['tiempo_entrega_dias'] ?? 2,
        $input['notas'] ?? null,
        $id
    ]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Proveedor actualizado exitosamente'
    ]);
}

/**
 * Eliminar proveedor (soft delete)
 */
function eliminarProveedor($pdo, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de proveedor requerido'
        ]);
        return;
    }
    
    // Verificar si tiene productos asignados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM productos WHERE proveedor = (
            SELECT nombre FROM proveedores WHERE id = ?
        )
    ");
    $stmt->execute([$id]);
    $totalProductos = $stmt->fetchColumn();
    
    if ($totalProductos > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "No se puede eliminar. Tiene {$totalProductos} productos asignados.",
            'total_productos' => $totalProductos
        ]);
        return;
    }
    
    // Soft delete
    $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Proveedor eliminado exitosamente'
    ]);
}
?>

