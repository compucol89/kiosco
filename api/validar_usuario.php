<?php
// Incluir middleware de CORS
require_once 'cors_middleware.php';

// Incluir archivo de configuración
require_once 'config.php';

// Sólo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Verificar datos requeridos
if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos (username, password)']);
    exit;
}

try {
    // Verificar si existe el usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$data['username']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        // Usuario no encontrado
        http_response_code(401);
        echo json_encode(['valid' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Verificar contraseña (asumiendo que está almacenada con password_hash)
    if (password_verify($data['password'], $usuario['password'])) {
        // Contraseña correcta
        http_response_code(200);
        echo json_encode([
            'valid' => true, 
            'message' => 'Credenciales válidas',
            'user' => [
                'id' => $usuario['id'],
                'username' => $usuario['username'],
                'nombre' => $usuario['nombre'],
                'role' => $usuario['role']
            ]
        ]);
    } else {
        // Contraseña incorrecta
        http_response_code(401);
        echo json_encode(['valid' => false, 'message' => 'Contraseña incorrecta']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'Error al validar usuario: ' . $e->getMessage()]);
} 