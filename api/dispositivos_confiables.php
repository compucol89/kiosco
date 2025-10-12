<?php
/**
 * api/dispositivos_confiables.php
 * Sistema de autorización de dispositivos para vendedores/cajeros
 * Admin aprueba dispositivos, funciona con IP dinámica
 * RELEVANT FILES: api/seguridad_acceso.php, src/components/LoginPage.jsx
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
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
    
    // Crear tabla de dispositivos confiables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dispositivos_confiables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_fingerprint VARCHAR(255) NOT NULL UNIQUE,
            codigo_activacion VARCHAR(20) NOT NULL UNIQUE,
            nombre_dispositivo VARCHAR(200),
            usuario_solicito VARCHAR(100),
            usuario_aprobo VARCHAR(100),
            ip_primer_uso VARCHAR(50),
            user_agent TEXT,
            estado ENUM('pendiente', 'aprobado', 'rechazado', 'revocado') DEFAULT 'pendiente',
            fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_aprobacion TIMESTAMP NULL,
            ultima_actividad TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_fingerprint (device_fingerprint),
            KEY idx_codigo (codigo_activacion),
            KEY idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'solicitar_acceso':
            solicitarAcceso($pdo);
            break;
        case 'verificar_dispositivo':
            verificarDispositivo($pdo);
            break;
        case 'listar_dispositivos':
            listarDispositivos($pdo);
            break;
        case 'aprobar_dispositivo':
            aprobarDispositivo($pdo);
            break;
        case 'rechazar_dispositivo':
            rechazarDispositivo($pdo);
            break;
        case 'revocar_dispositivo':
            revocarDispositivo($pdo);
            break;
        case 'registrar_actividad':
            registrarActividad($pdo);
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
 * Generar código de activación único
 */
function generarCodigoActivacion() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $parte1 = substr(str_shuffle($chars), 0, 2);
    $parte2 = substr(str_shuffle('0123456789'), 0, 4);
    $parte3 = substr(str_shuffle($chars), 0, 2);
    $parte4 = substr(str_shuffle('0123456789'), 0, 4);
    
    return "{$parte1}-{$parte2}-{$parte3}-{$parte4}";
}

/**
 * Solicitar acceso desde un nuevo dispositivo
 */
function solicitarAcceso($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fingerprint = $input['device_fingerprint'] ?? '';
    $username = $input['username'] ?? '';
    $ipOrigen = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (empty($fingerprint)) {
        throw new Exception('Fingerprint del dispositivo requerido');
    }
    
    // Verificar si ya existe este dispositivo
    $stmt = $pdo->prepare("SELECT * FROM dispositivos_confiables WHERE device_fingerprint = ?");
    $stmt->execute([$fingerprint]);
    $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dispositivo) {
        echo json_encode([
            'success' => true,
            'dispositivo_existe' => true,
            'dispositivo' => $dispositivo
        ]);
        return;
    }
    
    // Crear nueva solicitud
    $codigo = generarCodigoActivacion();
    
    $stmt = $pdo->prepare("
        INSERT INTO dispositivos_confiables (
            device_fingerprint, codigo_activacion, usuario_solicito,
            ip_primer_uso, user_agent, estado
        ) VALUES (?, ?, ?, ?, ?, 'pendiente')
    ");
    $stmt->execute([$fingerprint, $codigo, $username, $ipOrigen, $userAgent]);
    
    echo json_encode([
        'success' => true,
        'dispositivo_existe' => false,
        'codigo_activacion' => $codigo,
        'mensaje' => 'Solicitud creada. Espera a que un administrador apruebe este dispositivo.'
    ]);
}

/**
 * Verificar si un dispositivo está autorizado
 */
function verificarDispositivo($pdo) {
    $fingerprint = $_GET['fingerprint'] ?? '';
    $username = $_GET['username'] ?? '';
    
    if (empty($fingerprint)) {
        throw new Exception('Fingerprint requerido');
    }
    
    // Obtener rol del usuario
    $stmtUser = $pdo->prepare("SELECT role FROM usuarios WHERE username = ?");
    $stmtUser->execute([$username]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    // Admin siempre tiene acceso
    if ($usuario && $usuario['role'] === 'admin') {
        echo json_encode([
            'success' => true,
            'acceso_concedido' => true,
            'motivo' => 'Administrador - acceso desde cualquier dispositivo',
            'requiere_aprobacion' => false
        ]);
        return;
    }
    
    // Verificar dispositivo
    $stmt = $pdo->prepare("SELECT * FROM dispositivos_confiables WHERE device_fingerprint = ?");
    $stmt->execute([$fingerprint]);
    $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dispositivo) {
        // Dispositivo no registrado
        echo json_encode([
            'success' => true,
            'acceso_concedido' => false,
            'motivo' => 'Dispositivo no registrado',
            'requiere_aprobacion' => true
        ]);
        return;
    }
    
    if ($dispositivo['estado'] === 'aprobado') {
        // Actualizar última actividad
        $pdo->prepare("UPDATE dispositivos_confiables SET ultima_actividad = NOW() WHERE id = ?")
            ->execute([$dispositivo['id']]);
        
        echo json_encode([
            'success' => true,
            'acceso_concedido' => true,
            'motivo' => 'Dispositivo autorizado',
            'requiere_aprobacion' => false,
            'dispositivo' => $dispositivo
        ]);
        return;
    }
    
    if ($dispositivo['estado'] === 'pendiente') {
        echo json_encode([
            'success' => true,
            'acceso_concedido' => false,
            'motivo' => 'Esperando aprobación del administrador',
            'requiere_aprobacion' => false,
            'codigo_activacion' => $dispositivo['codigo_activacion'],
            'estado' => 'pendiente'
        ]);
        return;
    }
    
    // Rechazado o revocado
    echo json_encode([
        'success' => true,
        'acceso_concedido' => false,
        'motivo' => 'Dispositivo bloqueado por el administrador',
        'requiere_aprobacion' => false
    ]);
}

/**
 * Listar todos los dispositivos
 */
function listarDispositivos($pdo) {
    $estado = $_GET['estado'] ?? 'todos';
    
    $sql = "SELECT * FROM dispositivos_confiables";
    if ($estado !== 'todos') {
        $sql .= " WHERE estado = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$estado]);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    $dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'dispositivos' => $dispositivos,
        'total' => count($dispositivos),
        'por_estado' => [
            'pendientes' => count(array_filter($dispositivos, fn($d) => $d['estado'] === 'pendiente')),
            'aprobados' => count(array_filter($dispositivos, fn($d) => $d['estado'] === 'aprobado')),
            'rechazados' => count(array_filter($dispositivos, fn($d) => $d['estado'] === 'rechazado')),
            'revocados' => count(array_filter($dispositivos, fn($d) => $d['estado'] === 'revocado'))
        ]
    ]);
}

/**
 * Aprobar dispositivo
 */
function aprobarDispositivo($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $codigo = $input['codigo_activacion'] ?? '';
    $adminUsername = $input['admin_username'] ?? 'admin';
    $nombreDispositivo = $input['nombre_dispositivo'] ?? 'Dispositivo';
    
    if (empty($codigo)) {
        throw new Exception('Código de activación requerido');
    }
    
    $stmt = $pdo->prepare("
        UPDATE dispositivos_confiables 
        SET estado = 'aprobado',
            usuario_aprobo = ?,
            nombre_dispositivo = ?,
            fecha_aprobacion = NOW()
        WHERE codigo_activacion = ?
    ");
    $stmt->execute([$adminUsername, $nombreDispositivo, $codigo]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Código de activación no encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Dispositivo aprobado exitosamente'
    ]);
}

/**
 * Rechazar dispositivo
 */
function rechazarDispositivo($pdo) {
    $codigo = $_GET['codigo'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE dispositivos_confiables SET estado = 'rechazado' WHERE codigo_activacion = ?");
    $stmt->execute([$codigo]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Dispositivo rechazado'
    ]);
}

/**
 * Revocar acceso a dispositivo
 */
function revocarDispositivo($pdo) {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $pdo->prepare("UPDATE dispositivos_confiables SET estado = 'revocado' WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Acceso revocado'
    ]);
}

/**
 * Registrar actividad
 */
function registrarActividad($pdo) {
    $fingerprint = $_GET['fingerprint'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE dispositivos_confiables SET ultima_actividad = NOW() WHERE device_fingerprint = ?");
    $stmt->execute([$fingerprint]);
    
    echo json_encode(['success' => true]);
}
?>

