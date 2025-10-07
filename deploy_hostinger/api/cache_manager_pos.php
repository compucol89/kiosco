<?php
/**
 * 🚀 CACHE MANAGER PARA PUNTO DE VENTA
 * 
 * Sistema de cache optimizado para operaciones frecuentes del POS:
 * - Cache de productos con stock
 * - Cache de categorías
 * - Cache de estadísticas
 * - Invalidación inteligente
 */

class CacheManagerPOS {
    
    private $cacheDir;
    private $defaultTTL = 300; // 5 minutos por defecto
    private $maxMemory = 50 * 1024 * 1024; // 50MB máximo
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/cache/pos/';
        $this->ensureCacheDirectory();
    }
    
    /**
     * 📁 ASEGURAR QUE EXISTE EL DIRECTORIO DE CACHE
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * 💾 OBTENER DATOS DEL CACHE
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = @file_get_contents($filename);
        if ($content === false) {
            return null;
        }
        
        $data = @json_decode($content, true);
        if (!$data || !isset($data['expires'], $data['data'])) {
            $this->delete($key);
            return null;
        }
        
        // Verificar expiración
        if (time() > $data['expires']) {
            $this->delete($key);
            return null;
        }
        
        return $data['data'];
    }
    
    /**
     * 💾 GUARDAR DATOS EN CACHE
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?: $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $cacheData = [
            'expires' => time() + $ttl,
            'created' => time(),
            'key' => $key,
            'data' => $data
        ];
        
        $content = json_encode($cacheData, JSON_UNESCAPED_UNICODE);
        
        // Verificar memoria disponible
        if (strlen($content) > $this->maxMemory) {
            error_log("Cache entry too large for key: $key");
            return false;
        }
        
        $result = @file_put_contents($filename, $content, LOCK_EX);
        
        if ($result === false) {
            error_log("Failed to write cache file: $filename");
            return false;
        }
        
        return true;
    }
    
    /**
     * 🗑️ ELIMINAR ENTRADA DEL CACHE
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        return true;
    }
    
    /**
     * 🧹 LIMPIAR CACHE EXPIRADO
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content) {
                $data = @json_decode($content, true);
                if (!$data || !isset($data['expires']) || time() > $data['expires']) {
                    @unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * 🔄 INVALIDAR CACHE POR PATRÓN
     */
    public function invalidatePattern($pattern) {
        $files = glob($this->cacheDir . '*' . $pattern . '*.cache');
        $invalidated = 0;
        
        foreach ($files as $file) {
            if (@unlink($file)) {
                $invalidated++;
            }
        }
        
        return $invalidated;
    }
    
    /**
     * 📊 OBTENER ESTADÍSTICAS DEL CACHE
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $totalFiles = count($files);
        $expired = 0;
        $valid = 0;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            
            $content = @file_get_contents($file);
            if ($content) {
                $data = @json_decode($content, true);
                if ($data && isset($data['expires'])) {
                    if (time() > $data['expires']) {
                        $expired++;
                    } else {
                        $valid++;
                    }
                }
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'valid_entries' => $valid,
            'expired_entries' => $expired,
            'cache_directory' => $this->cacheDir,
            'max_memory_mb' => round($this->maxMemory / 1024 / 1024, 2)
        ];
    }
    
    /**
     * 🔧 OBTENER NOMBRE DE ARCHIVO DE CACHE
     */
    private function getCacheFilename($key) {
        $hash = hash('sha256', $key);
        return $this->cacheDir . $hash . '.cache';
    }
    
    /**
     * 🎯 CACHE ESPECÍFICO PARA PRODUCTOS POS
     */
    public function cacheProductosPOS($filtros, $data, $ttl = 180) {
        $key = 'productos_pos_' . md5(json_encode($filtros));
        return $this->set($key, $data, $ttl);
    }
    
    /**
     * 📦 OBTENER PRODUCTOS DEL CACHE
     */
    public function getProductosPOS($filtros) {
        $key = 'productos_pos_' . md5(json_encode($filtros));
        return $this->get($key);
    }
    
    /**
     * 📊 CACHE PARA ESTADÍSTICAS DE STOCK
     */
    public function cacheEstadisticasStock($data, $ttl = 120) {
        return $this->set('estadisticas_stock', $data, $ttl);
    }
    
    /**
     * 📊 OBTENER ESTADÍSTICAS DEL CACHE
     */
    public function getEstadisticasStock() {
        return $this->get('estadisticas_stock');
    }
    
    /**
     * 📂 CACHE PARA CATEGORÍAS
     */
    public function cacheCategorias($categorias, $ttl = 600) {
        return $this->set('categorias_productos', $categorias, $ttl);
    }
    
    /**
     * 📂 OBTENER CATEGORÍAS DEL CACHE
     */
    public function getCategorias() {
        return $this->get('categorias_productos');
    }
    
    /**
     * ⚡ INVALIDAR CACHE RELACIONADO CON PRODUCTOS
     */
    public function invalidarCacheProductos() {
        $this->delete('estadisticas_stock');
        $this->delete('categorias_productos');
        return $this->invalidatePattern('productos_pos_');
    }
    
    /**
     * 🔥 LIMPIAR TODO EL CACHE
     */
    public function flush() {
        $files = glob($this->cacheDir . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * 🎯 CACHE CON BLOQUEO PARA EVITAR CONDICIONES DE CARRERA
     */
    public function getOrSet($key, $callback, $ttl = null) {
        $data = $this->get($key);
        
        if ($data !== null) {
            return $data;
        }
        
        // Archivo de bloqueo
        $lockFile = $this->getCacheFilename($key . '_lock');
        $lockHandle = @fopen($lockFile, 'w');
        
        if (!$lockHandle || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            // No se pudo obtener el bloqueo, esperar un poco y devolver datos del cache si existen
            usleep(100000); // 100ms
            $data = $this->get($key);
            if ($data !== null) {
                if ($lockHandle) {
                    @fclose($lockHandle);
                    @unlink($lockFile);
                }
                return $data;
            }
        }
        
        try {
            // Ejecutar callback para obtener datos frescos
            $data = $callback();
            
            if ($data !== null) {
                $this->set($key, $data, $ttl);
            }
            
            return $data;
            
        } finally {
            if ($lockHandle) {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
                @unlink($lockFile);
            }
        }
    }
}

/**
 * 🎯 CLASE SINGLETON PARA GESTIÓN GLOBAL DEL CACHE
 */
class POSCacheManager {
    
    private static $instance = null;
    private $cache;
    
    private function __construct() {
        $this->cache = new CacheManagerPOS();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCache() {
        return $this->cache;
    }
    
    /**
     * 🧹 MANTENIMIENTO AUTOMÁTICO DEL CACHE
     */
    public function performMaintenance() {
        $stats = $this->cache->getStats();
        
        // Limpiar expirados si hay muchos
        if ($stats['expired_entries'] > 50) {
            $cleaned = $this->cache->cleanExpired();
            error_log("Cache maintenance: cleaned $cleaned expired entries");
        }
        
        // Limpiar si el cache es muy grande
        if ($stats['total_size_mb'] > 40) {
            $this->cache->cleanExpired();
            
            // Si sigue siendo muy grande, hacer flush parcial
            if ($this->cache->getStats()['total_size_mb'] > 40) {
                $deleted = $this->cache->flush();
                error_log("Cache maintenance: emergency flush deleted $deleted files");
            }
        }
        
        return $stats;
    }
}

// Si se ejecuta directamente, realizar mantenimiento
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $manager = POSCacheManager::getInstance();
    $stats = $manager->performMaintenance();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache maintenance completed',
        'stats' => $stats
    ], JSON_PRETTY_PRINT);
}
?>
