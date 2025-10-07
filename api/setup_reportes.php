<?php
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    
    if (!$pdo) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }
    
    // Añadir columna precio_compra si no existe
    try {
        $pdo->exec("ALTER TABLE productos ADD COLUMN precio_compra DECIMAL(10,2) DEFAULT 0 AFTER precio_venta");
    } catch (Exception $e) {
        // La columna ya existe, no hacer nada
    }
    
    // Crear tabla de ingresos extra
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingresos_extra (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo ENUM('prestamo', 'inversion', 'venta-activo', 'otro') NOT NULL,
            concepto VARCHAR(255) NOT NULL,
            monto DECIMAL(15,2) NOT NULL,
            fecha DATE NOT NULL,
            descripcion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Crear tabla de egresos/gastos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS egresos_gastos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo ENUM('gasto', 'pago-prestamo', 'compra-activo', 'retiro-socio', 'otro') NOT NULL,
            concepto VARCHAR(255) NOT NULL,
            monto DECIMAL(15,2) NOT NULL,
            fecha DATE NOT NULL,
            descripcion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Actualizar productos existentes con precio_compra estimado
    $pdo->exec("
        UPDATE productos 
        SET precio_compra = ROUND(precio_venta * 0.6, 2) 
        WHERE precio_compra = 0 OR precio_compra IS NULL
    ");
    
    // Verificar si ya existen datos de ejemplo
    $stmt = $pdo->query("SELECT COUNT(*) FROM ingresos_extra");
    $ingresos_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM egresos_gastos");
    $egresos_count = $stmt->fetchColumn();
    
    // Insertar datos de ejemplo si no existen
    if ($ingresos_count == 0) {
        $pdo->exec("
            INSERT INTO ingresos_extra (tipo, concepto, monto, fecha, descripcion) VALUES
            ('prestamo', 'Préstamo inicial', 100000.00, CURDATE(), 'Capital de trabajo inicial'),
            ('inversion', 'Inversión socio', 50000.00, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Ampliación de inventario')
        ");
    }
    
    if ($egresos_count == 0) {
        $pdo->exec("
            INSERT INTO egresos_gastos (tipo, concepto, monto, fecha, descripcion) VALUES
            ('gasto', 'Alquiler local', 80000.00, CURDATE(), 'Alquiler mensual del local'),
            ('gasto', 'Servicios públicos', 25000.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Electricidad y gas'),
            ('gasto', 'Compra de estantería', 45000.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Equipamiento del local')
        ");
    }
    
    // Verificar resultados
    $stmt = $pdo->query("SELECT COUNT(*) FROM ingresos_extra");
    $total_ingresos = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM egresos_gastos");
    $total_egresos = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE precio_compra > 0");
    $productos_con_costo = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sistema de reportes configurado correctamente',
        'datos' => [
            'total_ingresos' => $total_ingresos,
            'total_egresos' => $total_egresos,
            'productos_con_costo' => $productos_con_costo,
            'tablas_creadas' => [
                'ingresos_extra' => true,
                'egresos_gastos' => true,
                'productos_actualizado' => true
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en setup_reportes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error configurando sistema de reportes: ' . $e->getMessage()
    ]);
}
?> 