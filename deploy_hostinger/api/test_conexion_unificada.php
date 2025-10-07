<?php
/**
 * api/test_conexion_unificada.php
 * Prueba de conexión unificada
 * RELEVANT FILES: db_config.php, bd_conexion.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DE CONEXIÓN UNIFICADA ===\n\n";

require_once 'bd_conexion.php';

try {
    echo "1️⃣ Cargando configuración desde db_config.php...\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Port: " . DB_PORT . "\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   User: " . DB_USER . "\n";
    echo "   Password: " . (DB_PASS ? '[CONFIGURADA]' : '[VACÍA]') . "\n";
    echo "   Charset: " . DB_CHARSET . "\n\n";
    
    echo "2️⃣ Probando conexión...\n";
    $pdo = Conexion::obtenerConexion();
    
    if (!$pdo) {
        echo "   ❌ No se pudo conectar\n";
        exit;
    }
    
    echo "   ✅ Conexión exitosa\n\n";
    
    echo "3️⃣ Verificando tablas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Tablas encontradas: " . count($tablas) . "\n";
    foreach ($tablas as $tabla) {
        echo "   - $tabla\n";
    }
    echo "\n";
    
    echo "4️⃣ Probando función de compatibilidad...\n";
    $pdo2 = obtenerConexionUnificada();
    
    if ($pdo2) {
        echo "   ✅ obtenerConexionUnificada() funciona\n\n";
    }
    
    echo "=" . str_repeat("=", 60) . "\n";
    echo "✅ CONEXIÓN UNIFICADA FUNCIONANDO CORRECTAMENTE\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    echo "🎯 Para cambiar servidor:\n";
    echo "   1. Editar: api/db_config.php\n";
    echo "   2. Cambiar DB_HOST, DB_NAME, DB_USER, DB_PASS\n";
    echo "   3. ¡Listo! Todo el sistema usará las nuevas credenciales\n\n";
    
    echo json_encode([
        'success' => true,
        'host' => DB_HOST,
        'database' => DB_NAME,
        'tablas' => count($tablas)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>

