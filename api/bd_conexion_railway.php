<?php
/**
 * api/bd_conexion_railway.php
 * Configuración de base de datos específica para Railway
 * Usa variables de entorno automáticas de Railway MySQL
 * RELEVANT FILES: api/bd_conexion.php, config_production.php
 */

class Conexion {
    // Configuración Railway (usar variables de entorno)
    private static $host = null;
    private static $db_name = null;
    private static $username = null;
    private static $password = null;
    private static $conexion = null;
    
    private static function getConfig() {
        // Railway proporciona estas variables automáticamente
        return [
            'host' => $_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? 'localhost',
            'port' => $_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? '3306',
            'database' => $_ENV['MYSQL_DATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? 'railway',
            'username' => $_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? 'root',
            'password' => $_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? ''
        ];
    }
    
    public static function obtenerConexion() {
        try {
            if (self::$conexion !== null) {
                return self::$conexion;
            }
            
            $config = self::getConfig();
            
            // Log de conexión para Railway
            error_log("[RAILWAY] Conectando a MySQL: {$config['host']}:{$config['port']} DB: {$config['database']}");
            
            // DSN con puerto específico
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
            
            self::$conexion = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_TIMEOUT => 30
                ]
            );
            
            // Verificar conexión
            self::$conexion->query("SELECT 1");
            error_log("[RAILWAY] Conexión MySQL exitosa");
            
            return self::$conexion;
            
        } catch (PDOException $e) {
            error_log("[RAILWAY] Error de conexión MySQL: " . $e->getMessage());
            
            // Fallback a configuración local si falla Railway
            try {
                self::$conexion = new PDO(
                    'mysql:host=localhost;dbname=kiosco_db;charset=utf8mb4',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
                    ]
                );
                error_log("[RAILWAY] Usando configuración local como fallback");
                return self::$conexion;
            } catch (PDOException $fallback_error) {
                error_log("[RAILWAY] Error en fallback: " . $fallback_error->getMessage());
                throw new Exception('Error de conexión a la base de datos');
            }
        }
    }
    
    public static function cerrarConexion() {
        self::$conexion = null;
    }
}
?>
