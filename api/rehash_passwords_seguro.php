<?php
/**
 * File: api/rehash_passwords_seguro.php
 * Script para convertir contraseñas antiguas (MD5/SHA1/plain) a bcrypt de forma segura
 * Exists to fix authentication issues caused by non-bcrypt passwords in production
 * Related files: api/auth.php, api/diagnostico_auth_completo.php
 */

header('Content-Type: application/json; charset=utf-8');

// 🔐 SEGURIDAD: Solo permitir en modo admin explícito
// Descomentar la siguiente línea para activar la protección:
// die(json_encode(['error' => 'Script bloqueado. Editar archivo para habilitar.']));

require_once 'bd_conexion.php';

$resultado = [
    'timestamp' => date('c'),
    'accion' => 'rehash_passwords',
    'usuarios_actualizados' => [],
    'errores' => [],
    'resumen' => []
];

try {
    $pdo = Conexion::obtenerConexion();
    
    if (!$pdo) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    // ========================================
    // MODO 1: REHASH CON CONTRASEÑAS CONOCIDAS
    // ========================================
    // Para usuarios que sabes su contraseña actual
    
    $usuarios_conocidos = [
        // Formato: ['username' => 'xxx', 'password_nueva' => 'yyy']
        // Ejemplo:
        // ['username' => 'admin', 'password_nueva' => 'Admin123!'],
        // ['username' => 'vendedor1', 'password_nueva' => 'Vend123!'],
    ];
    
    foreach ($usuarios_conocidos as $user_data) {
        try {
            $username = $user_data['username'];
            $password_nueva = $user_data['password_nueva'];
            
            // Generar hash bcrypt
            $hash_bcrypt = password_hash($password_nueva, PASSWORD_DEFAULT);
            
            // Actualizar en BD
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE username = ?");
            $stmt->execute([$hash_bcrypt, $username]);
            
            if ($stmt->rowCount() > 0) {
                $resultado['usuarios_actualizados'][] = [
                    'username' => $username,
                    'metodo' => 'rehash_conocido',
                    'nuevo_hash_prefix' => substr($hash_bcrypt, 0, 7)
                ];
            }
        } catch (Exception $e) {
            $resultado['errores'][] = [
                'username' => $username,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================================
    // MODO 2: DETECTAR Y REPORTAR USUARIOS SIN BCRYPT
    // ========================================
    
    $stmt = $pdo->query("
        SELECT 
            id,
            username,
            nombre,
            role,
            LENGTH(password) as password_length,
            LEFT(password, 7) as password_prefix
        FROM usuarios
        WHERE password NOT REGEXP '^\\$2[ay]\\$'
        AND password NOT REGEXP '^\\$argon2id\\$'
    ");
    
    $usuarios_sin_bcrypt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usuarios_sin_bcrypt) > 0) {
        $resultado['usuarios_sin_bcrypt'] = $usuarios_sin_bcrypt;
        $resultado['recomendaciones'] = [
            'accion' => 'Agregar usuarios al array $usuarios_conocidos con sus contraseñas',
            'ejemplo' => "['username' => 'admin', 'password_nueva' => 'Admin123!']",
            'nota' => 'Después de actualizar el array, ejecutar este script nuevamente'
        ];
    }
    
    // ========================================
    // RESUMEN
    // ========================================
    
    $resultado['resumen'] = [
        'total_actualizados' => count($resultado['usuarios_actualizados']),
        'total_errores' => count($resultado['errores']),
        'total_pendientes' => count($usuarios_sin_bcrypt ?? [])
    ];
    
} catch (Exception $e) {
    $resultado['error_fatal'] = [
        'mensaje' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine()
    ];
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

