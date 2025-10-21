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

// 🔒 FIX MEDIO: Rate limiting simple para prevenir fuerza bruta
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitResult = checkRateLimit($ip, $username);

if (!$rateLimitResult['allowed']) {
    http_response_code(429); // Too Many Requests
    echo json_encode([
        'success' => false,
        'message' => 'Demasiados intentos de login. Intenta nuevamente en ' . $rateLimitResult['wait_minutes'] . ' minutos',
        'retry_after' => $rateLimitResult['wait_minutes']
    ]);
    exit();
}

try {
    // Registro para debugging
    error_log("Intento de autenticación para usuario: " . $username . " desde IP: " . $ip);
    
    // Buscar el usuario en la base de datos por username
    $stmt = $pdo->prepare("SELECT id, username, password, nombre, role FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        // 🔒 FIX SEGURIDAD: No loguear detalles específicos de por qué falló
        error_log("Intento de login fallido para usuario: " . $username . " (usuario no encontrado) desde IP: " . $ip);
        
        // 🔒 Registrar intento fallido para rate limiting
        registerFailedAttempt($ip, $username);
        
        echo json_encode([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ]);
        exit();
    }

// 🔐 FIX CRÍTICO: REMOVIDO log de hash de contraseña (seguridad)
// Verificar la contraseña
if (!password_verify($password, $usuario['password'])) {
    error_log("Intento de login fallido para usuario: " . $username . " (contraseña incorrecta) desde IP: " . $ip);
    
    // 🔒 Registrar intento fallido para rate limiting
    registerFailedAttempt($ip, $username);
    
    echo json_encode([
        'success' => false,
        'message' => 'Credenciales inválidas'
    ]);
    exit();
}

error_log("Login exitoso para usuario: " . $username . " [ID: " . $usuario['id'] . "] desde IP: " . $ip);
    
    // 🔒 Limpiar intentos fallidos previos (login exitoso)
    clearFailedAttempts($ip, $username);
    
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

// ========================================
// 🔒 FUNCIONES DE RATE LIMITING
// ========================================

/**
 * Verificar límite de intentos de login
 * 
 * @param string $ip Dirección IP del cliente
 * @param string $username Nombre de usuario intentando login
 * @return array ['allowed' => bool, 'wait_minutes' => int]
 */
function checkRateLimit($ip, $username) {
    $maxAttempts = 5;           // Máximo 5 intentos
    $windowMinutes = 15;        // En ventana de 15 minutos
    $lockoutMinutes = 15;       // Bloqueo por 15 minutos
    
    $attempts = getFailedAttempts($ip, $username, $windowMinutes);
    
    if (count($attempts) >= $maxAttempts) {
        // Calcular cuántos minutos faltan para que expire el bloqueo
        $oldestAttempt = min($attempts);
        $unlockTime = $oldestAttempt + ($windowMinutes * 60);
        $now = time();
        $waitSeconds = max(0, $unlockTime - $now);
        $waitMinutes = ceil($waitSeconds / 60);
        
        return [
            'allowed' => false,
            'wait_minutes' => $waitMinutes
        ];
    }
    
    return [
        'allowed' => true,
        'wait_minutes' => 0
    ];
}

/**
 * Obtener intentos fallidos recientes
 * 
 * @param string $ip Dirección IP
 * @param string $username Nombre de usuario
 * @param int $windowMinutes Ventana de tiempo en minutos
 * @return array Array de timestamps
 */
function getFailedAttempts($ip, $username, $windowMinutes) {
    $file = __DIR__ . '/cache/rate_limit_' . md5($ip . '_' . $username) . '.json';
    
    if (!file_exists($file)) {
        return [];
    }
    
    $data = json_decode(file_get_contents($file), true);
    if (!$data || !isset($data['attempts'])) {
        return [];
    }
    
    // Filtrar solo intentos dentro de la ventana de tiempo
    $cutoff = time() - ($windowMinutes * 60);
    $attempts = array_filter($data['attempts'], function($timestamp) use ($cutoff) {
        return $timestamp > $cutoff;
    });
    
    return array_values($attempts);
}

/**
 * Registrar intento fallido
 * 
 * @param string $ip Dirección IP
 * @param string $username Nombre de usuario
 */
function registerFailedAttempt($ip, $username) {
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $file = $cacheDir . '/rate_limit_' . md5($ip . '_' . $username) . '.json';
    
    $attempts = getFailedAttempts($ip, $username, 15);
    $attempts[] = time();
    
    $data = [
        'ip' => $ip,
        'username' => $username,
        'attempts' => $attempts,
        'last_attempt' => time()
    ];
    
    file_put_contents($file, json_encode($data));
    
    error_log("Rate Limit: Registrado intento fallido #" . count($attempts) . " para $username desde IP $ip");
}

/**
 * Limpiar intentos fallidos (después de login exitoso)
 * 
 * @param string $ip Dirección IP
 * @param string $username Nombre de usuario
 */
function clearFailedAttempts($ip, $username) {
    $file = __DIR__ . '/cache/rate_limit_' . md5($ip . '_' . $username) . '.json';
    
    if (file_exists($file)) {
        unlink($file);
        error_log("Rate Limit: Limpiados intentos fallidos para $username desde IP $ip (login exitoso)");
    }
}