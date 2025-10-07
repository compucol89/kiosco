<?php
/**
 * SISTEMA DE LOGS AFIP
 * 
 * Logging especializado para operaciones fiscales con AFIP
 */

class AFIPLogger {
    
    private static $log_file = 'logs/afip_operations.log';
    private static $error_file = 'logs/afip_errors.log';
    
    /**
     * Log de información general
     */
    public static function info($message, $context = []) {
        self::writeLog('INFO', $message, $context, self::$log_file);
    }
    
    /**
     * Log de advertencias
     */
    public static function warning($message, $context = []) {
        self::writeLog('WARNING', $message, $context, self::$log_file);
    }
    
    /**
     * Log de errores
     */
    public static function error($message, $context = []) {
        self::writeLog('ERROR', $message, $context, self::$error_file);
        // También escribir en log general
        self::writeLog('ERROR', $message, $context, self::$log_file);
    }
    
    /**
     * Log de operaciones críticas
     */
    public static function critical($message, $context = []) {
        self::writeLog('CRITICAL', $message, $context, self::$error_file);
        self::writeLog('CRITICAL', $message, $context, self::$log_file);
        
        // Enviar notificación inmediata en caso crítico
        self::notifyADMIN($message, $context);
    }
    
    /**
     * Log de auditoría fiscal
     */
    public static function audit($venta_id, $action, $data, $result) {
        global $pdo;
        
        try {
            // Crear tabla de auditoría si no existe
            self::createAuditTable();
            
            $stmt = $pdo->prepare("
                INSERT INTO afip_audit_log (
                    venta_id, 
                    action, 
                    request_data, 
                    response_data, 
                    result_status,
                    ip_address,
                    user_agent,
                    timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $venta_id,
                $action,
                json_encode($data),
                json_encode($result),
                $result['success'] ? 'SUCCESS' : 'FAILED',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            self::error("Error escribiendo auditoría AFIP", [
                'error' => $e->getMessage(),
                'venta_id' => $venta_id,
                'action' => $action
            ]);
        }
    }
    
    /**
     * Escribir log en archivo
     */
    private static function writeLog($level, $message, $context, $file) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            $level,
            $message,
            $contextStr
        );
        
        // Crear directorio si no existe
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Escribir al archivo
        file_put_contents($file, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Crear tabla de auditoría AFIP
     */
    private static function createAuditTable() {
        global $pdo;
        
        $sql = "
        CREATE TABLE IF NOT EXISTS afip_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venta_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            request_data TEXT,
            response_data TEXT,
            result_status ENUM('SUCCESS', 'FAILED', 'PENDING') DEFAULT 'PENDING',
            ip_address VARCHAR(45),
            user_agent TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_venta_id (venta_id),
            INDEX idx_timestamp (timestamp),
            INDEX idx_result_status (result_status)
        ) ENGINE=InnoDB;
        ";
        
        $pdo->exec($sql);
    }
    
    /**
     * Notificar administrador en casos críticos
     */
    private static function notifyADMIN($message, $context) {
        // Implementar notificación por email (webhook/Telegram removidos)
        error_log("🚨 AFIP CRITICAL: $message - " . json_encode($context));
        
        // Log crítico mejorado (notificaciones webhook removidas)
        error_log("🚨 AFIP CRITICAL ALERT: $message - Contexto: " . json_encode($context, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Obtener logs recientes para panel de administración
     */
    public static function getRecentLogs($limit = 50) {
        if (!file_exists(self::$log_file)) {
            return [];
        }
        
        $lines = file(self::$log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_slice($lines, -$limit);
        
        return array_reverse($lines);
    }
    
    /**
     * Limpiar logs antiguos (más de 30 días)
     */
    public static function cleanOldLogs() {
        $files = [self::$log_file, self::$error_file];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $newLines = [];
                $cutoff = date('Y-m-d', strtotime('-30 days'));
                
                foreach ($lines as $line) {
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                        if ($matches[1] >= $cutoff) {
                            $newLines[] = $line;
                        }
                    }
                }
                
                file_put_contents($file, implode("\n", $newLines) . "\n");
            }
        }
    }
}

/**
 * Funciones helper para logging rápido
 */
function logAfipInfo($message, $context = []) {
    AFIPLogger::info($message, $context);
}

function logAfipError($message, $context = []) {
    AFIPLogger::error($message, $context);
}

function logAfipCritical($message, $context = []) {
    AFIPLogger::critical($message, $context);
}

function logAfipWarning($message, $context = []) {
    AFIPLogger::warning($message, $context);
}

function auditAfip($venta_id, $action, $data, $result) {
    AFIPLogger::audit($venta_id, $action, $data, $result);
}
?> 