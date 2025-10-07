<?php
/**
 * api/bd_conexion.php
 * Conexión única a base de datos MySQL
 * Lee credenciales desde db_config.php (archivo único)
 * RELEVANT FILES: db_config.php (ÚNICO ARCHIVO DE CREDENCIALES)
 */

// Cargar configuración única
require_once __DIR__ . '/db_config.php';

class Conexion {
    private static $conexion = null;
    
    /**
     * Obtiene conexión PDO a la base de datos
     * Usa credenciales de db_config.php
     */
    public static function obtenerConexion() {
        try {
            // Si ya existe conexión, reutilizarla
            if (self::$conexion !== null) {
                return self::$conexion;
            }
            
            // Crear DSN desde constantes
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            // Log de conexión
            error_log("[BD_CONEXION] Conectando a: " . DB_HOST . ":" . DB_PORT . " / " . DB_NAME);
            
            // Crear conexión PDO
            self::$conexion = new PDO($dsn, DB_USER, DB_PASS, $GLOBALS['DB_OPTIONS']);
            
            // Configurar zona horaria (si está disponible)
            try {
                self::$conexion->exec("SET time_zone = '-03:00'"); // Argentina UTC-3
            } catch (PDOException $e) {
                // Ignorar si falla, no es crítico
                error_log("[BD_CONEXION] ⚠️ No se pudo configurar timezone");
            }
            
            // Verificar conexión
            $test = self::$conexion->query("SELECT 1");
            if ($test) {
                error_log("[BD_CONEXION] ✅ Conexión establecida correctamente");
            }
            
            return self::$conexion;
            
        } catch (PDOException $e) {
            error_log("[BD_CONEXION] ❌ Error: " . $e->getMessage());
            echo "[BD_CONEXION] ❌ Error: " . $e->getMessage() . "\n";
            self::$conexion = null;
            throw $e; // Lanzar excepción para debug
        }
    }
    
    /**
     * Cierra la conexión
     */
    public static function cerrarConexion() {
        self::$conexion = null;
        error_log("[BD_CONEXION] Conexión cerrada");
    }
}

// Función de compatibilidad
function obtenerConexionUnificada() {
    return Conexion::obtenerConexion();
}
?>