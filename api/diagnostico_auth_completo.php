<?php
/**
 * File: api/diagnostico_auth_completo.php
 * Script de diagnóstico para depurar problemas de autenticación en producción
 * Exists to identify root causes of login failures (bcrypt, charset, collation)
 * Related files: api/auth.php, api/bd_conexion.php, src/components/LoginPage.jsx
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'bd_conexion.php';

// ========================================
// 🔍 DIAGNÓSTICO COMPLETO DE AUTENTICACIÓN
// ========================================

$diagnostico = [
    'timestamp' => date('c'),
    'entorno' => [],
    'php' => [],
    'mysql' => [],
    'usuarios' => [],
    'test_bcrypt' => [],
    'recomendaciones' => []
];

try {
    $pdo = Conexion::obtenerConexion();
    
    if (!$pdo) {
        $diagnostico['error'] = 'No se pudo conectar a la base de datos';
        echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // 1️⃣ ENTORNO
    // ========================================
    $diagnostico['entorno'] = [
        'servidor' => $_SERVER['SERVER_NAME'] ?? 'desconocido',
        'ip_servidor' => $_SERVER['SERVER_ADDR'] ?? 'desconocido',
        'puerto' => $_SERVER['SERVER_PORT'] ?? 'desconocido',
        'protocolo' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http',
        'sistema_operativo' => PHP_OS,
        'timezone' => date_default_timezone_get()
    ];
    
    // ========================================
    // 2️⃣ PHP
    // ========================================
    $diagnostico['php'] = [
        'version' => phpversion(),
        'extensiones_requeridas' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'sodium' => extension_loaded('sodium')
        ],
        'password_algos_disponibles' => [
            'PASSWORD_DEFAULT' => PASSWORD_DEFAULT,
            'PASSWORD_BCRYPT' => PASSWORD_BCRYPT,
            'PASSWORD_ARGON2I' => defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : 'NO DISPONIBLE',
            'PASSWORD_ARGON2ID' => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : 'NO DISPONIBLE'
        ],
        'default_algo_name' => password_get_info(password_hash('test', PASSWORD_DEFAULT))['algoName']
    ];
    
    // ========================================
    // 3️⃣ MYSQL
    // ========================================
    
    // Variables críticas
    $vars = [
        'character_set_server',
        'character_set_database',
        'character_set_connection',
        'collation_server',
        'collation_database',
        'collation_connection',
        'lower_case_table_names',
        'sql_mode',
        'default_authentication_plugin'
    ];
    
    $mysql_vars = [];
    foreach ($vars as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $mysql_vars[$var] = $result['Value'];
        }
    }
    
    $diagnostico['mysql'] = [
        'version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
        'variables' => $mysql_vars
    ];
    
    // ========================================
    // 4️⃣ TABLA USUARIOS
    // ========================================
    
    // Verificar estructura
    $stmt = $pdo->query("SHOW CREATE TABLE usuarios");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener info de columnas
    $stmt = $pdo->query("SHOW FULL COLUMNS FROM usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Análisis de usuarios
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_usuarios,
            SUM(CASE WHEN password REGEXP '^\\$2y\\$' THEN 1 ELSE 0 END) as bcrypt_usuarios,
            SUM(CASE WHEN password REGEXP '^\\$2a\\$' THEN 1 ELSE 0 END) as bcrypt_2a_usuarios,
            SUM(CASE WHEN password REGEXP '^\\$argon2id\\$' THEN 1 ELSE 0 END) as argon2id_usuarios,
            SUM(CASE WHEN LENGTH(password) < 30 THEN 1 ELSE 0 END) as password_sospechosas,
            SUM(CASE WHEN LENGTH(password) = 32 THEN 1 ELSE 0 END) as posible_md5,
            SUM(CASE WHEN LENGTH(password) = 40 THEN 1 ELSE 0 END) as posible_sha1
        FROM usuarios
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Listar usuarios con info de password (SIN mostrar el hash completo por seguridad)
    $stmt = $pdo->query("
        SELECT 
            id,
            username,
            nombre,
            role,
            LEFT(password, 7) as password_prefix,
            LENGTH(password) as password_length,
            CASE 
                WHEN password REGEXP '^\\$2y\\$' THEN 'bcrypt ($2y$)'
                WHEN password REGEXP '^\\$2a\\$' THEN 'bcrypt ($2a$)'
                WHEN password REGEXP '^\\$argon2id\\$' THEN 'argon2id'
                WHEN LENGTH(password) = 32 THEN 'POSIBLE MD5 (INSEGURO)'
                WHEN LENGTH(password) = 40 THEN 'POSIBLE SHA1 (INSEGURO)'
                ELSE 'DESCONOCIDO/PLANO (PELIGRO)'
            END as password_type
        FROM usuarios
        ORDER BY id
    ");
    $usuarios_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $diagnostico['usuarios'] = [
        'estructura' => [
            'columnas' => $columns,
            'create_table' => isset($createTable['Create Table']) ? substr($createTable['Create Table'], 0, 500) . '...' : 'N/A'
        ],
        'estadisticas' => $stats,
        'lista' => $usuarios_list
    ];
    
    // ========================================
    // 5️⃣ TEST BCRYPT
    // ========================================
    
    // Crear contraseñas de prueba
    $password_test = 'Test123!';
    $hash_nuevo = password_hash($password_test, PASSWORD_DEFAULT);
    
    $diagnostico['test_bcrypt'] = [
        'password_original' => $password_test,
        'hash_generado' => $hash_nuevo,
        'hash_length' => strlen($hash_nuevo),
        'hash_prefix' => substr($hash_nuevo, 0, 7),
        'hash_info' => password_get_info($hash_nuevo),
        'verify_correcto' => password_verify($password_test, $hash_nuevo),
        'verify_incorrecto' => password_verify('WrongPass', $hash_nuevo)
    ];
    
    // ========================================
    // 6️⃣ RECOMENDACIONES
    // ========================================
    
    $recomendaciones = [];
    
    // Check 1: Extensiones PHP
    if (!extension_loaded('pdo_mysql')) {
        $recomendaciones[] = '❌ CRÍTICO: Extensión pdo_mysql no disponible';
    }
    if (!extension_loaded('mbstring')) {
        $recomendaciones[] = '⚠️ ADVERTENCIA: Extensión mbstring no disponible (problemas con UTF-8)';
    }
    
    // Check 2: Usuarios sin bcrypt
    if ($stats['total_usuarios'] > 0 && $stats['bcrypt_usuarios'] < $stats['total_usuarios']) {
        $sin_bcrypt = $stats['total_usuarios'] - $stats['bcrypt_usuarios'];
        $recomendaciones[] = "❌ CRÍTICO: {$sin_bcrypt} usuario(s) NO tienen contraseña bcrypt. Ejecutar rehash.";
    }
    
    // Check 3: MD5/SHA1 detectado
    if ($stats['posible_md5'] > 0) {
        $recomendaciones[] = "❌ PELIGRO: {$stats['posible_md5']} usuario(s) con posible MD5 (32 chars). INSEGURO.";
    }
    if ($stats['posible_sha1'] > 0) {
        $recomendaciones[] = "❌ PELIGRO: {$stats['posible_sha1']} usuario(s) con posible SHA1 (40 chars). INSEGURO.";
    }
    
    // Check 4: Charset/Collation
    if (isset($mysql_vars['character_set_database']) && $mysql_vars['character_set_database'] !== 'utf8mb4') {
        $recomendaciones[] = "⚠️ ADVERTENCIA: Charset de BD es {$mysql_vars['character_set_database']}, se recomienda utf8mb4";
    }
    
    // Check 5: lower_case_table_names
    if (isset($mysql_vars['lower_case_table_names']) && $mysql_vars['lower_case_table_names'] !== '0') {
        $recomendaciones[] = "ℹ️ INFO: lower_case_table_names={$mysql_vars['lower_case_table_names']} (Windows=1, Linux=0). Puede causar problemas de mayúsculas en usernames.";
    }
    
    // Check 6: Todos OK
    if (empty($recomendaciones)) {
        $recomendaciones[] = "✅ Todo parece estar configurado correctamente";
    }
    
    $diagnostico['recomendaciones'] = $recomendaciones;
    
    // ========================================
    // 7️⃣ SCRIPT DE SOLUCIÓN
    // ========================================
    
    if ($stats['bcrypt_usuarios'] < $stats['total_usuarios']) {
        $diagnostico['solucion'] = [
            'descripcion' => 'Algunos usuarios NO tienen contraseñas bcrypt. Ejecutar rehash.',
            'sql_manual' => "-- OPCIÓN A: Resetear password de admin manualmente\nUPDATE usuarios SET password = '\$2y\$10\$yZSbplOdTxIU4P/ylVbPIOhVwoo3Yji.nQ8odBY6QLDvXU0VTuyjG' WHERE username = 'admin'; -- password: Admin123!",
            'api_automatica' => 'POST /api/rehash_passwords.php con parámetro seguro'
        ];
    }
    
} catch (Exception $e) {
    $diagnostico['error'] = [
        'mensaje' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine()
    ];
}

// Devolver diagnóstico completo
echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

