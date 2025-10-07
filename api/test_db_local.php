<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'test' => 'Iniciando test de conexión',
    'timestamp' => date('Y-m-d H:i:s')
]) . "\n\n";

try {
    // Test 1: Conexión directa sin BD
    echo "Test 1: Conectar a MySQL sin especificar BD...\n";
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "✅ MySQL está corriendo\n\n";
    
    // Test 2: Listar bases de datos
    echo "Test 2: Bases de datos disponibles:\n";
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($databases, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Conectar a BD kiosco_db
    echo "Test 3: Intentar conectar a BD 'kiosco_db'...\n";
    if (in_array('kiosco_db', $databases)) {
        $pdo_kiosco = new PDO('mysql:host=localhost;port=3306;dbname=kiosco_db;charset=utf8mb4', 'root', '');
        echo "✅ Conexión a BD 'kiosco_db' exitosa\n\n";
        
        // Test 4: Verificar tabla usuarios
        echo "Test 4: Verificar tabla usuarios...\n";
        $stmt = $pdo_kiosco->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla 'usuarios' existe\n";
            
            // Contar usuarios
            $stmt = $pdo_kiosco->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch();
            echo "📊 Total usuarios: " . $result['total'] . "\n";
        } else {
            echo "❌ Tabla 'usuarios' NO existe\n";
        }
    } else {
        echo "❌ BD 'kiosco_db' NO existe\n";
        echo "💡 Necesitas crear la base de datos 'kiosco_db' en Laragon\n";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test completado'
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
