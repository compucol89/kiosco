<?php
/**
 * 🔐 SISTEMA DE PERMISOS DE USUARIO - ACTUALIZADO
 * 
 * Gestión de permisos granulares por rol para módulos del sistema POS
 * 
 * ACTUALIZACIONES REALIZADAS:
 * ✅ Módulos actualizados para coincidir con sistema actual
 * ✅ Eliminados módulos obsoletos (Vencimientos, Pedidos, Proveedores, etc.)
 * ✅ Agregado módulo "GastosFijos" faltante
 * ✅ Permisos por defecto optimizados por rol
 * ✅ Auto-limpieza de módulos obsoletos en BD
 * ✅ Auto-agregado de módulos nuevos
 * 
 * MÓDULOS ACTUALES (10):
 * - Inicio, PuntoDeVenta, Ventas, ControlCaja, Inventario
 * - Productos, Reportes, GastosFijos, Usuarios, Configuracion
 * 
 * @version 2.0.0-updated
 * @author Sistema POS Empresarial
 * @date 31 Enero 2025
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            obtenerPermisos();
            break;
        case 'POST':
            actualizarPermisos();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en permisos_usuario.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

function obtenerPermisos() {
    try {
        // Obtener conexión a la base de datos
        $pdo = Conexion::obtenerConexion();
        
        if (!$pdo) {
            throw new Exception('No se pudo establecer conexión a la base de datos');
        }
        
        // Crear tabla de permisos si no existe
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS permisos_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rol VARCHAR(50) NOT NULL,
                modulo VARCHAR(100) NOT NULL,
                acceso BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_rol_modulo (rol, modulo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($createTableSQL);
        
        // Definir permisos por defecto actualizados
        $permisosDefecto = [
            'admin' => [
                'Inicio' => true,
                'PuntoDeVenta' => true,
                'Ventas' => true,
                'ControlCaja' => true,
                'Inventario' => true,
                'Productos' => true,
                'Reportes' => true,
                'GastosFijos' => true,
                'Usuarios' => true,
                'Configuracion' => true
            ],
            'vendedor' => [
                'Inicio' => true,
                'PuntoDeVenta' => true,
                'Ventas' => true,
                'ControlCaja' => false,
                'Inventario' => true,
                'Productos' => true,
                'Reportes' => false,
                'GastosFijos' => false,
                'Usuarios' => false,
                'Configuracion' => false
            ],
            'cajero' => [
                'Inicio' => true,
                'PuntoDeVenta' => true,
                'Ventas' => true,
                'ControlCaja' => true,
                'Inventario' => true,
                'Productos' => false,
                'Reportes' => false,
                'GastosFijos' => false,
                'Usuarios' => false,
                'Configuracion' => false
            ]
        ];
        
        // Verificar si ya existen permisos en la base de datos
        $stmt = $pdo->query("SELECT COUNT(*) FROM permisos_roles");
        $count = $stmt->fetchColumn();
        
        // Lista de módulos actuales válidos
        $modulosActuales = array_keys($permisosDefecto['admin']);
        
        // Si no hay permisos, insertar los por defecto
        if ($count == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO permisos_roles (rol, modulo, acceso) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE acceso = VALUES(acceso)
            ");
            
            foreach ($permisosDefecto as $rol => $modulos) {
                foreach ($modulos as $modulo => $acceso) {
                    $stmt->execute([$rol, $modulo, $acceso]);
                }
            }
        } else {
            // Limpiar módulos obsoletos que ya no existen
            $placeholders = str_repeat('?,', count($modulosActuales) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM permisos_roles WHERE modulo NOT IN ($placeholders)");
            $stmt->execute($modulosActuales);
            
            // Agregar módulos nuevos que falten para cada rol
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO permisos_roles (rol, modulo, acceso) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($permisosDefecto as $rol => $modulos) {
                foreach ($modulos as $modulo => $acceso) {
                    // Verificar si el permiso ya existe
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM permisos_roles WHERE rol = ? AND modulo = ?");
                    $checkStmt->execute([$rol, $modulo]);
                    
                    // Si no existe, agregarlo con el valor por defecto
                    if ($checkStmt->fetchColumn() == 0) {
                        $stmt->execute([$rol, $modulo, $acceso]);
                    }
                }
            }
        }
        
        // Obtener todos los permisos de la base de datos
        $stmt = $pdo->query("SELECT rol, modulo, acceso FROM permisos_roles ORDER BY rol, modulo");
        $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar permisos por rol
        $permisosOrganizados = [];
        foreach ($permisos as $permiso) {
            $permisosOrganizados[$permiso['rol']][$permiso['modulo']] = (bool)$permiso['acceso'];
        }
        
        // Obtener información de módulos disponibles (actualizados)
        $modulosDisponibles = [
            'Inicio' => ['nombre' => 'Panel de Control', 'descripcion' => 'Dashboard principal del sistema'],
            'PuntoDeVenta' => ['nombre' => 'Punto de Ventas', 'descripcion' => 'Interface de ventas profesional'],
            'Ventas' => ['nombre' => 'Reporte Ventas', 'descripcion' => 'Historial y reportes de ventas'],
            'ControlCaja' => ['nombre' => 'Control de Caja', 'descripcion' => 'Apertura, cierre y gestión de caja'],
            'Inventario' => ['nombre' => 'Inventario', 'descripcion' => 'Gestión inteligente de inventario'],
            'Productos' => ['nombre' => 'Productos', 'descripcion' => 'CRUD de productos y categorías'],
            'Reportes' => ['nombre' => 'Reportes Financieros', 'descripcion' => 'Reportes contables y análisis financiero'],
            'GastosFijos' => ['nombre' => 'Gastos Fijos', 'descripcion' => 'Gestión de gastos fijos mensuales'],
            'Usuarios' => ['nombre' => 'Usuarios', 'descripcion' => 'Gestión de usuarios del sistema'],
            'Configuracion' => ['nombre' => 'Configuración', 'descripcion' => 'Configuración del sistema']
        ];
        
        echo json_encode([
            'success' => true,
            'permisos' => $permisosOrganizados,
            'modulos' => $modulosDisponibles,
            'roles' => ['admin', 'vendedor', 'cajero']
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo permisos: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al obtener permisos']);
    }
}

function actualizarPermisos() {
    try {
        // Obtener conexión a la base de datos
        $pdo = Conexion::obtenerConexion();
        
        if (!$pdo) {
            throw new Exception('No se pudo establecer conexión a la base de datos');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['permisos'])) {
            echo json_encode(['success' => false, 'message' => 'Datos de permisos requeridos']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Eliminar permisos existentes para actualizar
        $pdo->exec("DELETE FROM permisos_roles");
        
        // Insertar nuevos permisos
        $stmt = $pdo->prepare("
            INSERT INTO permisos_roles (rol, modulo, acceso) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($input['permisos'] as $rol => $modulos) {
            foreach ($modulos as $modulo => $acceso) {
                $stmt->execute([$rol, $modulo, $acceso ? 1 : 0]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Permisos actualizados correctamente'
        ]);
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log("Error actualizando permisos: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al actualizar permisos']);
    }
}
?> 