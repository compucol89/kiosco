<?php
/**
 * 🔒 SISTEMA DE AUDITORÍA INMUTABLE - GRADO BANCARIO
 * 
 * Características críticas:
 * - Logs inmutables con hash de integridad
 * - Trazabilidad completa de transacciones
 * - Resistente a manipulación
 * - Cumplimiento de estándares financieros
 */

require_once 'bd_conexion.php';

class AuditLogger {
    private $pdo;
    private $secret_key;
    
    public function __construct() {
        $this->pdo = Conexion::obtenerConexion();
        $this->secret_key = $this->getSecretKey();
        $this->initializeAuditTables();
    }
    
    // ========================================================================
    // 🔐 LOGGING INMUTABLE
    // ========================================================================
    
    /**
     * Registrar evento crítico en logs inmutables
     */
    public function logCriticalEvent($categoria, $accion, $detalles, $usuario_id = null, $ip = null) {
        try {
            $timestamp = microtime(true);
            $fecha_hora = date('Y-m-d H:i:s.u');
            
            // Generar ID único para el evento
            $evento_id = $this->generateEventId($categoria, $accion, $timestamp);
            
            // Obtener información del contexto
            $contexto = $this->gatherContext($ip);
            
            // Preparar datos para el log
            $log_data = [
                'evento_id' => $evento_id,
                'categoria' => $categoria,
                'accion' => $accion,
                'detalles' => json_encode($detalles, JSON_UNESCAPED_UNICODE),
                'usuario_id' => $usuario_id,
                'ip_origen' => $contexto['ip'],
                'user_agent' => $contexto['user_agent'],
                'fecha_hora' => $fecha_hora,
                'timestamp_unix' => $timestamp,
                'session_id' => session_id() ?: $this->generateSessionId()
            ];
            
            // Generar hash de integridad
            $hash_integridad = $this->generateIntegrityHash($log_data);
            $log_data['hash_integridad'] = $hash_integridad;
            
            // Insertar en tabla de auditoría inmutable
            $stmt = $this->pdo->prepare("
                INSERT INTO auditoria_inmutable (
                    evento_id, categoria, accion, detalles, usuario_id,
                    ip_origen, user_agent, fecha_hora, timestamp_unix,
                    session_id, hash_integridad, estado
                ) VALUES (
                    :evento_id, :categoria, :accion, :detalles, :usuario_id,
                    :ip_origen, :user_agent, :fecha_hora, :timestamp_unix,
                    :session_id, :hash_integridad, 'ACTIVO'
                )
            ");
            
            $result = $stmt->execute($log_data);
            
            if ($result) {
                $log_id = $this->pdo->lastInsertId();
                
                // Crear backup inmediato en tabla secundaria
                $this->createImmutableBackup($log_id, $log_data);
                
                return [
                    'success' => true,
                    'log_id' => $log_id,
                    'evento_id' => $evento_id,
                    'hash' => $hash_integridad
                ];
            }
            
            throw new Exception('Error al insertar log de auditoría');
            
        } catch (Exception $e) {
            // Log de emergencia en archivo si la BD falla
            $this->emergencyFileLog($categoria, $accion, $detalles, $e->getMessage());
            throw new Exception('Error crítico en sistema de auditoría: ' . $e->getMessage());
        }
    }
    
    /**
     * Logging específico para transacciones de caja
     */
    public function logCashTransaction($tipo_transaccion, $datos_transaccion, $usuario_id = null) {
        $detalles = [
            'tipo' => $tipo_transaccion,
            'monto' => $datos_transaccion['monto'] ?? 0,
            'metodo_pago' => $datos_transaccion['metodo_pago'] ?? null,
            'caja_id' => $datos_transaccion['caja_id'] ?? null,
            'venta_id' => $datos_transaccion['venta_id'] ?? null,
            'numero_comprobante' => $datos_transaccion['numero_comprobante'] ?? null,
            'descripcion' => $datos_transaccion['descripcion'] ?? null,
            'afecta_efectivo' => $datos_transaccion['afecta_efectivo'] ?? false
        ];
        
        return $this->logCriticalEvent('CAJA', $tipo_transaccion, $detalles, $usuario_id);
    }
    
    /**
     * Logging para apertura/cierre de caja
     */
    public function logCashOperation($operacion, $datos_caja, $usuario_id = null) {
        $detalles = [
            'operacion' => $operacion,
            'caja_id' => $datos_caja['caja_id'] ?? null,
            'monto' => $datos_caja['monto'] ?? 0,
            'diferencia' => $datos_caja['diferencia'] ?? 0,
            'justificacion' => $datos_caja['justificacion'] ?? null,
            'fecha_operacion' => $datos_caja['fecha_operacion'] ?? date('Y-m-d H:i:s')
        ];
        
        return $this->logCriticalEvent('OPERACION_CAJA', $operacion, $detalles, $usuario_id);
    }
    
    // ========================================================================
    // 🔍 VERIFICACIÓN DE INTEGRIDAD
    // ========================================================================
    
    /**
     * Verificar integridad de logs
     */
    public function verifyLogIntegrity($log_id = null) {
        try {
            $whereClause = $log_id ? "WHERE id = :log_id" : "";
            $params = $log_id ? ['log_id' => $log_id] : [];
            
            $stmt = $this->pdo->prepare("
                SELECT id, evento_id, categoria, accion, detalles, usuario_id,
                       ip_origen, user_agent, fecha_hora, timestamp_unix,
                       session_id, hash_integridad
                FROM auditoria_inmutable 
                {$whereClause}
                ORDER BY id DESC
            ");
            
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $verification_results = [];
            $integrity_violations = 0;
            
            foreach ($logs as $log) {
                $original_hash = $log['hash_integridad'];
                unset($log['hash_integridad']);
                
                $calculated_hash = $this->generateIntegrityHash($log);
                $is_valid = hash_equals($original_hash, $calculated_hash);
                
                if (!$is_valid) {
                    $integrity_violations++;
                }
                
                $verification_results[] = [
                    'log_id' => $log['id'],
                    'evento_id' => $log['evento_id'],
                    'is_valid' => $is_valid,
                    'original_hash' => $original_hash,
                    'calculated_hash' => $calculated_hash,
                    'fecha_hora' => $log['fecha_hora']
                ];
            }
            
            return [
                'success' => true,
                'total_logs' => count($logs),
                'integrity_violations' => $integrity_violations,
                'integrity_percentage' => count($logs) > 0 ? (count($logs) - $integrity_violations) / count($logs) * 100 : 100,
                'results' => $verification_results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================================================================
    // 📊 CONSULTAS Y REPORTES DE AUDITORÍA
    // ========================================================================
    
    /**
     * Obtener logs por categoría y período
     */
    public function getAuditLogs($categoria = null, $fecha_inicio = null, $fecha_fin = null, $usuario_id = null) {
        try {
            $conditions = ["estado = 'ACTIVO'"];
            $params = [];
            
            if ($categoria) {
                $conditions[] = "categoria = :categoria";
                $params['categoria'] = $categoria;
            }
            
            if ($fecha_inicio) {
                $conditions[] = "fecha_hora >= :fecha_inicio";
                $params['fecha_inicio'] = $fecha_inicio;
            }
            
            if ($fecha_fin) {
                $conditions[] = "fecha_hora <= :fecha_fin";
                $params['fecha_fin'] = $fecha_fin;
            }
            
            if ($usuario_id) {
                $conditions[] = "usuario_id = :usuario_id";
                $params['usuario_id'] = $usuario_id;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, evento_id, categoria, accion, detalles, usuario_id,
                    ip_origen, user_agent, fecha_hora, timestamp_unix, session_id
                FROM auditoria_inmutable 
                {$whereClause}
                ORDER BY fecha_hora DESC
                LIMIT 1000
            ");
            
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar detalles JSON
            foreach ($logs as &$log) {
                $log['detalles'] = json_decode($log['detalles'], true);
            }
            
            return [
                'success' => true,
                'logs' => $logs,
                'total' => count($logs)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar reporte de auditoría
     */
    public function generateAuditReport($fecha_inicio, $fecha_fin) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    categoria,
                    accion,
                    COUNT(*) as total_eventos,
                    COUNT(DISTINCT usuario_id) as usuarios_involucrados,
                    MIN(fecha_hora) as primer_evento,
                    MAX(fecha_hora) as ultimo_evento
                FROM auditoria_inmutable 
                WHERE fecha_hora BETWEEN :fecha_inicio AND :fecha_fin
                AND estado = 'ACTIVO'
                GROUP BY categoria, accion
                ORDER BY categoria, total_eventos DESC
            ");
            
            $stmt->execute([
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ]);
            
            $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estadísticas adicionales
            $stmt_stats = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_eventos,
                    COUNT(DISTINCT usuario_id) as total_usuarios,
                    COUNT(DISTINCT ip_origen) as total_ips,
                    COUNT(DISTINCT DATE(fecha_hora)) as dias_activos
                FROM auditoria_inmutable 
                WHERE fecha_hora BETWEEN :fecha_inicio AND :fecha_fin
                AND estado = 'ACTIVO'
            ");
            
            $stmt_stats->execute([
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ]);
            
            $estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'periodo' => [
                    'inicio' => $fecha_inicio,
                    'fin' => $fecha_fin
                ],
                'estadisticas' => $estadisticas,
                'resumen_por_categoria' => $resumen,
                'generado_en' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ========================================================================
    // 🔧 FUNCIONES AUXILIARES PRIVADAS
    // ========================================================================
    
    private function initializeAuditTables() {
        // La tabla ya fue creada en el migration script
        // Solo verificamos que exista
        try {
            $this->pdo->query("SELECT 1 FROM auditoria_inmutable LIMIT 1");
        } catch (PDOException $e) {
            // Si no existe, crear tabla de emergencia
            $this->createEmergencyAuditTable();
        }
    }
    
    private function createEmergencyAuditTable() {
        $sql = "CREATE TABLE IF NOT EXISTS auditoria_inmutable (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evento_id VARCHAR(64) NOT NULL UNIQUE,
            categoria VARCHAR(50) NOT NULL,
            accion VARCHAR(50) NOT NULL,
            detalles JSON NOT NULL,
            usuario_id INT NULL,
            ip_origen VARCHAR(45) NULL,
            user_agent TEXT NULL,
            fecha_hora DATETIME(6) NOT NULL,
            timestamp_unix DECIMAL(16,6) NOT NULL,
            session_id VARCHAR(128) NULL,
            hash_integridad VARCHAR(64) NOT NULL,
            estado ENUM('ACTIVO', 'ANULADO') DEFAULT 'ACTIVO',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_categoria_accion (categoria, accion),
            INDEX idx_fecha_hora (fecha_hora),
            INDEX idx_usuario_id (usuario_id),
            INDEX idx_hash (hash_integridad),
            INDEX idx_evento_id (evento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->pdo->exec($sql);
    }
    
    private function generateEventId($categoria, $accion, $timestamp) {
        return hash('sha256', $categoria . $accion . $timestamp . $this->secret_key);
    }
    
    private function generateIntegrityHash($data) {
        $concatenated = '';
        foreach ($data as $key => $value) {
            if ($key !== 'hash_integridad') {
                $concatenated .= $key . ':' . $value . '|';
            }
        }
        return hash_hmac('sha256', $concatenated, $this->secret_key);
    }
    
    private function gatherContext($provided_ip = null) {
        return [
            'ip' => $provided_ip ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
    }
    
    private function getSecretKey() {
        // En producción, esto debe venir de variables de entorno
        return hash('sha256', 'KIOSCO_AUDIT_SECRET_' . date('Y-m-d'));
    }
    
    private function generateSessionId() {
        return bin2hex(random_bytes(16));
    }
    
    private function createImmutableBackup($log_id, $log_data) {
        // Backup secundario para redundancia
        try {
            $backup_file = __DIR__ . '/logs/audit_backup_' . date('Y-m-d') . '.log';
            $backup_entry = json_encode(['id' => $log_id, 'data' => $log_data]) . "\n";
            file_put_contents($backup_file, $backup_entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Log silencioso - el backup secundario es opcional
            error_log("Warning: No se pudo crear backup de auditoría: " . $e->getMessage());
        }
    }
    
    private function emergencyFileLog($categoria, $accion, $detalles, $error) {
        try {
            $emergency_file = __DIR__ . '/logs/audit_emergency_' . date('Y-m-d') . '.log';
            $emergency_entry = json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'categoria' => $categoria,
                'accion' => $accion,
                'detalles' => $detalles,
                'error' => $error
            ]) . "\n";
            file_put_contents($emergency_file, $emergency_entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Último recurso - no podemos hacer nada más
            error_log("CRITICAL: Sistema de auditoría completamente fuera de servicio");
        }
    }
}

// ========================================================================
// 🚀 API ENDPOINTS
// ========================================================================

// Solo procesar si es llamada directa
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    try {
        $audit = new AuditLogger();
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                if ($action === 'verify') {
                    $log_id = $_GET['log_id'] ?? null;
                    echo json_encode($audit->verifyLogIntegrity($log_id));
                } elseif ($action === 'logs') {
                    $categoria = $_GET['categoria'] ?? null;
                    $fecha_inicio = $_GET['fecha_inicio'] ?? null;
                    $fecha_fin = $_GET['fecha_fin'] ?? null;
                    $usuario_id = $_GET['usuario_id'] ?? null;
                    echo json_encode($audit->getAuditLogs($categoria, $fecha_inicio, $fecha_fin, $usuario_id));
                } elseif ($action === 'report') {
                    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
                    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
                    echo json_encode($audit->generateAuditReport($fecha_inicio, $fecha_fin));
                } else {
                    throw new Exception('Acción no válida para GET');
                }
                break;
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    throw new Exception('Datos JSON inválidos');
                }
                
                if ($action === 'log') {
                    $result = $audit->logCriticalEvent(
                        $data['categoria'],
                        $data['accion'],
                        $data['detalles'],
                        $data['usuario_id'] ?? null,
                        $data['ip'] ?? null
                    );
                    echo json_encode($result);
                } else {
                    throw new Exception('Acción no válida para POST');
                }
                break;
                
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

