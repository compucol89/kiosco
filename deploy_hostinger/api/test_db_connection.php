<?php
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Test 1: Conexión básica MySQL
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    echo json_encode([
        'test' => 'mysql_connection',
        'step' => 'connecting',
        'host' => $host,
        'user' => $username
    ]) . "\n";
    
    $pdo_test = new PDO("mysql:host=$host", $username, $password);
    $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'test' => 'mysql_connection',
        'status' => 'success',
        'message' => 'MySQL server accessible'
    ]) . "\n";
    
    // Test 2: Base de datos específica
    $db_name = 'kiosco_db';
    $pdo_db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'test' => 'database_connection',
        'status' => 'success',
        'database' => $db_name,
        'message' => 'Database kiosco_db accessible'
    ]) . "\n";
    
    // Test 3: Tabla turnos_caja
    $stmt = $pdo_db->query("SHOW TABLES LIKE 'turnos_caja'");
    $table_exists = $stmt->rowCount() > 0;
    
    echo json_encode([
        'test' => 'table_check',
        'table' => 'turnos_caja',
        'exists' => $table_exists,
        'status' => $table_exists ? 'success' : 'error',
        'message' => $table_exists ? 'Table turnos_caja exists' : 'Table turnos_caja NOT FOUND'
    ]) . "\n";
    
    if ($table_exists) {
        // Test 4: Consulta de turnos activos
        $stmt = $pdo_db->query("SELECT COUNT(*) as count FROM turnos_caja WHERE estado = 'abierto'");
        $active_turns = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'test' => 'active_turns',
            'count' => $active_turns,
            'status' => 'success',
            'message' => "Found $active_turns active turns"
        ]) . "\n";
    }
    
    echo json_encode([
        'final_status' => 'ALL_TESTS_PASSED',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'test' => 'database_error',
        'status' => 'error',
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
        'suggestion' => 'Check if LARAGON MySQL service is running'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'test' => 'general_error',
        'status' => 'error',
        'error_message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
}
?>
