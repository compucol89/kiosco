<?php
/**
 * MANAGER DE CACHÉ AFIP - OPTIMIZACIÓN DE PERFORMANCE
 * 
 * Sistema de caché inteligente para tokens de acceso, respuestas AFIP
 * y datos frecuentemente accedidos para mejorar tiempos de respuesta
 */

class AFIPCacheManager {
    
    private $cache_dir;
    private $default_ttl;
    
    public function __construct($cache_dir = 'cache/afip', $default_ttl = 3600) {
        $this->cache_dir = $cache_dir;
        $this->default_ttl = $default_ttl; // 1 hora por defecto
        
        // Crear directorio de caché si no existe
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * 🏎️ OBTENER DESDE CACHÉ CON VALIDACIÓN
     */
    public function get($key, $default = null) {
        $cache_file = $this->getCacheFilePath($key);
        
        if (!file_exists($cache_file)) {
            return $default;
        }
        
        $cache_data = json_decode(file_get_contents($cache_file), true);
        
        if (!$cache_data || !isset($cache_data['expires_at']) || !isset($cache_data['data'])) {
            // Caché inválido, eliminar
            unlink($cache_file);
            return $default;
        }
        
        // Verificar expiración
        if (time() > $cache_data['expires_at']) {
            unlink($cache_file);
            return $default;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * 💾 GUARDAR EN CACHÉ CON TTL
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;
        $cache_file = $this->getCacheFilePath($key);
        
        $cache_data = [
            'data' => $data,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'ttl' => $ttl
        ];
        
        return file_put_contents($cache_file, json_encode($cache_data, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * 🔄 REMEMBER - OBTENER O EJECUTAR FUNCIÓN Y CACHEAR
     */
    public function remember($key, $callback, $ttl = null) {
        $cached_value = $this->get($key);
        
        if ($cached_value !== null) {
            return $cached_value;
        }
        
        // Ejecutar callback y cachear resultado
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * 🗑️ ELIMINAR CACHÉ ESPECÍFICO
     */
    public function forget($key) {
        $cache_file = $this->getCacheFilePath($key);
        
        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }
        
        return true;
    }
    
    /**
     * 🧹 LIMPIAR CACHÉ EXPIRADO
     */
    public function cleanExpired() {
        $files = glob($this->cache_dir . '/*.json');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $cache_data = json_decode(file_get_contents($file), true);
            
            if (!$cache_data || time() > ($cache_data['expires_at'] ?? 0)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * 🚮 LIMPIAR TODO EL CACHÉ
     */
    public function flush() {
        $files = glob($this->cache_dir . '/*.json');
        $deleted = 0;
        
        foreach ($files as $file) {
            unlink($file);
            $deleted++;
        }
        
        return $deleted;
    }
    
    /**
     * 📊 ESTADÍSTICAS DEL CACHÉ
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*.json');
        $total_files = count($files);
        $total_size = 0;
        $expired_count = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            
            $cache_data = json_decode(file_get_contents($file), true);
            if ($cache_data && time() > ($cache_data['expires_at'] ?? 0)) {
                $expired_count++;
            }
        }
        
        return [
            'total_entries' => $total_files,
            'total_size_bytes' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'expired_entries' => $expired_count,
            'valid_entries' => $total_files - $expired_count,
            'cache_directory' => $this->cache_dir
        ];
    }
    
    /**
     * 🔗 GENERAR RUTA DE ARCHIVO CACHÉ
     */
    private function getCacheFilePath($key) {
        $safe_key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cache_dir . '/' . $safe_key . '.json';
    }
    
    /**
     * 🎯 MÉTODOS ESPECÍFICOS PARA AFIP
     */
    
    /**
     * 🔐 CACHEAR TOKEN DE ACCESO AFIP
     */
    public function cacheAccessToken($token, $expires_in = 3600) {
        return $this->set('afip_access_token', [
            'token' => $token,
            'obtained_at' => time()
        ], $expires_in - 60); // Expirar 1 minuto antes para seguridad
    }
    
    /**
     * 🔑 OBTENER TOKEN DE ACCESO CACHEADO
     */
    public function getCachedAccessToken() {
        return $this->get('afip_access_token');
    }
    
    /**
     * 📄 CACHEAR RESPUESTA DE CAE
     */
    public function cacheCAEResponse($venta_id, $cae_data, $ttl = 86400) { // 24 horas
        return $this->set("cae_response_{$venta_id}", $cae_data, $ttl);
    }
    
    /**
     * 📋 OBTENER RESPUESTA CAE CACHEADA
     */
    public function getCachedCAEResponse($venta_id) {
        return $this->get("cae_response_{$venta_id}");
    }
    
    /**
     * 🏢 CACHEAR CONFIGURACIÓN FISCAL
     */
    public function cacheFiscalConfig($config, $ttl = 3600) {
        return $this->set('fiscal_config', $config, $ttl);
    }
    
    /**
     * ⚙️ OBTENER CONFIGURACIÓN FISCAL CACHEADA
     */
    public function getCachedFiscalConfig() {
        return $this->get('fiscal_config');
    }
    
    /**
     * 📈 CACHEAR MÉTRICAS DE PERFORMANCE
     */
    public function cachePerformanceMetrics($metrics, $ttl = 1800) { // 30 minutos
        return $this->set('performance_metrics', $metrics, $ttl);
    }
    
    /**
     * 📊 OBTENER MÉTRICAS CACHEADAS
     */
    public function getCachedPerformanceMetrics() {
        return $this->get('performance_metrics');
    }
}

/**
 * 🌟 INSTANCIA GLOBAL DEL CACHE MANAGER
 */
function getAFIPCacheManager() {
    static $cache_manager = null;
    
    if ($cache_manager === null) {
        $cache_manager = new AFIPCacheManager();
    }
    
    return $cache_manager;
}

/**
 * 🔧 FUNCIONES HELPER PARA CACHÉ RÁPIDO
 */
function cacheAFIP($key, $data, $ttl = null) {
    return getAFIPCacheManager()->set($key, $data, $ttl);
}

function getCachedAFIP($key, $default = null) {
    return getAFIPCacheManager()->get($key, $default);
}

function rememberAFIP($key, $callback, $ttl = null) {
    return getAFIPCacheManager()->remember($key, $callback, $ttl);
}

function forgetAFIP($key) {
    return getAFIPCacheManager()->forget($key);
}

/**
 * 🧹 ENDPOINT PARA MANTENIMIENTO DE CACHÉ
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $cache = getAFIPCacheManager();
    
    switch ($action) {
        case 'clean':
            $cleaned = $cache->cleanExpired();
            echo json_encode([
                'success' => true,
                'message' => "Se limpiaron {$cleaned} entradas expiradas"
            ]);
            break;
            
        case 'flush':
            $deleted = $cache->flush();
            echo json_encode([
                'success' => true,
                'message' => "Se eliminaron {$deleted} entradas del caché"
            ]);
            break;
            
        case 'stats':
            $stats = $cache->getStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? 'stats';
    $cache = getAFIPCacheManager();
    
    if ($action === 'stats') {
        $stats = $cache->getStats();
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Acción no válida para GET'
        ]);
    }
}
?> 