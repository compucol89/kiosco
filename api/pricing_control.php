<?php
// File: api/pricing_control.php
// Control panel endpoint for dynamic pricing system
// Exists to enable/disable system and view active rules without editing files
// Related files: api/pricing_config.php, api/pricing_engine.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * PANEL DE CONTROL DE DYNAMIC PRICING
 * 
 * Endpoints:
 * - GET  /pricing_control.php?action=status  → Ver estado del sistema
 * - GET  /pricing_control.php?action=rules   → Ver reglas activas
 * - POST /pricing_control.php?action=toggle  → Activar/desactivar sistema
 * - POST /pricing_control.php?action=test    → Simular regla para un producto
 */

$action = $_GET['action'] ?? 'status';

try {
    // Cargar configuración
    $configFile = __DIR__ . '/pricing_config.php';
    
    if (!file_exists($configFile)) {
        throw new Exception('pricing_config.php no encontrado');
    }
    
    $config = require $configFile;
    
    switch ($action) {
        case 'status':
            // Ver estado del sistema
            $enabled = $config['enabled'] ?? false;
            $rulesCount = count($config['rules'] ?? []);
            $activeRules = array_filter($config['rules'] ?? [], function($rule) {
                return isset($rule['enabled']) && $rule['enabled'];
            });
            
            $response = [
                'success' => true,
                'system' => [
                    'enabled' => $enabled,
                    'timezone' => $config['timezone'] ?? 'UTC',
                    'total_rules' => $rulesCount,
                    'active_rules' => count($activeRules),
                ],
                'current_time' => date('Y-m-d H:i:s'),
                'current_day' => strtolower(date('D')),
            ];
            break;
            
        case 'rules':
            // Ver todas las reglas
            $rules = [];
            foreach ($config['rules'] ?? [] as $rule) {
                $rules[] = [
                    'id' => $rule['id'] ?? 'N/A',
                    'name' => $rule['name'] ?? 'Sin nombre',
                    'enabled' => $rule['enabled'] ?? false,
                    'type' => $rule['type'] ?? 'unknown',
                    'target' => $rule['category_slug'] ?? $rule['sku'] ?? 'N/A',
                    'days' => $rule['days'] ?? [],
                    'from' => $rule['from'] ?? '00:00',
                    'to' => $rule['to'] ?? '23:59',
                    'percent_inc' => $rule['percent_inc'] ?? 0,
                ];
            }
            
            $response = [
                'success' => true,
                'rules' => $rules,
            ];
            break;
            
        case 'test':
            // Simular regla para un producto
            require_once __DIR__ . '/pricing_engine.php';
            
            $testProduct = [
                'id' => 999,
                'codigo_barras' => 'TEST-001',
                'categoria_slug' => $_POST['category_slug'] ?? 'bebidas-alcoholicas',
                'precio' => floatval($_POST['precio'] ?? 1000),
                'nombre' => 'Producto de prueba',
            ];
            
            // Simular fecha/hora si se proporciona
            $simDateTime = null;
            if (isset($_POST['sim_datetime'])) {
                $simDateTime = new DateTime($_POST['sim_datetime'], new DateTimeZone($config['timezone']));
            }
            
            $result = PricingEngine::applyPricingRules($testProduct, $config, $simDateTime);
            
            $response = [
                'success' => true,
                'test_product' => $testProduct,
                'result' => $result,
                'pricing_applied' => $result['pricing']['ajuste_aplicado'] ?? false,
            ];
            break;
            
        case 'toggle':
            // Activar/desactivar sistema (requiere escribir archivo)
            $newState = !$config['enabled'];
            
            $response = [
                'success' => false,
                'message' => 'Para activar/desactivar, editar manualmente api/pricing_config.php línea 16',
                'current_state' => $config['enabled'],
                'file' => 'api/pricing_config.php',
            ];
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
}

