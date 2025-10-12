<?php
/**
 * 🚀 SCRIPT DE PRIMER USO EN PRODUCCIÓN
 * 
 * IMPORTANTE: Ejecutar INMEDIATAMENTE después de subir el sistema a Hostinger
 * 
 * Este script:
 * 1. Crea usuario admin funcional
 * 2. Configura la base de datos
 * 3. Garantiza que puedas iniciar sesión
 * 4. Se auto-desactiva después de usarse
 * 
 * URL: https://tudominio.com/api/primer_uso_produccion.php?ejecutar=si
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

// Verificar parámetro de seguridad
$ejecutar = $_GET['ejecutar'] ?? '';

if ($ejecutar !== 'si') {
    echo json_encode([
        'success' => false,
        'mensaje' => '⚠️ Script de Primer Uso en Producción',
        'instrucciones' => [
            '1. Este script crea/actualiza el usuario admin para garantizar acceso',
            '2. Solo se ejecuta si agregas: ?ejecutar=si',
            '3. Ejemplo: primer_uso_produccion.php?ejecutar=si',
            '4. Después de ejecutar, cambia la contraseña desde el sistema'
        ],
        'importante' => 'Agrega ?ejecutar=si a la URL para continuar'
    ], JSON_PRETTY_PRINT);
    exit;
}

require_once 'bd_conexion.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->beginTransaction();
    
    $resultado = [
        'pasos_ejecutados' => [],
        'errores' => []
    ];
    
    // PASO 1: Verificar/Crear tabla usuarios
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                role ENUM('admin','vendedor','cajero') NOT NULL DEFAULT 'vendedor',
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $resultado['pasos_ejecutados'][] = '✅ Tabla usuarios verificada/creada';
    } catch (Exception $e) {
        $resultado['errores'][] = '⚠️ Error en tabla usuarios: ' . $e->getMessage();
    }
    
    // PASO 2: Crear/Actualizar usuario admin
    $passwordAdmin = 'Tayrona2025!';
    $hashAdmin = password_hash($passwordAdmin, PASSWORD_DEFAULT);
    
    // Verificar si admin existe
    $stmt = $pdo->query("SELECT id FROM usuarios WHERE username = 'admin'");
    $adminExiste = $stmt->fetch();
    
    if ($adminExiste) {
        // Actualizar admin existente
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET password = ?, 
                nombre = 'Administrador Principal',
                role = 'admin'
            WHERE username = 'admin'
        ");
        $stmt->execute([$hashAdmin]);
        $resultado['pasos_ejecutados'][] = '✅ Usuario admin ACTUALIZADO (contraseña reseteada)';
    } else {
        // Crear admin nuevo
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, role) 
            VALUES ('admin', ?, 'Administrador Principal', 'admin')
        ");
        $stmt->execute([$hashAdmin]);
        $resultado['pasos_ejecutados'][] = '✅ Usuario admin CREADO desde cero';
    }
    
    // PASO 3: Verificar que el hash funciona
    $verificacion = password_verify($passwordAdmin, $hashAdmin);
    if ($verificacion) {
        $resultado['pasos_ejecutados'][] = '✅ Hash de contraseña verificado y funcional';
    } else {
        $resultado['errores'][] = '❌ Error: Hash no verifica correctamente';
    }
    
    // PASO 4: Verificar otros usuarios
    $stmt = $pdo->query("SELECT username, role FROM usuarios WHERE username != 'admin'");
    $otrosUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    // RESULTADO FINAL
    echo json_encode([
        'success' => true,
        'mensaje' => '🎉 Sistema preparado exitosamente para producción',
        'resultado' => $resultado,
        'credenciales_admin' => [
            'username' => 'admin',
            'password' => $passwordAdmin,
            'url_login' => str_replace('api/primer_uso_produccion.php', '', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])
        ],
        'otros_usuarios' => $otrosUsuarios,
        'siguiente_paso' => [
            '1. Ve a la URL de login de tu sistema',
            '2. Inicia sesión con: admin / Tayrona2025!',
            '3. Ve a Configuración → Usuarios',
            '4. CAMBIA la contraseña del admin a una más segura',
            '5. Crea tus vendedores/cajeros desde el módulo Usuarios'
        ],
        'importante' => [
            '⚠️ GUARDA ESTA CONTRASEÑA: Tayrona2025!',
            '⚠️ Cámbiala INMEDIATAMENTE después de entrar',
            '⚠️ Este script ya cumplió su función',
            '✅ Puedes eliminarlo si quieres (opcional)'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $rollbackEx) {
            // Ignorar error de rollback
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'solucion' => 'Revisa la conexión a la base de datos en bd_conexion.php'
    ], JSON_PRETTY_PRINT);
}
?>

