<?php
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo "=== TEST DE CONEXIÓN PARA AUTH.PHP ===\n\n";

// Test 1: Incluir bd_conexion.php
echo "1. Incluyendo bd_conexion.php...\n";
require_once 'bd_conexion.php';
echo "   ✅ Archivo incluido correctamente\n\n";

// Test 2: Obtener conexión
echo "2. Intentando obtener conexión...\n";
try {
    $pdo = Conexion::obtenerConexion();
    
    if ($pdo === null) {
        echo "   ❌ obtenerConexion() retornó NULL\n";
        echo "   💡 Revisa los logs de PHP para ver el error\n";
        exit;
    }
    
    echo "   ✅ Conexión obtenida exitosamente\n\n";
    
    // Test 3: Verificar conexión con query
    echo "3. Verificando conexión con SELECT 1...\n";
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "   ✅ Query ejecutado: " . json_encode($result) . "\n\n";
    
    // Test 4: Verificar tabla usuarios
    echo "4. Verificando tabla usuarios...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ Tabla 'usuarios' existe\n";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        echo "   📊 Total de usuarios: " . $result['total'] . "\n\n";
        
        // Listar usuarios
        echo "5. Usuarios en la base de datos:\n";
        $stmt = $pdo->query("SELECT id, nombre, email, rol FROM usuarios LIMIT 5");
        $usuarios = $stmt->fetchAll();
        foreach ($usuarios as $user) {
            echo "   - ID: {$user['id']}, Nombre: {$user['nombre']}, Email: {$user['email']}, Rol: {$user['rol']}\n";
        }
        echo "\n";
        
        // Test 6: Buscar usuario específico
        echo "6. Buscando usuario 'admin'...\n";
        $stmt = $pdo->prepare("SELECT id, email, nombre, rol FROM usuarios WHERE email = ?");
        $stmt->execute(['admin']);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            echo "   ✅ Usuario encontrado:\n";
            echo "   " . json_encode($usuario, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "   ⚠️ Usuario 'admin' NO encontrado\n";
            echo "   💡 Prueba con otro email que exista en tu BD\n\n";
        }
        
    } else {
        echo "   ❌ Tabla 'usuarios' NO existe\n";
        echo "   💡 Necesitas crear la tabla usuarios en tu BD\n\n";
    }
    
    echo "=== TEST COMPLETADO ===\n";
    echo "✅ La conexión funciona correctamente\n";
    echo "Si el login sigue fallando, el problema está en auth.php o las credenciales\n";
    
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>


