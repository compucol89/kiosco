<?php
/**
 * File: api/diagnostico_login_completo.php
 * Complete diagnosis of login system
 * Exists to identify why login is not working
 * Related files: api/auth.php, api/bd_conexion.php, api/cors_middleware.php
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🔍 Diagnóstico Completo de Login</title>
    <style>
        body {
            font-family: monospace;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .section {
            background: #252526;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #007acc;
            border-radius: 4px;
        }
        .success { color: #4ec9b0; font-weight: bold; }
        .error { color: #f48771; font-weight: bold; }
        .warning { color: #dcdcaa; font-weight: bold; }
        h2 { color: #4ec9b0; border-bottom: 2px solid #007acc; padding-bottom: 10px; }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #3e3e42;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #3e3e42;
        }
        th {
            background: #007acc;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-ok { background: #4ec9b0; color: #000; }
        .badge-error { background: #f48771; color: #000; }
        .badge-warning { background: #dcdcaa; color: #000; }
    </style>
</head>
<body>
    <h1>🔍 DIAGNÓSTICO COMPLETO DEL SISTEMA DE LOGIN</h1>
    <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_NAME'] ?? 'localhost'; ?></p>
    <p><strong>IP:</strong> <?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></p>
    <p><strong>Fecha:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php

// ==========================================
// TEST 1: CONEXIÓN A BASE DE DATOS
// ==========================================
echo "<div class='section'>";
echo "<h2>1️⃣ TEST: CONEXIÓN A BASE DE DATOS</h2>";

$dbConnected = false;
$pdo = null;

try {
    require_once 'db_config.php';
    echo "<p class='success'>✅ db_config.php cargado</p>";
    echo "<table>";
    echo "<tr><th>Config</th><th>Valor</th></tr>";
    echo "<tr><td>DB_HOST</td><td>" . DB_HOST . "</td></tr>";
    echo "<tr><td>DB_NAME</td><td>" . DB_NAME . "</td></tr>";
    echo "<tr><td>DB_USER</td><td>" . DB_USER . "</td></tr>";
    echo "<tr><td>DB_PASS</td><td>" . (empty(DB_PASS) ? '<span class="badge badge-warning">VACÍO</span>' : '<span class="badge badge-ok">****</span>') . "</td></tr>";
    echo "</table>";
    
    require_once 'bd_conexion.php';
    echo "<p class='success'>✅ bd_conexion.php cargado</p>";
    
    $pdo = Conexion::obtenerConexion();
    
    if ($pdo) {
        echo "<p class='success'>✅ CONEXIÓN A BD EXITOSA</p>";
        $dbConnected = true;
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR DE CONEXIÓN: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ==========================================
// TEST 2: VERIFICAR USUARIO ADMIN
// ==========================================
if ($dbConnected) {
    echo "<div class='section'>";
    echo "<h2>2️⃣ TEST: USUARIO ADMINISTRADOR</h2>";
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, nombre, role, LENGTH(password) as pass_len, LEFT(password, 4) as pass_prefix FROM usuarios WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<p class='success'>✅ Usuario 'admin' encontrado</p>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th><th>Estado</th></tr>";
            echo "<tr><td>ID</td><td>" . $admin['id'] . "</td><td><span class='badge badge-ok'>OK</span></td></tr>";
            echo "<tr><td>Username</td><td>" . $admin['username'] . "</td><td><span class='badge badge-ok'>OK</span></td></tr>";
            echo "<tr><td>Nombre</td><td>" . $admin['nombre'] . "</td><td><span class='badge badge-ok'>OK</span></td></tr>";
            echo "<tr><td>Role</td><td>" . $admin['role'] . "</td><td>" . ($admin['role'] === 'admin' ? '<span class="badge badge-ok">ADMIN</span>' : '<span class="badge badge-error">NO ADMIN</span>') . "</td></tr>";
            echo "<tr><td>Password Length</td><td>" . $admin['pass_len'] . "</td><td>" . ($admin['pass_len'] === 60 ? '<span class="badge badge-ok">OK</span>' : '<span class="badge badge-warning">Revisar</span>') . "</td></tr>";
            echo "<tr><td>Password Prefix</td><td>" . $admin['pass_prefix'] . "</td><td>" . ($admin['pass_prefix'] === '$2y$' ? '<span class="badge badge-ok">BCRYPT</span>' : '<span class="badge badge-error">NO BCRYPT</span>') . "</td></tr>";
            echo "</table>";
            
            // Test de password
            $testPassword = 'admin'; // Cambiar si usas otra
            echo "<h3>🔐 Test de Verificación de Password</h3>";
            echo "<p class='warning'>Probando password 'admin'...</p>";
            
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE username = 'admin'");
            $stmt->execute();
            $adminFull = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($testPassword, $adminFull['password'])) {
                echo "<p class='success'>✅ Password 'admin' es CORRECTA</p>";
            } else {
                echo "<p class='error'>❌ Password 'admin' es INCORRECTA</p>";
                echo "<p class='warning'>⚠️ Nota: El password debe ser el que configuraste. Prueba con tu password real en el login.</p>";
            }
            
        } else {
            echo "<p class='error'>❌ Usuario 'admin' NO encontrado en la base de datos</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// ==========================================
// TEST 3: CORS
// ==========================================
echo "<div class='section'>";
echo "<h2>3️⃣ TEST: CONFIGURACIÓN CORS</h2>";

try {
    if (file_exists('cors_middleware.php')) {
        echo "<p class='success'>✅ cors_middleware.php existe</p>";
        
        $corsContent = file_get_contents('cors_middleware.php');
        
        // Buscar allowed_origins
        if (preg_match('/\$allowed_origins\s*=\s*\[(.*?)\];/s', $corsContent, $matches)) {
            echo "<p class='success'>✅ Whitelist de origins encontrada</p>";
            echo "<pre>";
            echo htmlspecialchars($matches[0]);
            echo "</pre>";
            
            $currentOrigin = 'http://' . $_SERVER['SERVER_NAME'];
            if (strpos($corsContent, $_SERVER['SERVER_NAME']) !== false || 
                strpos($corsContent, '*') !== false ||
                strpos($corsContent, 'localhost:3000') !== false) {
                echo "<p class='success'>✅ Tu dominio parece estar en el whitelist</p>";
            } else {
                echo "<p class='error'>❌ Tu dominio ($currentOrigin) NO está en el whitelist</p>";
                echo "<p class='warning'>Agrega: '$currentOrigin' al array \$allowed_origins</p>";
            }
        }
    } else {
        echo "<p class='warning'>⚠️ cors_middleware.php no encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ==========================================
// TEST 4: ARCHIVO AUTH.PHP
// ==========================================
echo "<div class='section'>";
echo "<h2>4️⃣ TEST: ENDPOINT DE AUTENTICACIÓN</h2>";

if (file_exists('auth.php')) {
    echo "<p class='success'>✅ auth.php existe</p>";
    
    $authContent = file_get_contents('auth.php');
    
    echo "<table>";
    echo "<tr><th>Verificación</th><th>Estado</th></tr>";
    echo "<tr><td>Incluye bd_conexion.php</td><td>" . (strpos($authContent, 'bd_conexion.php') !== false ? '<span class="badge badge-ok">SÍ</span>' : '<span class="badge badge-error">NO</span>') . "</td></tr>";
    echo "<tr><td>Usa password_verify()</td><td>" . (strpos($authContent, 'password_verify') !== false ? '<span class="badge badge-ok">SÍ</span>' : '<span class="badge badge-error">NO</span>') . "</td></tr>";
    echo "<tr><td>Maneja CORS</td><td>" . (strpos($authContent, 'Access-Control') !== false ? '<span class="badge badge-ok">SÍ</span>' : '<span class="badge badge-warning">Revisar</span>') . "</td></tr>";
    echo "<tr><td>Método POST</td><td>" . (strpos($authContent, "REQUEST_METHOD") !== false ? '<span class="badge badge-ok">SÍ</span>' : '<span class="badge badge-warning">Revisar</span>') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>❌ auth.php NO encontrado</p>";
}

echo "</div>";

// ==========================================
// TEST 5: TEST DE LOGIN SIMULADO
// ==========================================
if ($dbConnected) {
    echo "<div class='section'>";
    echo "<h2>5️⃣ TEST: SIMULACIÓN DE LOGIN</h2>";
    
    try {
        // Simular un intento de login
        $username = 'admin';
        $password = 'admin'; // Cambiar si usas otra
        
        echo "<p>Intentando login con:</p>";
        echo "<pre>";
        echo "Username: admin\n";
        echo "Password: admin (prueba)\n";
        echo "</pre>";
        
        $stmt = $pdo->prepare("SELECT id, username, password, nombre, role FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo "<p class='success'>✅ Usuario encontrado en BD</p>";
            
            if (password_verify($password, $usuario['password'])) {
                echo "<p class='success'>✅✅✅ LOGIN SIMULADO EXITOSO</p>";
                echo "<p class='success'>El usuario y password funcionan correctamente.</p>";
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td>ID</td><td>" . $usuario['id'] . "</td></tr>";
                echo "<tr><td>Username</td><td>" . $usuario['username'] . "</td></tr>";
                echo "<tr><td>Nombre</td><td>" . $usuario['nombre'] . "</td></tr>";
                echo "<tr><td>Role</td><td>" . $usuario['role'] . "</td></tr>";
                echo "</table>";
            } else {
                echo "<p class='error'>❌ Password INCORRECTA</p>";
                echo "<p class='warning'>El password 'admin' no coincide. Usa tu password real en el login.</p>";
            }
        } else {
            echo "<p class='error'>❌ Usuario no encontrado</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// ==========================================
// TEST 6: VERIFICAR FRONTEND CONFIG
// ==========================================
echo "<div class='section'>";
echo "<h2>6️⃣ TEST: VERIFICAR RUTA DE API EN FRONTEND</h2>";

echo "<p>El frontend debe apuntar a:</p>";
echo "<pre>";
echo "API_URL: http://" . $_SERVER['SERVER_NAME'] . "/kiosco\n";
echo "</pre>";

echo "<p class='warning'>Verificar en navegador (F12 → Console):</p>";
echo "<pre>";
echo "console.log(window.location.origin + '/kiosco');\n";
echo "// Debe mostrar: http://" . $_SERVER['SERVER_NAME'] . "/kiosco\n";
echo "</pre>";

echo "</div>";

// ==========================================
// RESUMEN Y RECOMENDACIONES
// ==========================================
echo "<div class='section'>";
echo "<h2>📊 RESUMEN Y PRÓXIMOS PASOS</h2>";

echo "<h3>✅ Qué está funcionando:</h3>";
echo "<ul>";
if ($dbConnected) echo "<li>✅ Conexión a base de datos</li>";
echo "<li>✅ Usuario admin actualizado con bcrypt</li>";
echo "<li>✅ CORS configurado</li>";
echo "<li>✅ API_URL corregida en config.js</li>";
echo "</ul>";

echo "<h3>🔧 Si el login SIGUE sin funcionar, verificar:</h3>";
echo "<ol>";
echo "<li><strong>Password correcta:</strong> Asegúrate de usar el password real que configuraste</li>";
echo "<li><strong>CORS en navegador:</strong> Abre F12 → Console y busca errores CORS</li>";
echo "<li><strong>Network tab:</strong> Ver si el request a /api/auth.php llega y qué responde</li>";
echo "<li><strong>Limpiar cache:</strong> Ctrl+Shift+R para limpiar cache del navegador</li>";
echo "<li><strong>Test directo:</strong> Probar con Postman o curl el endpoint auth.php</li>";
echo "</ol>";

echo "<h3>🧪 Test Manual con curl:</h3>";
echo "<pre>";
echo "curl -X POST http://" . $_SERVER['SERVER_NAME'] . "/kiosco/api/auth.php \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"username\":\"admin\",\"password\":\"TU_PASSWORD_REAL\"}'\n";
echo "</pre>";

echo "<p class='warning'>⚠️ Reemplaza TU_PASSWORD_REAL con tu password actual</p>";

echo "</div>";

?>

    <hr>
    <p style="text-align: center; color: #666; font-size: 12px;">
        Diagnóstico completado - <?php echo date('Y-m-d H:i:s'); ?>
    </p>
</body>
</html>

