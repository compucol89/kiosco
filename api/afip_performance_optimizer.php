<?php
/**
 * OPTIMIZADOR DE PERFORMANCE AFIP AVANZADO
 * 
 * Sistema de optimización con connection pooling, caché predictivo,
 * compresión de requests y timeout adaptativo
 */

require_once 'afip_cache_manager.php';

class AFIPPerformanceOptimizer {
    
    private $cache;
    private $connectionPool;
    private $performanceMetrics;
    private $config;
    
    public function __construct() {
        $this->cache = getAFIPCacheManager();
        $this->connectionPool = new AFIPConnectionPool();
        $this->performanceMetrics = new AFIPPerformanceMetrics();
        
        $this->config = [
            'max_connections' => 5,
            'connection_timeout' => 10,
            'read_timeout' => 15,
            'retry_attempts' => 3,
            'backoff_multiplier' => 1.5,
            'compression_enabled' => true,
            'predictive_cache_enabled' => true,
            'metrics_enabled' => true
        ];
        
        $this->initializeOptimizations();
    }
    
    /**
     * 🚀 REALIZAR REQUEST OPTIMIZADO A AFIP
     */
    public function optimizedAFIPRequest($endpoint, $data, $options = []) {
        $start_time = microtime(true);
        $request_id = uniqid('afip_req_');
        
        // Configuración de request
        $config = array_merge($this->config, $options);
        
        // Verificar caché predictivo
        if ($config['predictive_cache_enabled']) {
            $cached_response = $this->checkPredictiveCache($endpoint, $data);
            if ($cached_response) {
                $this->recordMetrics($request_id, 'cache_hit', microtime(true) - $start_time);
                return $cached_response;
            }
        }
        
        // Preparar request optimizado
        $optimized_data = $this->optimizeRequestData($data, $config);
        
        try {
            // Usar connection pool
            $connection = $this->connectionPool->getConnection();
            
            // Ejecutar request con retry automático
            $response = $this->executeWithRetry(
                $connection, 
                $endpoint, 
                $optimized_data, 
                $config
            );
            
            // Procesar respuesta
            $processed_response = $this->processResponse($response, $config);
            
            // Cachear para predicción futura
            if ($config['predictive_cache_enabled'] && $processed_response['success']) {
                $this->cachePredictiveResponse($endpoint, $data, $processed_response);
            }
            
            // Liberar conexión
            $this->connectionPool->releaseConnection($connection);
            
            // Registrar métricas
            $execution_time = microtime(true) - $start_time;
            $this->recordMetrics($request_id, 'success', $execution_time, $processed_response);
            
            return $processed_response;
            
        } catch (Exception $e) {
            $execution_time = microtime(true) - $start_time;
            $this->recordMetrics($request_id, 'error', $execution_time, null, $e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * 🔄 EJECUTAR CON RETRY INTELIGENTE
     */
    private function executeWithRetry($connection, $endpoint, $data, $config) {
        $attempt = 1;
        $last_error = null;
        
        while ($attempt <= $config['retry_attempts']) {
            try {
                // Ajustar timeout dinámicamente
                $timeout = $this->calculateAdaptiveTimeout($attempt, $config);
                
                $response = $this->executeRequest($connection, $endpoint, $data, $timeout);
                
                // Verificar si la respuesta es válida
                if ($this->isValidResponse($response)) {
                    return $response;
                }
                
                throw new Exception('Respuesta inválida de AFIP');
                
            } catch (Exception $e) {
                $last_error = $e;
                
                // Analizar si el error es reintentable
                if (!$this->isRetryableError($e) || $attempt >= $config['retry_attempts']) {
                    throw $e;
                }
                
                // Backoff exponencial
                $delay = $config['backoff_multiplier'] ** ($attempt - 1);
                usleep($delay * 1000000); // Convertir a microsegundos
                
                $attempt++;
            }
        }
        
        throw $last_error ?: new Exception('Error desconocido en requests AFIP');
    }
    
    /**
     * 📊 CACHÉ PREDICTIVO INTELIGENTE
     */
    private function checkPredictiveCache($endpoint, $data) {
        // Generar hash único para la combinación endpoint + datos
        $cache_key = 'predictive_' . md5($endpoint . serialize($data));
        
        $cached = $this->cache->get($cache_key);
        
        if ($cached && $this->isPredictiveCacheValid($cached, $data)) {
            // Registrar hit de caché predictivo
            $this->performanceMetrics->recordCacheHit('predictive');
            return $cached['response'];
        }
        
        return null;
    }
    
    /**
     * 💾 CACHEAR RESPUESTA PREDICTIVA
     */
    private function cachePredictiveResponse($endpoint, $data, $response) {
        $cache_key = 'predictive_' . md5($endpoint . serialize($data));
        
        $cache_data = [
            'response' => $response,
            'original_data' => $data,
            'timestamp' => time(),
            'endpoint' => $endpoint
        ];
        
        // Cachear por tiempo variable según el tipo de respuesta
        $ttl = $this->calculatePredictiveTTL($endpoint, $response);
        
        $this->cache->set($cache_key, $cache_data, $ttl);
    }
    
    /**
     * ⚙️ OPTIMIZAR DATOS DE REQUEST
     */
    private function optimizeRequestData($data, $config) {
        $optimized = $data;
        
        // Compresión si está habilitada
        if ($config['compression_enabled']) {
            $optimized = $this->compressRequestData($optimized);
        }
        
        // Minificación de datos innecesarios
        $optimized = $this->minifyRequestData($optimized);
        
        // Optimización de estructura
        $optimized = $this->optimizeDataStructure($optimized);
        
        return $optimized;
    }
    
    /**
     * 🗜️ COMPRIMIR DATOS DE REQUEST
     */
    private function compressRequestData($data) {
        // Comprimir strings largos
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) && strlen($value) > 100) {
                    $data[$key] = gzcompress($value, 6);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 🎯 CALCULAR TIMEOUT ADAPTATIVO
     */
    private function calculateAdaptiveTimeout($attempt, $config) {
        $base_timeout = $config['connection_timeout'];
        
        // Incrementar timeout en reintentos
        $adaptive_timeout = $base_timeout * (1 + ($attempt - 1) * 0.5);
        
        // Aplicar límites
        return min(max($adaptive_timeout, 5), 30);
    }
    
    /**
     * ✅ VERIFICAR SI ERROR ES REINTENTABLE
     */
    private function isRetryableError($error) {
        $retryable_patterns = [
            'connection timeout',
            'temporary failure',
            'service temporarily unavailable',
            'HTTP 502',
            'HTTP 503',
            'HTTP 504'
        ];
        
        $error_message = strtolower($error->getMessage());
        
        foreach ($retryable_patterns as $pattern) {
            if (strpos($error_message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 📈 REGISTRAR MÉTRICAS DE PERFORMANCE
     */
    private function recordMetrics($request_id, $status, $execution_time, $response = null, $error = null) {
        if (!$this->config['metrics_enabled']) return;
        
        $metrics_data = [
            'request_id' => $request_id,
            'timestamp' => time(),
            'status' => $status,
            'execution_time' => round($execution_time, 4),
            'response_size' => $response ? strlen(json_encode($response)) : 0,
            'error' => $error
        ];
        
        $this->performanceMetrics->record($metrics_data);
    }
    
    /**
     * 📊 OBTENER ESTADÍSTICAS DE PERFORMANCE
     */
    public function getPerformanceStats($period_hours = 24) {
        return $this->performanceMetrics->getStats($period_hours);
    }
    
    /**
     * 🧹 CLEANUP DE RECURSOS
     */
    public function cleanup() {
        $this->connectionPool->closeAllConnections();
        $this->performanceMetrics->cleanup();
    }
    
    /**
     * 🚀 INICIALIZAR OPTIMIZACIONES
     */
    private function initializeOptimizations() {
        // Configurar opciones de cURL para performance
        $this->setCurlOptimizations();
        
        // Precargar certificados si es necesario
        $this->preloadCertificates();
        
        // Inicializar métricas
        $this->performanceMetrics->initialize();
    }
    
    private function setCurlOptimizations() {
        // Configuraciones globales de cURL para mejor performance
        if (function_exists('curl_setopt_array')) {
            $default_options = [
                CURLOPT_TCP_NODELAY => true,
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 10,
                CURLOPT_TCP_KEEPINTVL => 5,
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false
            ];
            
            // Aplicar configuraciones (esto es conceptual)
            $this->config['curl_defaults'] = $default_options;
        }
    }
    
    private function preloadCertificates() {
        // Precargar certificados AFIP para conexiones más rápidas
        // Implementación específica según el SDK usado
    }
}

/**
 * POOL DE CONEXIONES AFIP
 */
class AFIPConnectionPool {
    private $connections = [];
    private $max_connections = 5;
    private $current_connections = 0;
    
    public function getConnection() {
        // Implementación básica de connection pooling
        if ($this->current_connections < $this->max_connections) {
            $connection = $this->createNewConnection();
            $this->connections[] = $connection;
            $this->current_connections++;
            return $connection;
        }
        
        // Reusar conexión existente
        return $this->connections[array_rand($this->connections)];
    }
    
    public function releaseConnection($connection) {
        // Marcar conexión como disponible para reuso
        // En implementación real, manejar estado de conexión
    }
    
    public function closeAllConnections() {
        $this->connections = [];
        $this->current_connections = 0;
    }
    
    private function createNewConnection() {
        // Crear nueva conexión optimizada
        return [
            'id' => uniqid('conn_'),
            'created_at' => time(),
            'last_used' => time(),
            'status' => 'active'
        ];
    }
}

/**
 * MÉTRICAS DE PERFORMANCE AFIP
 */
class AFIPPerformanceMetrics {
    private $metrics = [];
    
    public function initialize() {
        // Inicializar almacenamiento de métricas
    }
    
    public function record($data) {
        $this->metrics[] = $data;
        
        // En implementación real, persistir en base de datos
        $this->persistMetrics($data);
    }
    
    public function recordCacheHit($type) {
        $this->record([
            'type' => 'cache_hit',
            'cache_type' => $type,
            'timestamp' => time()
        ]);
    }
    
    public function getStats($period_hours = 24) {
        $cutoff = time() - ($period_hours * 3600);
        
        $recent_metrics = array_filter($this->metrics, function($metric) use ($cutoff) {
            return $metric['timestamp'] >= $cutoff;
        });
        
        return [
            'total_requests' => count($recent_metrics),
            'avg_response_time' => $this->calculateAverage($recent_metrics, 'execution_time'),
            'success_rate' => $this->calculateSuccessRate($recent_metrics),
            'cache_hit_rate' => $this->calculateCacheHitRate($recent_metrics)
        ];
    }
    
    public function cleanup() {
        // Limpiar métricas antiguas
        $cutoff = time() - (7 * 24 * 3600); // 7 días
        
        $this->metrics = array_filter($this->metrics, function($metric) use ($cutoff) {
            return $metric['timestamp'] >= $cutoff;
        });
    }
    
    private function persistMetrics($data) {
        // Persistir métricas en archivo o base de datos
        $log_file = 'logs/afip_performance.log';
        $log_entry = date('Y-m-d H:i:s') . ' ' . json_encode($data) . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    private function calculateAverage($metrics, $field) {
        if (empty($metrics)) return 0;
        
        $sum = array_sum(array_column($metrics, $field));
        return $sum / count($metrics);
    }
    
    private function calculateSuccessRate($metrics) {
        if (empty($metrics)) return 0;
        
        $successful = array_filter($metrics, function($metric) {
            return $metric['status'] === 'success';
        });
        
        return (count($successful) / count($metrics)) * 100;
    }
    
    private function calculateCacheHitRate($metrics) {
        $cache_hits = array_filter($metrics, function($metric) {
            return $metric['status'] === 'cache_hit';
        });
        
        $total_requests = array_filter($metrics, function($metric) {
            return in_array($metric['status'], ['success', 'cache_hit']);
        });
        
        if (empty($total_requests)) return 0;
        
        return (count($cache_hits) / count($total_requests)) * 100;
    }
}

/**
 * INSTANCIA GLOBAL DEL OPTIMIZADOR
 */
function getAFIPOptimizer() {
    static $optimizer = null;
    
    if ($optimizer === null) {
        $optimizer = new AFIPPerformanceOptimizer();
    }
    
    return $optimizer;
}
?> 