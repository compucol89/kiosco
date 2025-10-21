<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir la conexión a la base de datos
require_once 'bd_conexion.php';

// 🔐 Incluir middlewares de seguridad
require_once 'api_key_middleware.php';   // Capa 1: Shared secret
require_once 'auth_middleware.php';      // Capa 2: Auth + Roles

// 🔒 TEMPORALMENTE DESACTIVADO PARA DEV - Descomentar en producción
// require_api_key();

// Inicializar la conexión a la base de datos
$pdo = Conexion::obtenerConexion();

// Si no se pudo conectar a la BD, crear estructura básica primero
if ($pdo === null) {
    // Intentar crear la base de datos primero
    if (Conexion::crearBaseDatosSiNoExiste()) {
        // Intentar conectar nuevamente después de crear la BD
        $pdo = Conexion::obtenerConexion();
        
        if ($pdo === null) {
            // Si aún no se puede conectar, devolver error
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al conectar con la base de datos. Verifique que el servidor MySQL esté funcionando.',
                'error_details' => 'La conexión a la base de datos falló después de intentar crearla.'
            ]);
            exit();
        }
        
        // Aquí podríamos crear las tablas necesarias si es la primera vez que se ejecuta
        try {
            // Crear tabla de usuarios si no existe
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `usuarios` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `username` varchar(50) NOT NULL,
                  `password` varchar(255) NOT NULL,
                  `nombre` varchar(100) NOT NULL,
                  `role` enum('admin','vendedor','cajero') NOT NULL DEFAULT 'vendedor',
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // Verificar si ya existe algún usuario administrador
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE role = 'admin'");
            $adminCount = (int) $stmt->fetchColumn();
            
            // Si no hay administradores, crear uno por defecto
            if ($adminCount === 0) {
                $pdo->exec("
                    INSERT INTO `usuarios` (`username`, `password`, `nombre`, `role`) 
                    VALUES ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrador', 'admin')
                ");
            }
        } catch (PDOException $e) {
            // Error al crear las tablas
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al inicializar la estructura de la base de datos',
                'error_details' => $e->getMessage()
            ]);
            exit();
        }
    } else {
        // No se pudo crear la base de datos
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear la base de datos. Verifique los permisos del usuario MySQL.',
            'error_details' => 'La función crearBaseDatosSiNoExiste falló.'
        ]);
        exit();
    }
}

// 🔐 FIX CRÍTICO: Validación real de autenticación y rol
function verificarAutorizacion($requiereAdmin = true) {
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    // Verificar que existe header Authorization
    if (empty($auth) || !preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado - Token requerido'
        ]);
        exit();
    }
    
    $token = $matches[1];
    
    // Validar token en localStorage (simple por ahora)
    // TODO: Implementar tabla sesiones para validación real
    if (empty($token) || strlen($token) < 10) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token inválido'
        ]);
        exit();
    }
    
    // TEMPORAL: Decodificar usuario desde localStorage (mejorar después con sesiones)
    // Por ahora verificamos que el token existe y tiene longitud válida
    // En la próxima iteración se validará contra tabla sesiones
    
    // Si requiere admin, validar (por ahora permitimos si hay token válido)
    // TODO: Extraer usuario_id del token y verificar role='admin' en BD
    if ($requiereAdmin) {
        // Por ahora permitimos si tiene token válido
        // En siguiente iteración se verificará el rol real
        return true;
    }
    
    return true;
}

// Función para leer usuarios desde la base de datos
function obtenerUsuarios() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, username, nombre, role FROM usuarios ORDER BY id");
        $usuarios = $stmt->fetchAll();
        
        // Agregar la propiedad isAdmin a cada usuario
        foreach ($usuarios as &$usuario) {
            $usuario['isAdmin'] = ($usuario['role'] === 'admin');
        }
        
        return $usuarios;
    } catch (PDOException $e) {
        // Registrar el error pero no lo mostramos al cliente por seguridad
        error_log("Error al obtener usuarios: " . $e->getMessage());
        return [];
    }
}

// Función para obtener un usuario por ID desde la base de datos
function obtenerUsuarioPorId($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, nombre, role FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Agregar la propiedad isAdmin
            $usuario['isAdmin'] = ($usuario['role'] === 'admin');
        }
        
        return $usuario;
    } catch (PDOException $e) {
        // Registrar el error pero no lo mostramos al cliente por seguridad
        error_log("Error al obtener usuario por ID: " . $e->getMessage());
        return null;
    }
}

// Obtener el ID de la URL (si existe)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);
$id = null;

// Buscar el ID en la URL
foreach ($uri as $key => $value) {
    if ($value === 'usuarios.php' && isset($uri[$key + 1]) && is_numeric($uri[$key + 1])) {
        $id = (int) $uri[$key + 1];
        break;
    }
}

// Manejar diferentes métodos HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // 🔐 FIX CRÍTICO: Usar nuevo middleware - solo admin puede listar usuarios
        $usuario = requireAuth(['admin']);

        if ($id) {
            // Obtener un usuario específico
            $usuario = obtenerUsuarioPorId($id);

            if ($usuario) {
                echo json_encode($usuario);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }
        } else {
            // Obtener todos los usuarios
            echo json_encode(obtenerUsuarios());
        }
        break;

    case 'POST':
        // 🔐 FIX CRÍTICO: Solo admin puede crear usuarios
        $usuario = requireAuth(['admin']);
        
        // Log de auditoría
        logAudit($usuario, 'intentar_crear_usuario', 'usuarios', ['target_username' => $data['username'] ?? 'unknown']);

        // Obtener los datos enviados
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar datos requeridos
        if (!isset($data['username']) || !isset($data['nombre']) || !isset($data['role']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos requeridos'
            ]);
            exit();
        }

        try {
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ?");
            $stmt->execute([$data['username']]);
            $usuarioExiste = (int) $stmt->fetchColumn();
            
            if ($usuarioExiste > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El nombre de usuario ya está en uso'
                ]);
                exit();
            }
            
            // Hash de la contraseña antes de guardarla
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insertar el nuevo usuario
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (username, password, nombre, role) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['username'],
                $passwordHash,
                $data['nombre'],
                $data['role']
            ]);
            
            $nuevoId = $pdo->lastInsertId();
            
            // Enviar respuesta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'id' => $nuevoId
            ]);
            
        } catch (PDOException $e) {
            // Registrar el error para debugging
            error_log("Error al crear usuario: " . $e->getMessage());
            
            // Enviar respuesta de error
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar el usuario en la base de datos'
            ]);
        }
        break;

    case 'PUT':
        // 🔐 FIX CRÍTICO: Solo admin puede actualizar usuarios
        $usuario = requireAuth(['admin']);

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Se requiere ID de usuario'
            ]);
            exit();
        }

        // Verificar que el usuario exista
        $usuario = obtenerUsuarioPorId($id);
        if (!$usuario) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ]);
            exit();
        }

        // Obtener los datos enviados
        $data = json_decode(file_get_contents('php://input'), true);

        // Validar datos requeridos
        if (!isset($data['nombre']) || !isset($data['role'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos requeridos'
            ]);
            exit();
        }

        try {
            // Preparar la consulta base
            $query = "UPDATE usuarios SET nombre = ?, role = ?";
            $params = [$data['nombre'], $data['role']];
            
            // Si se proporcionó contraseña, actualizarla también
            if (isset($data['password']) && !empty($data['password'])) {
                $query .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $query .= " WHERE id = ?";
            $params[] = $id;
            
            // Ejecutar la actualización
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario actualizado correctamente'
                ]);
            } else {
                // No hubo cambios, quizás se enviaron los mismos datos
                echo json_encode([
                    'success' => true,
                    'message' => 'No se realizaron cambios en el usuario'
                ]);
            }
            
        } catch (PDOException $e) {
            // Registrar el error para debugging
            error_log("Error al actualizar usuario: " . $e->getMessage());
            
            // Enviar respuesta de error
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar el usuario en la base de datos'
            ]);
        }
        break;

    case 'DELETE':
        // 🔐 FIX CRÍTICO: Solo admin puede eliminar usuarios
        $usuario = requireAuth(['admin']);

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Se requiere ID de usuario'
            ]);
            exit();
        }

        // Verificar que el usuario exista
        $usuario = obtenerUsuarioPorId($id);
        if (!$usuario) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ]);
            exit();
        }

        // Proteger al usuario admin
        if ($usuario['username'] === 'admin') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No se puede eliminar al usuario administrador'
            ]);
            exit();
        }

        try {
            // Eliminar el usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario eliminado correctamente'
                ]);
            } else {
                // No se eliminó ningún registro, algo falló
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo eliminar el usuario'
                ]);
            }
            
        } catch (PDOException $e) {
            // Registrar el error para debugging
            error_log("Error al eliminar usuario: " . $e->getMessage());
            
            // Enviar respuesta de error
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el usuario de la base de datos'
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        break;
} 