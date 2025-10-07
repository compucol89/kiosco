<?php
/**
 * 🔧 CONFIGURACIÓN UNIFICADA DE BASE DE DATOS
 * 
 * Este archivo centraliza toda la configuración de conexión
 * para evitar duplicaciones y inconsistencias
 */

// Configuración principal de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'kiosco_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Opciones PDO predeterminadas
$PDO_OPTIONS = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_TIMEOUT => 30
];

/**
 * Función unificada para obtener conexión PDO
 */
function obtenerConexionUnificada() {
    global $PDO_OPTIONS;
    
    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $PDO_OPTIONS);
        
        // Verificar que la conexión funciona
        $pdo->query("SELECT 1");
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("[DB ERROR] No se pudo conectar: " . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}

/**
 * Función para obtener conexión mysqli (para compatibilidad con código legacy)
 */
function obtenerConexionMySQLi() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión MySQLi: ' . $conn->connect_error);
    }
    
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

// Para compatibilidad con el código existente
if (!function_exists('Conexion')) {
    class Conexion {
        public static function obtenerConexion() {
            return obtenerConexionUnificada();
        }
    }
}
?>
