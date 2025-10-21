<?php
/**
 * File: api/test_conexion_bd.php
 * Test script to verify database connection
 * Exists to diagnose connection issues in production
 * Related files: api/db_config.php, api/bd_conexion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🧪 Test de Conexión BD</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; margin-top: 0; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .info { 
            background: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border-left: 4px solid #3498db;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #3498db;
            color: white;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #27ae60; color: white; }
        .badge-error { background: #e74c3c; color: white; }
        .badge-warning { background: #f39c12; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test de Conexión a Base de Datos</h1>
        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_NAME'] ?? 'localhost'; ?></p>
        <p><strong>Fecha:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <hr>

<?php

try {
    echo "<h2>📋 Paso 1: Cargar Configuración</h2>\n";
    
    // Verificar que exista db_config.php
    if (!file_exists(__DIR__ . '/db_config.php')) {
        throw new Exception("❌ Archivo db_config.php no encontrado");
    }
    
    require_once 'db_config.php';
    echo "<div class='info'>";
    echo "<strong>✅ db_config.php cargado correctamente</strong><br>\n";
    echo "<table>";
    echo "<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>";
    echo "<tr><td>DB_HOST</td><td>" . DB_HOST . "</td><td><span class='badge badge-success'>OK</span></td></tr>";
    echo "<tr><td>DB_NAME</td><td>" . DB_NAME . "</td><td><span class='badge badge-success'>OK</span></td></tr>";
    echo "<tr><td>DB_USER</td><td>" . DB_USER . "</td><td><span class='badge badge-success'>OK</span></td></tr>";
    echo "<tr><td>DB_PASS</td><td>" . (empty(DB_PASS) ? '<span class="badge badge-warning">VACÍO</span>' : '<span class="badge badge-success">****</span>') . "</td><td>" . (empty(DB_PASS) ? '<span class="badge badge-warning">⚠️ Revisar</span>' : '<span class="badge badge-success">OK</span>') . "</td></tr>";
    echo "<tr><td>DB_PORT</td><td>" . DB_PORT . "</td><td><span class='badge badge-success'>OK</span></td></tr>";
    echo "<tr><td>DB_CHARSET</td><td>" . DB_CHARSET . "</td><td><span class='badge badge-success'>OK</span></td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<h2>🔌 Paso 2: Probar Conexión</h2>\n";
    
    require_once 'bd_conexion.php';
    echo "<div class='info'>";
    echo "✅ bd_conexion.php cargado correctamente<br>\n";
    echo "</div>";
    
    $pdo = Conexion::obtenerConexion();
    
    if ($pdo) {
        echo "<div class='info'>";
        echo "<h3 class='success'>✅ CONEXIÓN ESTABLECIDA EXITOSAMENTE</h3>\n";
        echo "<p>La conexión a la base de datos está funcionando correctamente.</p>";
        echo "</div>";
        
        echo "<h2>📊 Paso 3: Verificar Estructura</h2>\n";
        
        // Verificar tabla usuarios
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch();
            echo "<div class='info'>";
            echo "<strong>✅ Tabla 'usuarios' existe</strong><br>\n";
            echo "Total de usuarios: <strong>" . $result['total'] . "</strong><br>\n";
            echo "</div>";
            
            // Verificar usuario admin
            $stmt = $pdo->query("SELECT id, username, role FROM usuarios WHERE role='admin' LIMIT 1");
            $admin = $stmt->fetch();
            if ($admin) {
                echo "<div class='info'>";
                echo "<strong>✅ Usuario administrador encontrado</strong><br>\n";
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td>ID</td><td>" . $admin['id'] . "</td></tr>";
                echo "<tr><td>Username</td><td>" . $admin['username'] . "</td></tr>";
                echo "<tr><td>Role</td><td><span class='badge badge-success'>" . $admin['role'] . "</span></td></tr>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "<strong>⚠️ No se encontró usuario administrador</strong><br>\n";
                echo "Puede que necesites crear un usuario admin en la base de datos.";
                echo "</div>";
            }
            
            // Verificar otras tablas críticas
            echo "<h2>🗂️ Paso 4: Verificar Tablas Críticas</h2>\n";
            $tablas = ['productos', 'ventas', 'turnos_caja', 'movimientos_caja_detallados'];
            echo "<table>";
            echo "<tr><th>Tabla</th><th>Registros</th><th>Estado</th></tr>";
            foreach ($tablas as $tabla) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
                    $result = $stmt->fetch();
                    echo "<tr><td>$tabla</td><td>" . $result['total'] . "</td><td><span class='badge badge-success'>✅ OK</span></td></tr>";
                } catch (Exception $e) {
                    echo "<tr><td>$tabla</td><td>-</td><td><span class='badge badge-error'>❌ No existe</span></td></tr>";
                }
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ Error al verificar estructura:</strong><br>\n";
            echo $e->getMessage();
            echo "</div>";
        }
        
        echo "<hr>";
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 4px; border-left: 4px solid #28a745;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>🎉 ¡TODO CORRECTO!</h3>";
        echo "<p style='color: #155724;'>La base de datos está configurada correctamente y todas las tablas necesarias existen.</p>";
        echo "<p style='color: #155724;'><strong>Siguiente paso:</strong> Puedes probar el login en:</p>";
        echo "<p style='color: #155724;'><a href='../'>http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI'], 1) . "/</a></p>";
        echo "</div>";
        
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 4px; border-left: 4px solid #dc3545;'>";
    echo "<h3 style='color: #721c24;'>❌ ERROR DE CONEXIÓN</h3>\n";
    echo "<p style='color: #721c24;'><strong>No se pudo conectar a la base de datos.</strong></p>";
    echo "<pre>";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h4>🔧 Posibles soluciones:</h4>\n";
    echo "<ul>";
    echo "<li><strong>Verificar DB_NAME:</strong> Asegúrate que el nombre de la base de datos sea correcto</li>";
    echo "<li><strong>Verificar DB_USER:</strong> Asegúrate que el usuario de MySQL sea correcto</li>";
    echo "<li><strong>Verificar DB_PASS:</strong> Asegúrate que el password sea correcto</li>";
    echo "<li><strong>Verificar DB_HOST:</strong> Generalmente es 'localhost', pero puede variar</li>";
    echo "<li><strong>MySQL activo:</strong> Verifica que el servicio MySQL esté corriendo en el servidor</li>";
    echo "<li><strong>Permisos:</strong> El usuario debe tener permisos sobre la base de datos</li>";
    echo "<li><strong>Contactar soporte:</strong> Si nada funciona, contacta a tu proveedor de hosting</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>📖 Documentación de ayuda:</h4>";
    echo "<ul>";
    echo "<li><a href='../COMO_OBTENER_CREDENCIALES_BD.md'>Cómo obtener credenciales</a></li>";
    echo "<li><a href='../FIX_CREDENCIALES_BD.md'>Guía de solución de problemas</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 4px; border-left: 4px solid #dc3545;'>";
    echo "<h3 style='color: #721c24;'>❌ ERROR GENERAL</h3>\n";
    echo "<pre>";
    echo $e->getMessage();
    echo "</pre>";
    echo "</div>";
}

?>

        <hr>
        <p style="text-align: center; color: #7f8c8d; font-size: 12px;">
            <strong>Tayrona Almacén - Kiosco POS</strong><br>
            Script de diagnóstico de base de datos<br>
            <?php echo date('Y'); ?>
        </p>
    </div>
</body>
</html>

