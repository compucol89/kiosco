<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir la conexión a la base de datos
require_once 'bd_conexion.php';

// Inicializar la conexión a la base de datos
$pdo = Conexion::obtenerConexion();

// Si no se pudo conectar a la BD, devolver error
if ($pdo === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectar con la base de datos.'
    ]);
    exit();
}

// Crear tabla de configuración si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `configuracion` (
          `clave` varchar(50) NOT NULL,
          `valor` text NOT NULL,
          `descripcion` varchar(255) DEFAULT NULL,
          `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`clave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Verificar si hay configuraciones predeterminadas, si no hay, insertar algunas básicas
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracion");
    $count = (int) $stmt->fetchColumn();
    
    if ($count === 0) {
        $defaultConfig = [
            ['modo_mantenimiento', '0', 'Activa/desactiva el modo mantenimiento del sistema'],
            ['nombre_negocio', 'Mi Negocio', 'Nombre del negocio mostrado en tickets y reportes'],
            ['direccion_negocio', 'Dirección del negocio', 'Dirección mostrada en tickets y reportes'],
            ['telefono_negocio', '123-456-7890', 'Teléfono de contacto'],
            ['mensaje_pie_ticket', 'Gracias por su compra!', 'Mensaje que aparece al pie de los tickets'],
            ['impresion_automatica', '1', 'Activar/desactivar impresión automática de tickets'],
            ['moneda', 'ARS', 'Moneda utilizada en el sistema'],
            ['descuento_efectivo', '10', 'Descuento aplicado al pago en efectivo (%)'],
            ['descuento_transferencia', '10', 'Descuento aplicado al pago por transferencia (%)'],
            ['descuento_tarjeta', '0', 'Descuento aplicado al pago con tarjeta (%)'],
            ['descuento_mercadopago', '0', 'Descuento aplicado al pago con MercadoPago (%)'],
            ['descuento_qr', '0', 'Descuento aplicado al pago con QR (%)'],
            ['descuento_otros', '0', 'Descuento aplicado a otros métodos de pago (%)']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)");
        
        foreach ($defaultConfig as $config) {
            $stmt->execute($config);
        }
    }
} catch (PDOException $e) {
    error_log("Error al crear tabla de configuración: " . $e->getMessage());
    // Continuar con la ejecución, ya que no es un error crítico
}

// Manejar diferentes métodos HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Obtener todas las configuraciones
        try {
            $stmt = $pdo->query("SELECT clave, valor, descripcion FROM configuracion");
            $configuraciones = [];
            
            while ($row = $stmt->fetch()) {
                $configuraciones[$row['clave']] = $row['valor'];
            }
            
            echo json_encode($configuraciones);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener la configuración'
            ]);
        }
        break;

    case 'POST':
        // Actualizar una configuración específica
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['clave']) || !isset($data['valor'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos requeridos (clave, valor)'
            ]);
            exit();
        }
        
        $clave = $data['clave'];
        $valor = $data['valor'];
        
        try {
            // Verificar si la clave existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM configuracion WHERE clave = ?");
            $stmt->execute([$clave]);
            $existe = (int) $stmt->fetchColumn() > 0;
            
            if ($existe) {
                // Actualizar valor
                $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
                $stmt->execute([$valor, $clave]);
            } else {
                // Insertar nueva configuración
                $descripcion = isset($data['descripcion']) ? $data['descripcion'] : '';
                $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)");
                $stmt->execute([$clave, $valor, $descripcion]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar la configuración'
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
?> 