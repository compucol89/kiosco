<?php
/**
 * api/seguridad_acceso.php
 * Control de seguridad de acceso por IP y dispositivo
 * Admins pueden entrar desde cualquier lado, vendedores solo desde el negocio
 * RELEVANT FILES: api/auth.php, src/components/LoginPage.jsx
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
    
    // Crear tabla de configuración de seguridad
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS seguridad_acceso (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_negocio VARCHAR(50) NOT NULL,
            descripcion VARCHAR(200),
            activo BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Crear tabla de logs de intentos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs_acceso (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100),
            ip_origen VARCHAR(50),
            user_agent TEXT,
            exito BOOLEAN,
            motivo_rechazo VARCHAR(200),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_timestamp (timestamp),
            KEY idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'obtener_config':
            obtenerConfiguracion($pdo);
            break;
        case 'guardar_config':
            guardarConfiguracion($pdo);
            break;
        case 'verificar_acceso':
            verificarAcceso($pdo);
            break;
        case 'logs':
            obtenerLogs($pdo);
            break;
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Obtener configuración actual
 */
function obtenerConfiguracion($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM seguridad_acceso 
        WHERE activo = 1 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Crear configuración por defecto
        $pdo->exec("INSERT INTO seguridad_acceso (ip_negocio, descripcion) VALUES ('0.0.0.0', 'Configurar IP del negocio')");
        $config = [
            'id' => $pdo->lastInsertId(),
            'ip_negocio' => '0.0.0.0',
            'descripcion' => 'Configurar IP del negocio',
            'activo' => 1
        ];
    }
    
    echo json_encode([
        'success' => true,
        'configuracion' => $config
    ]);
}

/**
 * Guardar nueva configuración
 */
function guardarConfiguracion($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ipNegocio = $input['ip_negocio'] ?? '';
    $descripcion = $input['descripcion'] ?? '';
    
    if (empty($ipNegocio)) {
        throw new Exception('IP del negocio es requerida');
    }
    
    // Validar formato IP
    if (!filter_var($ipNegocio, FILTER_VALIDATE_IP) && $ipNegocio !== '0.0.0.0') {
        throw new Exception('Formato de IP inválido');
    }
    
    // Desactivar configuraciones anteriores
    $pdo->exec("UPDATE seguridad_acceso SET activo = 0");
    
    // Insertar nueva configuración
    $stmt = $pdo->prepare("
        INSERT INTO seguridad_acceso (ip_negocio, descripcion, activo) 
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$ipNegocio, $descripcion]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Configuración de seguridad guardada'
    ]);
}

/**
 * Verificar si un usuario puede acceder desde una IP
 */
function verificarAcceso($pdo) {
    $username = $_GET['username'] ?? '';
    $ipOrigen = $_SERVER['REMOTE_ADDR'] ?? $_GET['ip'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Obtener IP del negocio configurada
    $stmt = $pdo->query("SELECT ip_negocio FROM seguridad_acceso WHERE activo = 1 LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $ipNegocio = $config['ip_negocio'] ?? '0.0.0.0';
    
    // Obtener rol del usuario
    $stmtUser = $pdo->prepare("SELECT role FROM usuarios WHERE username = ?");
    $stmtUser->execute([$username]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    $accesoConcedido = true;
    $motivoRechazo = null;
    
    if ($usuario) {
        $rol = $usuario['role'];
        
        // ADMIN puede entrar desde cualquier lado
        if ($rol === 'admin') {
            $accesoConcedido = true;
            $motivoRechazo = null;
        } 
        // VENDEDOR/CAJERO solo desde IP del negocio
        else {
            if ($ipNegocio === '0.0.0.0') {
                // Si no hay IP configurada, permitir acceso (modo desarrollo)
                $accesoConcedido = true;
            } else {
                // Verificar que la IP coincida
                if ($ipOrigen === $ipNegocio) {
                    $accesoConcedido = true;
                } else {
                    $accesoConcedido = false;
                    $motivoRechazo = "Acceso denegado. Los vendedores solo pueden iniciar sesión desde el negocio. (IP permitida: {$ipNegocio}, tu IP: {$ipOrigen})";
                }
            }
        }
    } else {
        $accesoConcedido = false;
        $motivoRechazo = "Usuario no encontrado";
    }
    
    // Registrar intento en logs
    $stmtLog = $pdo->prepare("
        INSERT INTO logs_acceso (username, ip_origen, user_agent, exito, motivo_rechazo) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtLog->execute([
        $username,
        $ipOrigen,
        $userAgent,
        $accesoConcedido ? 1 : 0,
        $motivoRechazo
    ]);
    
    echo json_encode([
        'success' => true,
        'acceso_concedido' => $accesoConcedido,
        'motivo_rechazo' => $motivoRechazo,
        'ip_origen' => $ipOrigen,
        'ip_negocio' => $ipNegocio,
        'rol' => $usuario['role'] ?? null
    ]);
}

/**
 * Obtener logs de intentos de acceso
 */
function obtenerLogs($pdo) {
    $limite = (int)($_GET['limite'] ?? 50);
    
    $stmt = $pdo->prepare("
        SELECT * FROM logs_acceso 
        ORDER BY timestamp DESC 
        LIMIT ?
    ");
    $stmt->execute([$limite]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total' => count($logs)
    ]);
}
?>

