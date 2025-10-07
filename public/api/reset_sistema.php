<?php
// Iniciar sesión para verificación
session_start();

// Encabezados para CORS y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Responder a las solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => true, "mensaje" => "Método no permitido"]);
    exit;
}

// Obtener datos de la solicitud
$datos = json_decode(file_get_contents("php://input"), true);

// Verificar clave de confirmación
if (!isset($datos['clave_confirmacion']) || $datos['clave_confirmacion'] !== 'REINICIAR_SISTEMA_CONFIRMAR') {
    http_response_code(403);
    echo json_encode(["error" => true, "mensaje" => "Clave de confirmación inválida"]);
    exit;
}

// Verificar ID de usuario
if (!isset($datos['usuario_id']) || empty($datos['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["error" => true, "mensaje" => "ID de usuario no proporcionado"]);
    exit;
}

// Incluir archivo de configuración y conexión a la base de datos
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Verificar si el usuario es administrador
try {
    $query = "SELECT is_admin FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $datos['usuario_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => true, "mensaje" => "Usuario no encontrado"]);
        exit;
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row['is_admin']) {
        http_response_code(403);
        echo json_encode(["error" => true, "mensaje" => "Acceso denegado. Se requieren privilegios de administrador"]);
        exit;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => "Error al verificar permisos: " . $e->getMessage()
    ]);
    exit;
}

// Función para registrar el evento
function registrarEvento($db, $usuario_id, $tipo, $descripcion) {
    try {
        $query = "INSERT INTO logs (usuario_id, tipo, descripcion, fecha) VALUES (:usuario_id, :tipo, :descripcion, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":descripcion", $descripcion);
        $stmt->execute();
    } catch (PDOException $e) {
        // Simplemente registramos el error pero no detenemos el proceso
        error_log("Error al registrar evento: " . $e->getMessage());
    }
}

// Lista de tablas a reiniciar (todas excepto usuarios, configuracion y logs)
$tablasNoReiniciar = ['usuarios', 'configuracion', 'logs'];

// Obtener todas las tablas de la base de datos
try {
    $query = "SHOW TABLES";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => "Error al obtener tablas: " . $e->getMessage()
    ]);
    exit;
}

// Comenzar transacción
$db->beginTransaction();

try {
    // Deshabilitar verificación de claves externas temporalmente
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $resultados = [];
    
    // Vaciar cada tabla, excepto las protegidas
    foreach ($tablas as $tabla) {
        if (!in_array($tabla, $tablasNoReiniciar)) {
            // Truncar la tabla
            $stmt = $db->prepare("TRUNCATE TABLE `$tabla`");
            $stmt->execute();
            
            $resultados[] = [
                "tabla" => $tabla,
                "estado" => "reiniciada"
            ];
        }
    }
    
    // Restablecer verificación de claves externas
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Confirmar cambios
    $db->commit();
    
    // Registrar el evento
    registrarEvento(
        $db, 
        $datos['usuario_id'], 
        'sistema', 
        'Reinicio completo del sistema. Se vaciaron ' . count($resultados) . ' tablas.'
    );
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "error" => false,
        "mensaje" => "El sistema ha sido reiniciado correctamente",
        "fecha_reinicio" => date("Y-m-d H:i:s"),
        "usuario_id" => $datos['usuario_id'],
        "resultados" => $resultados
    ]);
    
} catch (PDOException $e) {
    // Revertir cambios en caso de error
    $db->rollBack();
    
    // Registrar el error
    registrarEvento(
        $db, 
        $datos['usuario_id'], 
        'error', 
        'Error al reiniciar sistema: ' . $e->getMessage()
    );
    
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => "Error al reiniciar el sistema: " . $e->getMessage()
    ]);
} 