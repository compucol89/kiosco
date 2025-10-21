<?php
// File: api/pricing_engine.php
// Pure functions to compute time-based dynamic pricing adjustments
// Exists to apply pricing rules without modifying database schema
// Related files: api/pricing_config.php, api/productos_pos_optimizado.php, api/procesar_venta_ultra_rapida.php

/**
 * PRICING ENGINE - TIME-BASED DYNAMIC PRICING
 * 
 * Motor de precios dinámicos que ajusta precios en tiempo real
 * basado en día de la semana y hora del día.
 * 
 * CARACTERÍSTICAS:
 * - Pure functions (sin efectos secundarios)
 * - Timezone-aware (Argentina/Buenos_Aires)
 * - Config-driven (usa pricing_config.php)
 * - Server-side validation (no se puede manipular desde cliente)
 * 
 * USO:
 * $config = require_once 'pricing_config.php';
 * $adjusted = PricingEngine::applyPricingRules($product, $config);
 */

class PricingEngine {
    
    /**
     * 🧪 SIMULADOR DE FECHA/HORA PARA TESTING (DEV ONLY)
     * 
     * Permite simular fecha/hora con parámetro ?__sim=2025-10-24T18:30:00
     * Solo funciona en desarrollo (APP_ENV !== 'production')
     * 
     * @param DateTimeZone $timezone
     * @return DateTime
     */
    private static function getCurrentDateTime($timezone) {
        // Solo permitir simulación en desarrollo
        $isDev = (getenv('APP_ENV') !== 'production' && getenv('APP_ENV') !== 'prod');
        
        // Si hay parámetro __sim en la URL y estamos en dev
        if ($isDev && isset($_GET['__sim'])) {
            $simDate = $_GET['__sim'];
            
            // Validar formato ISO: YYYY-MM-DDTHH:mm:ss
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $simDate)) {
                try {
                    $simDateTime = new DateTime($simDate, $timezone);
                    
                    // Log para debugging
                    error_log("🧪 PRICING SIMULATOR: Using simulated date/time: {$simDate}");
                    
                    return $simDateTime;
                } catch (Exception $e) {
                    error_log("⚠️ PRICING SIMULATOR: Invalid sim date format: {$simDate}");
                }
            }
        }
        
        // Fecha/hora real
        return new DateTime('now', $timezone);
    }
    
    /**
     * Aplica reglas de pricing a un producto
     * 
     * @param array $product - Producto con campos: id, codigo_barras, categoria_slug, precio
     * @param array $config - Configuración de pricing (de pricing_config.php)
     * @param DateTime|null $now - Fecha/hora actual (para testing, opcional)
     * @return array - Producto con campos adicionales: precio_ajustado, ajuste_aplicado, regla_id
     */
    public static function applyPricingRules($product, $config, $now = null) {
        // Si el sistema está desactivado, retornar sin cambios
        if (!isset($config['enabled']) || !$config['enabled']) {
            return self::addPricingMetadata($product, null, null, 'system_disabled');
        }
        
        // Establecer timezone
        $timezone = new DateTimeZone($config['timezone'] ?? 'America/Argentina/Buenos_Aires');
        
        // Usar fecha/hora actual o la proporcionada (para testing)
        if ($now === null) {
            $now = self::getCurrentDateTime($timezone);
        } else {
            $now->setTimezone($timezone);
        }
        
        // Buscar regla aplicable
        $applicableRule = self::findApplicableRule($product, $config['rules'] ?? [], $now);
        
        // Si no hay regla aplicable, retornar precio original
        if ($applicableRule === null) {
            return self::addPricingMetadata($product, null, null, 'no_rule_applicable');
        }
        
        // Calcular precio ajustado
        $originalPrice = (float)($product['precio'] ?? 0);
        $percentInc = (float)($applicableRule['percent_inc'] ?? 0);
        
        // Validar límites de seguridad
        $limits = $config['limits'] ?? [];
        if ($percentInc > 0 && isset($limits['max_increase_percent'])) {
            $percentInc = min($percentInc, $limits['max_increase_percent']);
        }
        if ($percentInc < 0 && isset($limits['max_decrease_percent'])) {
            $percentInc = max($percentInc, -$limits['max_decrease_percent']);
        }
        
        // Calcular nuevo precio
        $adjustedPrice = $originalPrice * (1 + ($percentInc / 100));
        
        // Aplicar redondeo si está configurado
        $roundConfig = $config['round'] ?? [];
        if (isset($roundConfig['enabled']) && $roundConfig['enabled']) {
            $decimals = $roundConfig['decimals'] ?? 2;
            $mode = $roundConfig['mode'] ?? PHP_ROUND_HALF_UP;
            $adjustedPrice = round($adjustedPrice, $decimals, $mode);
        }
        
        // Logging si está habilitado
        if (isset($config['logging']['enabled']) && $config['logging']['enabled']) {
            self::logPricingAdjustment($product, $applicableRule, $originalPrice, $adjustedPrice, $config);
        }
        
        // Retornar producto con metadata de pricing
        return self::addPricingMetadata($product, $adjustedPrice, $applicableRule, 'rule_applied');
    }
    
    /**
     * Busca la primera regla aplicable al producto y momento actual
     * 
     * @param array $product
     * @param array $rules
     * @param DateTime $now
     * @return array|null - Regla aplicable o null
     */
    private static function findApplicableRule($product, $rules, $now) {
        foreach ($rules as $rule) {
            // Si la regla está desactivada, skip
            if (isset($rule['enabled']) && !$rule['enabled']) {
                continue;
            }
            
            // Verificar si la regla aplica a este producto
            if (!self::ruleMatchesProduct($rule, $product)) {
                continue;
            }
            
            // Verificar si la regla aplica a este día/hora
            if (!self::ruleMatchesDateTime($rule, $now)) {
                continue;
            }
            
            // Regla encontrada
            return $rule;
        }
        
        return null;
    }
    
    /**
     * Verifica si una regla aplica a un producto
     * 
     * @param array $rule
     * @param array $product
     * @return bool
     */
    private static function ruleMatchesProduct($rule, $product) {
        $type = $rule['type'] ?? '';
        
        if ($type === 'category') {
            $ruleCategory = strtolower($rule['category_slug'] ?? '');
            $productCategory = strtolower($product['categoria_slug'] ?? '');
            return $ruleCategory === $productCategory;
        }
        
        if ($type === 'sku') {
            $ruleSku = strtoupper($rule['sku'] ?? '');
            $productSku = strtoupper($product['codigo_barras'] ?? '');
            return $ruleSku === $productSku;
        }
        
        return false;
    }
    
    /**
     * Verifica si una regla aplica a un día/hora específico
     * 
     * @param array $rule
     * @param DateTime $now
     * @return bool
     */
    private static function ruleMatchesDateTime($rule, $now) {
        // Verificar día de la semana
        $currentDay = strtolower($now->format('D')); // mon, tue, wed, etc.
        $ruleDays = array_map('strtolower', $rule['days'] ?? []);
        
        if (!in_array($currentDay, $ruleDays, true)) {
            return false;
        }
        
        // Verificar hora
        $currentTime = $now->format('H:i');
        $fromTime = $rule['from'] ?? '00:00';
        $toTime = $rule['to'] ?? '23:59';
        
        // Si toTime es null, usar fin del día
        if ($toTime === null) {
            $toTime = '23:59';
        }
        
        // Comparar strings de tiempo (formato 24h)
        return $currentTime >= $fromTime && $currentTime <= $toTime;
    }
    
    /**
     * Agrega metadata de pricing al producto
     * 
     * @param array $product
     * @param float|null $adjustedPrice
     * @param array|null $rule
     * @param string $status
     * @return array
     */
    private static function addPricingMetadata($product, $adjustedPrice, $rule, $status) {
        $product['pricing'] = [
            'status' => $status,
            'precio_original' => (float)($product['precio'] ?? 0),
            'precio_ajustado' => $adjustedPrice,
            'ajuste_aplicado' => $adjustedPrice !== null,
            'regla_id' => $rule['id'] ?? null,
            'regla_nombre' => $rule['name'] ?? null,
            'porcentaje' => $rule['percent_inc'] ?? null,
        ];
        
        // Si hay ajuste, actualizar el precio del producto
        if ($adjustedPrice !== null) {
            $product['precio'] = $adjustedPrice;
        }
        
        return $product;
    }
    
    /**
     * Registra un ajuste de precio en el log
     * 
     * @param array $product
     * @param array $rule
     * @param float $originalPrice
     * @param float $adjustedPrice
     * @param array $config
     */
    private static function logPricingAdjustment($product, $rule, $originalPrice, $adjustedPrice, $config) {
        $logFile = $config['logging']['log_file'] ?? __DIR__ . '/logs/pricing_adjustments.log';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $productId = $product['id'] ?? 'N/A';
        $productName = $product['nombre'] ?? 'Unknown';
        $ruleId = $rule['id'] ?? 'N/A';
        $percentInc = $rule['percent_inc'] ?? 0;
        
        $logLine = sprintf(
            "[%s] Product #%s '%s' | Rule: %s | Original: $%.2f → Adjusted: $%.2f (%+.1f%%)\n",
            $timestamp,
            $productId,
            $productName,
            $ruleId,
            $originalPrice,
            $adjustedPrice,
            $percentInc
        );
        
        @file_put_contents($logFile, $logLine, FILE_APPEND);
    }
    
    /**
     * Aplica reglas de pricing a un array de productos
     * 
     * @param array $products - Array de productos
     * @param array $config - Configuración de pricing
     * @param DateTime|null $now - Fecha/hora actual (opcional)
     * @return array - Array de productos con pricing aplicado
     */
    public static function applyPricingRulesToMany($products, $config, $now = null) {
        $results = [];
        
        foreach ($products as $product) {
            $results[] = self::applyPricingRules($product, $config, $now);
        }
        
        return $results;
    }
    
    /**
     * Valida que un precio enviado desde el cliente sea correcto
     * (usado al procesar venta para evitar manipulación)
     * 
     * @param int $productId
     * @param float $clientPrice - Precio que envió el cliente
     * @param float $dbPrice - Precio en base de datos
     * @param array $config - Configuración de pricing
     * @param float $tolerance - Tolerancia de diferencia (en pesos)
     * @return array - ['valid' => bool, 'expected_price' => float, 'difference' => float]
     */
    public static function validatePrice($productId, $clientPrice, $dbPrice, $config, $tolerance = 0.01) {
        // Simular producto para aplicar reglas
        $mockProduct = [
            'id' => $productId,
            'precio' => $dbPrice,
        ];
        
        // Aplicar reglas
        $adjusted = self::applyPricingRules($mockProduct, $config);
        $expectedPrice = (float)($adjusted['precio'] ?? $dbPrice);
        
        // Calcular diferencia
        $difference = abs($clientPrice - $expectedPrice);
        $isValid = $difference <= $tolerance;
        
        return [
            'valid' => $isValid,
            'expected_price' => $expectedPrice,
            'client_price' => $clientPrice,
            'db_price' => $dbPrice,
            'difference' => $difference,
            'rule_applied' => $adjusted['pricing']['ajuste_aplicado'] ?? false,
            'rule_id' => $adjusted['pricing']['regla_id'] ?? null,
        ];
    }
}

