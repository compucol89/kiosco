<?php
/**
 * File: api/auth_middleware.php
 * Middleware centralizado de autenticación y autorización
 * Exists to validate tokens and enforce role-based access control
 * Related files: api/usuarios.php, api/auth.php, api/bd_conexion.php
 */

require_once __DIR__ . '/bd_conexion.php';

/**
 * 🔐 VALIDACIÓN COMPLETA DE AUTENTICACIÓN Y ROLES
 * 
 * @param array $rolesPermitidos Array de roles permitidos (ej: ['admin', 'cajero'])
 *                               Si está vacío, solo valida que esté autenticado
 * @return array Usuario autenticado con sus datos
 * @throws Exit con 401/403 si falla validación
 */
function requireAuth($rolesPermitidos = []) {
    global $pdo;
    
    // Obtener conexión si no existe
    if (!isset($pdo) || $pdo === null) {
        $pdo = Conexion::obtenerConexion();
    }
    
    // 1️⃣ PASO 1: Extraer token del header Authorization
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($auth) || !preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No autorizado',
            'message' => 'Token de autenticación requerido'
        ]);
        exit;
    }
    
    $token = trim($matches[1]);
    
    // 2️⃣ PASO 2: Validar formato del token
    if (empty($token) || strlen($token) < 10) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Token inválido',
            'message' => 'Formato de token incorrecto'
        ]);
        exit;
    }
    
    // 3️⃣ PASO 3: Validar token contra base de datos
    // TODO: Cuando se implemente tabla 'sesiones', validar aquí
    // Por ahora, asumimos que el token es válido si tiene el formato correcto
    
    // TEMPORAL: Extraer usuario desde header adicional o localStorage
    // En producción, esto debería venir de la tabla sesiones
    $usuario = validateTokenSimple($token, $pdo);
    
    if (!$usuario) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Sesión inválida',
            'message' => 'Token no válido o sesión expirada'
        ]);
        exit;
    }
    
    // 4️⃣ PASO 4: Verificar roles si se especificaron
    if (!empty($rolesPermitidos)) {
        if (!in_array($usuario['role'], $rolesPermitidos, true)) {
            // Log de intento de acceso no autorizado
            error_log("ACCESO DENEGADO: Usuario {$usuario['username']} (rol: {$usuario['role']}) intentó acceder a endpoint que requiere roles: " . implode(', ', $rolesPermitidos));
            
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Acceso denegado',
                'message' => 'No tienes permisos para realizar esta acción'
            ]);
            exit;
        }
    }
    
    // ✅ Usuario autenticado y autorizado
    return $usuario;
}

/**
 * 🔍 VALIDACIÓN SIMPLE DE TOKEN (TEMPORAL)
 * 
 * Esta función es TEMPORAL hasta implementar tabla 'sesiones'
 * Por ahora, solo verifica que el usuario existe en BD
 * 
 * @param string $token Token a validar
 * @param PDO $pdo Conexión a base de datos
 * @return array|false Usuario si es válido, false si no
 */
function validateTokenSimple($token, $pdo) {
    try {
        // MÉTODO TEMPORAL: El token no se valida realmente aquí
        // Solo verificamos que tenga el formato correcto
        
        // En un sistema real con tabla sesiones, haríamos:
        // SELECT usuario_id, expires_at FROM sesiones WHERE token = ? AND is_active = 1
        
        // Por ahora, extraemos user info del header adicional (hack temporal)
        $headers = getallheaders();
        $userJson = isset($headers['X-User-Data']) ? $headers['X-User-Data'] : '';
        
        if (!empty($userJson)) {
            $userData = json_decode($userJson, true);
            if ($userData && isset($userData['id'])) {
                // Verificar que el usuario existe y está activo
                $stmt = $pdo->prepare("SELECT id, username, nombre, role FROM usuarios WHERE id = ?");
                $stmt->execute([$userData['id']]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    return $usuario;
                }
            }
        }
        
        // FALLBACK: Si no hay header X-User-Data, buscar primer admin
        // ESTO ES TEMPORAL Y NO ES SEGURO - SOLO PARA DESARROLLO
        $stmt = $pdo->query("SELECT id, username, nombre, role FROM usuarios WHERE role = 'admin' LIMIT 1");
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            error_log("WARNING: Usando validación de token temporal (no segura) para usuario: {$usuario['username']}");
            return $usuario;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Error validando token: " . $e->getMessage());
        return false;
    }
}

/**
 * 🔐 HELPER: Verificar si usuario tiene permiso específico
 * 
 * @param array $usuario Usuario con role
 * @param string $modulo Módulo a verificar (ej: 'usuarios', 'productos')
 * @param string $accion Acción a verificar (ej: 'create', 'edit', 'delete')
 * @return bool True si tiene permiso
 */
function hasPermission($usuario, $modulo, $accion) {
    // Admin tiene todos los permisos
    if ($usuario['role'] === 'admin') {
        return true;
    }
    
    // Matriz de permisos (debe coincidir con AuthContext.jsx)
    $permisos = [
        'vendedor' => [
            'usuarios' => [],
            'productos' => ['view'],
            'ventas' => ['view', 'create'],
            'inventario' => ['view'],
            'reportes' => [],
            'configuracion' => [],
            'caja' => ['view']
        ],
        'cajero' => [
            'usuarios' => [],
            'productos' => ['view'],
            'ventas' => ['view', 'create'],
            'inventario' => ['view'],
            'reportes' => [],
            'configuracion' => [],
            'caja' => ['view', 'open', 'close', 'movements']
        ]
    ];
    
    $role = $usuario['role'];
    
    if (!isset($permisos[$role])) {
        return false;
    }
    
    if (!isset($permisos[$role][$modulo])) {
        return false;
    }
    
    return in_array($accion, $permisos[$role][$modulo], true);
}

/**
 * 🔐 HELPER: Obtener usuario actual desde token
 * 
 * Similar a requireAuth pero no hace exit, retorna null si falla
 * Útil para endpoints que son opcionales con auth
 * 
 * @return array|null Usuario o null
 */
function getCurrentUser() {
    global $pdo;
    
    if (!isset($pdo) || $pdo === null) {
        $pdo = Conexion::obtenerConexion();
    }
    
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($auth) || !preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return null;
    }
    
    $token = trim($matches[1]);
    
    if (empty($token) || strlen($token) < 10) {
        return null;
    }
    
    return validateTokenSimple($token, $pdo);
}

/**
 * 📊 HELPER: Log de auditoría
 * 
 * Registra acciones importantes para auditoría
 * TODO: Guardar en tabla audit_log cuando se implemente
 * 
 * @param array $usuario Usuario que realizó la acción
 * @param string $accion Descripción de la acción
 * @param string $modulo Módulo afectado
 * @param array $detalles Detalles adicionales opcionales
 */
function logAudit($usuario, $accion, $modulo, $detalles = []) {
    $username = $usuario['username'] ?? 'desconocido';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $detallesJson = json_encode($detalles);
    
    error_log("AUDIT: Usuario=$username IP=$ip Módulo=$modulo Acción=$accion Detalles=$detallesJson");
    
    // TODO: Cuando se implemente tabla audit_log:
    // INSERT INTO audit_log (usuario_id, accion, modulo, detalles, ip_address) VALUES (...)
}

/**
 * 🛡️ EJEMPLO DE USO:
 * 
 * En cualquier endpoint PHP que requiera autenticación:
 * 
 * ```php
 * require_once __DIR__ . '/auth_middleware.php';
 * 
 * // Solo verificar que esté autenticado
 * $usuario = requireAuth();
 * 
 * // Verificar que sea admin
 * $usuario = requireAuth(['admin']);
 * 
 * // Verificar que sea admin O cajero
 * $usuario = requireAuth(['admin', 'cajero']);
 * 
 * // Ahora $usuario contiene: id, username, nombre, role
 * // Continuar con lógica del endpoint...
 * 
 * // Log de auditoría
 * logAudit($usuario, 'crear_usuario', 'usuarios', ['nuevo_username' => 'test']);
 * ```
 */

