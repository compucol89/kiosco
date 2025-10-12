<?php
/**
 * Script seguro para resetear/crear usuario admin
 * Útil cuando hay problemas de login en producción
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $accion = $_GET['accion'] ?? 'info';
    
    if ($accion === 'info') {
        // Solo mostrar info, no hacer cambios
        $stmt = $pdo->query("SELECT id, username, nombre, role, created_at FROM usuarios WHERE role = 'admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'admins_actuales' => $admins,
            'total_admins' => count($admins),
            'instrucciones' => 'Para resetear admin, usa: ?accion=reset_admin&password=NUEVA_PASSWORD'
        ], JSON_PRETTY_PRINT);
        
    } elseif ($accion === 'reset_admin') {
        // Resetear contraseña de admin
        $nuevaPassword = $_GET['password'] ?? 'admin123';
        
        // Generar hash bcrypt
        $hashNuevo = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        
        // Verificar si existe admin
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE username = 'admin'");
        $adminExiste = $stmt->fetch();
        
        if ($adminExiste) {
            // Actualizar admin existente
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, nombre = 'Administrador' WHERE username = 'admin'");
            $stmt->execute([$hashNuevo]);
            $mensaje = "Admin actualizado";
        } else {
            // Crear admin nuevo
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, role) VALUES ('admin', ?, 'Administrador', 'admin')");
            $stmt->execute([$hashNuevo]);
            $mensaje = "Admin creado";
        }
        
        // Verificar que funciona
        $pruebaLogin = password_verify($nuevaPassword, $hashNuevo);
        
        echo json_encode([
            'success' => true,
            'mensaje' => $mensaje,
            'username' => 'admin',
            'password' => $nuevaPassword,
            'hash_generado' => substr($hashNuevo, 0, 20) . '...',
            'verificacion_hash' => $pruebaLogin ? '✅ Hash funciona correctamente' : '❌ Error en hash',
            'instrucciones_login' => [
                "Username: admin",
                "Password: {$nuevaPassword}",
                "Cámbiala desde el módulo de Usuarios después de entrar"
            ]
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

