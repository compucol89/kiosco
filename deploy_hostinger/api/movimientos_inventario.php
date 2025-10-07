<?php
// Incluir middleware de CORS
require_once 'cors_middleware.php';

// Incluir archivo de configuración
require_once 'config.php';

// Verificar si la tabla existe
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'movimientos_inventario'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Crear la tabla si no existe
        $sql = "CREATE TABLE movimientos_inventario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha DATETIME NOT NULL,
            tipo ENUM('entrada', 'salida') NOT NULL,
            cantidad INT NOT NULL,
            motivo TEXT,
            producto_id INT NOT NULL,
            producto_nombre VARCHAR(255) NOT NULL,
            stock_anterior INT NOT NULL,
            stock_nuevo INT NOT NULL,
            usuario VARCHAR(100) NOT NULL,
            verificado_por VARCHAR(100),
            motivo_verificacion TEXT,
            evidencia_url VARCHAR(255),
            ip_usuario VARCHAR(45),
            terminal TEXT,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al verificar/crear tabla: ' . $e->getMessage()]);
    exit;
}

// Método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener movimientos de inventario
        try {
            // Filtrar por producto_id si se proporciona
            if (isset($_GET['producto_id']) && is_numeric($_GET['producto_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM movimientos_inventario WHERE producto_id = ? ORDER BY fecha DESC");
                $stmt->execute([$_GET['producto_id']]);
            } else {
                // Parámetros de filtrado opcionales
                $filters = [];
                $params = [];
                
                if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
                    $filters[] = "tipo = ?";
                    $params[] = $_GET['tipo'];
                }
                
                if (isset($_GET['usuario']) && !empty($_GET['usuario'])) {
                    $filters[] = "usuario = ?";
                    $params[] = $_GET['usuario'];
                }
                
                if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
                    $filters[] = "fecha >= ?";
                    $params[] = $_GET['fecha_desde'];
                }
                
                if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
                    $filters[] = "fecha <= ?";
                    $params[] = $_GET['fecha_hasta'];
                }
                
                // Construir la consulta
                $sql = "SELECT * FROM movimientos_inventario";
                
                if (!empty($filters)) {
                    $sql .= " WHERE " . implode(' AND ', $filters);
                }
                
                $sql .= " ORDER BY fecha DESC";
                
                // Limitar resultados si se especifica
                if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
                    $sql .= " LIMIT " . intval($_GET['limit']);
                } else {
                    $sql .= " LIMIT 1000"; // Límite predeterminado
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($movimientos);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener movimientos: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Registrar un nuevo movimiento de inventario
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar datos requeridos
        if (!isset($data['tipo']) || !isset($data['cantidad']) || !isset($data['producto_id']) || 
            !isset($data['producto_nombre']) || !isset($data['stock_anterior']) || !isset($data['stock_nuevo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos requeridos']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO movimientos_inventario 
                (fecha, tipo, cantidad, motivo, producto_id, producto_nombre, stock_anterior, stock_nuevo, 
                usuario, verificado_por, motivo_verificacion, evidencia_url, ip_usuario, terminal) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $fecha = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d H:i:s');
            
            $stmt->execute([
                $fecha,
                $data['tipo'],
                $data['cantidad'],
                $data['motivo'] ?? null,
                $data['producto_id'],
                $data['producto_nombre'],
                $data['stock_anterior'],
                $data['stock_nuevo'],
                $data['usuario'] ?? 'Sistema',
                $data['verificado_por'] ?? null,
                $data['motivo_verificacion'] ?? null,
                $data['evidencia_url'] ?? null,
                $data['ip_usuario'] ?? $_SERVER['REMOTE_ADDR'],
                $data['terminal'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible'
            ]);
            
            $data['id'] = $pdo->lastInsertId();
            
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Movimiento registrado correctamente', 'data' => $data]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al registrar movimiento: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
} 