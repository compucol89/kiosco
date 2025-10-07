<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Incluir la conexión a la base de datos
require_once 'bd_conexion.php';

// Inicializar la conexión a la base de datos
$pdo = Conexion::obtenerConexion();

// Si no se pudo conectar a la BD, devolver error
if ($pdo === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectar con la base de datos. Inténtelo de nuevo más tarde.'
    ]);
    exit();
}

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que se hayan enviado los datos necesarios
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan datos requeridos'
    ]);
    exit();
}

// Sanitizar datos recibidos
$username = htmlspecialchars(strip_tags($data['username']));
$password = $data['password'];

try {
    // Registro para debugging
    error_log("Intento de autenticación para usuario: " . $username);
    
    // Buscar el usuario en la base de datos por username
    $stmt = $pdo->prepare("SELECT id, username, password, nombre, role FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        error_log("Usuario no encontrado: " . $username);
        echo json_encode([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ]);
        exit();
    }
    
    error_log("Verificando contraseña para: " . $username);
    error_log("Hash almacenado: " . $usuario['password']);
    
    // Verificar la contraseña
    if (!password_verify($password, $usuario['password'])) {
        error_log("Contraseña incorrecta para: " . $username);
        echo json_encode([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ]);
        exit();
    }
    
    error_log("Autenticación exitosa para: " . $username);
    
    // Generar un token simple (en un sistema real, se usaría JWT u otra solución más robusta)
    $token = bin2hex(random_bytes(32));
    
    // Eliminar la contraseña de la información del usuario
    unset($usuario['password']);
    
    // Agregar propiedad isAdmin basada en el role
    $usuario['isAdmin'] = ($usuario['role'] === 'admin');
    
    // Devolver la respuesta
    echo json_encode([
        'success' => true,
        'message' => 'Autenticación exitosa',
        'user' => $usuario,
        'token' => $token
    ]);
    
} catch (PDOException $e) {
    // Registrar el error para debugging
    error_log("Error en autenticación: " . $e->getMessage());
    
    // Enviar respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud'
    ]);
}