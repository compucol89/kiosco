<?php
/**
 * api/bd_conexion_hostinger.php
 * Configuración de base de datos para Hostinger
 * INSTRUCCIONES: Renombrar este archivo a bd_conexion.php cuando subas a Hostinger
 * RELEVANT FILES: bd_conexion.php
 */

class Conexion {
    private static $conexion = null;
    
    private static function getConfig() {
        // ⚠️ CONFIGURAR CON TUS DATOS DE HOSTINGER
        return [
            'host' => 'localhost',  // Hostinger siempre usa localhost
            'db_name' => 'u123456789_kiosco',  // Cambiar por tu BD de Hostinger
            'username' => 'u123456789_admin',  // Cambiar por tu usuario
            'password' => 'TU_PASSWORD_AQUI',  // ⚠️ CAMBIAR
            'port' => '3306'
        ];
    }
    
    public static function obtenerConexion() {
        try {
            if (self::$conexion !== null) {
                return self::$conexion;
            }
            
            $config = self::getConfig();
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db_name']};charset=utf8mb4";
            
            self::$conexion = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 30
                ]
            );
            
            return self::$conexion;
            
        } catch (PDOException $e) {
            error_log("[ERROR] Conexión Hostinger: " . $e->getMessage());
            return null;
        }
    }
    
    public static function cerrarConexion() {
        self::$conexion = null;
    }
}

// Función de compatibilidad
function obtenerConexionUnificada() {
    return Conexion::obtenerConexion();
}
?>