<?php
/**
 * SISTEMA DE MONITOREO BÁSICO
 * Detecta ataques y anomalías en tiempo real
 */

class SecurityMonitor {
    
    private $logFile;
    private $alertThreshold = 10; // Alertas por minuto
    
    public function __construct() {
        $this->logFile = __DIR__ . '/logs/security.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function checkForSQLInjection($input) {
        $patterns = [
            '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b)/i',
            '/(\-\-|\#|\/\*|\*\/)/i',
            '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('SQL_INJECTION_ATTEMPT', [
                    'input' => substr($input, 0, 200),
                    'pattern' => $pattern
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    public function checkRateLimit($identifier = null) {
        $identifier = $identifier ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $currentTime = time();
        $timeWindow = 60; // 1 minuto
        
        $recentRequests = $this->getRecentRequests($identifier, $currentTime - $timeWindow);
        
        if (count($recentRequests) > $this->alertThreshold) {
            $this->logSecurityEvent('RATE_LIMIT_EXCEEDED', [
                'identifier' => $identifier,
                'requests_count' => count($recentRequests)
            ]);
            return false;
        }
        
        $this->recordRequest($identifier, $currentTime);
        return true;
    }
    
    private function recordRequest($identifier, $timestamp) {
        $record = json_encode([
            'identifier' => $identifier,
            'timestamp' => $timestamp
        ]) . "\n";
        
        file_put_contents(__DIR__ . '/logs/requests.log', $record, FILE_APPEND | LOCK_EX);
    }
    
    private function getRecentRequests($identifier, $since) {
        $logFile = __DIR__ . '/logs/requests.log';
        if (!file_exists($logFile)) return [];
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $recentRequests = [];
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data && $data['identifier'] === $identifier && $data['timestamp'] >= $since) {
                $recentRequests[] = $data;
            }
        }
        
        return $recentRequests;
    }
    
    private function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'details' => $details
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    public function generateSecurityReport() {
        if (!file_exists($this->logFile)) {
            return ['error' => 'No security logs found'];
        }
        
        $logs = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $events = [];
        $ipCounts = [];
        
        foreach ($logs as $log) {
            $data = json_decode($log, true);
            if ($data) {
                $events[] = $data;
                $ip = $data['ip'];
                $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
            }
        }
        
        arsort($ipCounts);
        
        return [
            'total_events' => count($events),
            'recent_events' => array_slice($events, -10),
            'top_ips' => array_slice($ipCounts, 0, 5, true),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
?>