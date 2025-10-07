<?php
/**
 * 📊 PERFORMANCE OPTIMIZER - ENTERPRISE MONITORING
 * 
 * Sistema de monitoreo de performance en tiempo real para APIs críticas
 * Targets: <25ms queries, >95% cache hit ratio, <100ms response time
 */

class PerformanceMonitor {
    private $metrics = [];
    private $cache_stats = [];
    private $query_stats = [];
    
    public function __construct() {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['memory_start'] = memory_get_usage(true);
    }
    
    /**
     * 📈 REGISTRAR TIEMPO DE QUERY
     */
    public function recordQueryTime($query_name, $time_ms) {
        if (!isset($this->query_stats[$query_name])) {
            $this->query_stats[$query_name] = [
                'count' => 0,
                'total_time' => 0,
                'min_time' => PHP_FLOAT_MAX,
                'max_time' => 0,
                'slow_queries' => 0
            ];
        }
        
        $stats = &$this->query_stats[$query_name];
        $stats['count']++;
        $stats['total_time'] += $time_ms;
        $stats['min_time'] = min($stats['min_time'], $time_ms);
        $stats['max_time'] = max($stats['max_time'], $time_ms);
        
        // Contar queries lentas (>25ms)
        if ($time_ms > 25) {
            $stats['slow_queries']++;
        }
        
        // Log a archivo para análisis
        $this->logMetric('query_performance', [
            'query' => $query_name,
            'time_ms' => $time_ms,
            'timestamp' => time(),
            'is_slow' => $time_ms > 25
        ]);
    }
    
    /**
     * 💾 ESTADÍSTICAS DE CACHÉ
     */
    public function recordCacheHit($cache_type) {
        if (!isset($this->cache_stats[$cache_type])) {
            $this->cache_stats[$cache_type] = ['hits' => 0, 'misses' => 0];
        }
        $this->cache_stats[$cache_type]['hits']++;
    }
    
    public function recordCacheMiss($cache_type) {
        if (!isset($this->cache_stats[$cache_type])) {
            $this->cache_stats[$cache_type] = ['hits' => 0, 'misses' => 0];
        }
        $this->cache_stats[$cache_type]['misses']++;
    }
    
    /**
     * 🌐 PERFORMANCE DE RESPUESTA API
     */
    public function recordAPIResponse($api_name, $response_time_ms, $from_cache) {
        $this->logMetric('api_performance', [
            'api' => $api_name,
            'response_time_ms' => $response_time_ms,
            'from_cache' => $from_cache,
            'timestamp' => time(),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }
    
    /**
     * 📊 GENERAR REPORTE DE PERFORMANCE
     */
    public function getPerformanceReport() {
        $total_time = (microtime(true) - $this->metrics['start_time']) * 1000;
        $memory_used = (memory_get_usage(true) - $this->metrics['memory_start']) / 1024 / 1024;
        
        $report = [
            'execution_time_ms' => round($total_time, 2),
            'memory_used_mb' => round($memory_used, 2),
            'queries' => [],
            'cache_performance' => []
        ];
        
        // Estadísticas de queries
        foreach ($this->query_stats as $query_name => $stats) {
            $avg_time = $stats['count'] > 0 ? $stats['total_time'] / $stats['count'] : 0;
            $slow_query_rate = $stats['count'] > 0 ? ($stats['slow_queries'] / $stats['count']) * 100 : 0;
            
            $report['queries'][$query_name] = [
                'count' => $stats['count'],
                'avg_time_ms' => round($avg_time, 2),
                'min_time_ms' => round($stats['min_time'], 2),
                'max_time_ms' => round($stats['max_time'], 2),
                'slow_query_rate_percent' => round($slow_query_rate, 2),
                'performance_grade' => $this->getPerformanceGrade($avg_time)
            ];
        }
        
        // Estadísticas de caché
        foreach ($this->cache_stats as $cache_type => $stats) {
            $total_requests = $stats['hits'] + $stats['misses'];
            $hit_rate = $total_requests > 0 ? ($stats['hits'] / $total_requests) * 100 : 0;
            
            $report['cache_performance'][$cache_type] = [
                'hits' => $stats['hits'],
                'misses' => $stats['misses'],
                'hit_rate_percent' => round($hit_rate, 2),
                'efficiency_grade' => $this->getCacheEfficiencyGrade($hit_rate)
            ];
        }
        
        return $report;
    }
    
    /**
     * 🎯 CALIFICACIÓN DE PERFORMANCE
     */
    private function getPerformanceGrade($avg_time_ms) {
        if ($avg_time_ms < 10) return 'A+';
        if ($avg_time_ms < 25) return 'A';
        if ($avg_time_ms < 50) return 'B';
        if ($avg_time_ms < 100) return 'C';
        return 'F';
    }
    
    private function getCacheEfficiencyGrade($hit_rate) {
        if ($hit_rate >= 95) return 'A+';
        if ($hit_rate >= 90) return 'A';
        if ($hit_rate >= 80) return 'B';
        if ($hit_rate >= 70) return 'C';
        return 'F';
    }
    
    /**
     * 📝 LOGGING DE MÉTRICAS
     */
    private function logMetric($type, $data) {
        $log_entry = [
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
        
        $log_file = __DIR__ . '/logs/performance_metrics.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

/**
 * 🔍 ANALIZADOR DE PERFORMANCE EN TIEMPO REAL
 */
class PerformanceAnalyzer {
    
    /**
     * 📊 ANÁLISIS DE MÉTRICAS DE LAS ÚLTIMAS 24H
     */
    public static function analyzeRecent24Hours() {
        $log_file = __DIR__ . '/logs/performance_metrics.log';
        if (!file_exists($log_file)) {
            return ['error' => 'No performance data available'];
        }
        
        $cutoff_time = time() - (24 * 60 * 60); // 24 horas atrás
        $metrics = [];
        
        $handle = fopen($log_file, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $data = json_decode(trim($line), true);
                if ($data && isset($data['timestamp'])) {
                    $timestamp = strtotime($data['timestamp']);
                    if ($timestamp >= $cutoff_time) {
                        $metrics[] = $data;
                    }
                }
            }
            fclose($handle);
        }
        
        return self::processMetrics($metrics);
    }
    
    /**
     * 🔄 PROCESAR MÉTRICAS RECOPILADAS
     */
    private static function processMetrics($metrics) {
        $analysis = [
            'query_performance' => [],
            'api_performance' => [],
            'cache_performance' => [],
            'alerts' => [],
            'summary' => []
        ];
        
        $query_times = [];
        $api_times = [];
        $cache_stats = [];
        
        foreach ($metrics as $metric) {
            switch ($metric['type']) {
                case 'query_performance':
                    $query_name = $metric['data']['query'];
                    if (!isset($query_times[$query_name])) {
                        $query_times[$query_name] = [];
                    }
                    $query_times[$query_name][] = $metric['data']['time_ms'];
                    
                    // Detectar queries lentas
                    if ($metric['data']['time_ms'] > 25) {
                        $analysis['alerts'][] = [
                            'type' => 'slow_query',
                            'message' => "Slow query detected: {$query_name} took {$metric['data']['time_ms']}ms",
                            'timestamp' => $metric['timestamp'],
                            'severity' => $metric['data']['time_ms'] > 100 ? 'critical' : 'warning'
                        ];
                    }
                    break;
                    
                case 'api_performance':
                    $api_name = $metric['data']['api'];
                    if (!isset($api_times[$api_name])) {
                        $api_times[$api_name] = [];
                    }
                    $api_times[$api_name][] = $metric['data']['response_time_ms'];
                    break;
            }
        }
        
        // Procesar estadísticas de queries
        foreach ($query_times as $query_name => $times) {
            $analysis['query_performance'][$query_name] = [
                'count' => count($times),
                'avg_time_ms' => round(array_sum($times) / count($times), 2),
                'min_time_ms' => min($times),
                'max_time_ms' => max($times),
                'p95_time_ms' => self::percentile($times, 95),
                'p99_time_ms' => self::percentile($times, 99),
                'slow_queries' => count(array_filter($times, function($t) { return $t > 25; }))
            ];
        }
        
        // Procesar estadísticas de APIs
        foreach ($api_times as $api_name => $times) {
            $analysis['api_performance'][$api_name] = [
                'count' => count($times),
                'avg_time_ms' => round(array_sum($times) / count($times), 2),
                'min_time_ms' => min($times),
                'max_time_ms' => max($times),
                'p95_time_ms' => self::percentile($times, 95),
                'p99_time_ms' => self::percentile($times, 99)
            ];
        }
        
        // Generar resumen
        $analysis['summary'] = [
            'total_queries' => array_sum(array_map('count', $query_times)),
            'total_apis' => array_sum(array_map('count', $api_times)),
            'critical_alerts' => count(array_filter($analysis['alerts'], function($a) { 
                return $a['severity'] === 'critical'; 
            })),
            'warning_alerts' => count(array_filter($analysis['alerts'], function($a) { 
                return $a['severity'] === 'warning'; 
            })),
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $analysis;
    }
    
    /**
     * 📈 CALCULAR PERCENTILES
     */
    private static function percentile($array, $percentile) {
        sort($array);
        $index = ($percentile / 100) * (count($array) - 1);
        
        if (floor($index) == $index) {
            return $array[$index];
        } else {
            $lower = $array[floor($index)];
            $upper = $array[ceil($index)];
            return $lower + ($upper - $lower) * ($index - floor($index));
        }
    }
    
    /**
     * 🚨 GENERAR ALERTAS DE PERFORMANCE
     */
    public static function generatePerformanceAlerts() {
        $analysis = self::analyzeRecent24Hours();
        $alerts = [];
        
        // Alertas de queries lentas
        foreach ($analysis['query_performance'] ?? [] as $query_name => $stats) {
            if ($stats['avg_time_ms'] > 25) {
                $alerts[] = [
                    'type' => 'performance',
                    'severity' => $stats['avg_time_ms'] > 100 ? 'critical' : 'warning',
                    'message' => "Query '{$query_name}' average time is {$stats['avg_time_ms']}ms (target: <25ms)",
                    'suggestions' => [
                        'Add database indexes',
                        'Optimize query structure', 
                        'Implement query caching',
                        'Consider data partitioning'
                    ]
                ];
            }
            
            if ($stats['slow_queries'] > $stats['count'] * 0.1) { // >10% queries lentas
                $alerts[] = [
                    'type' => 'consistency',
                    'severity' => 'warning',
                    'message' => "Query '{$query_name}' has {$stats['slow_queries']} slow executions out of {$stats['count']} total",
                    'suggestions' => ['Monitor query execution plans', 'Check for blocking operations']
                ];
            }
        }
        
        return $alerts;
    }
}
?>