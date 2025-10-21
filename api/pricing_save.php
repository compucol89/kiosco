<?php
// File: api/pricing_save.php
// Saves dynamic pricing configuration from frontend
// Exists to allow editing pricing rules without touching files manually
// Related files: api/pricing_config.php, api/pricing_engine.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action'])) {
        throw new Exception('Acción no especificada');
    }
    
    $configFile = __DIR__ . '/pricing_config.php';
    
    if (!file_exists($configFile)) {
        throw new Exception('Archivo de configuración no encontrado');
    }
    
    // Leer configuración actual
    $currentConfig = require $configFile;
    
    switch ($input['action']) {
        case 'toggle':
            // Activar/desactivar sistema
            $newState = isset($input['enabled']) ? (bool)$input['enabled'] : !$currentConfig['enabled'];
            $currentConfig['enabled'] = $newState;
            
            // Guardar
            saveConfig($configFile, $currentConfig);
            
            echo json_encode([
                'success' => true,
                'message' => $newState ? 'Sistema activado' : 'Sistema desactivado',
                'enabled' => $newState,
            ]);
            break;
            
        case 'update_rule':
            // Actualizar una regla específica
            $ruleId = $input['rule_id'] ?? null;
            $updates = $input['updates'] ?? [];
            
            if (!$ruleId) {
                throw new Exception('ID de regla no especificado');
            }
            
            // Buscar y actualizar regla
            $found = false;
            foreach ($currentConfig['rules'] as $index => &$rule) {
                if (($rule['id'] ?? '') === $ruleId) {
                    // Actualizar campos permitidos
                    if (isset($updates['enabled'])) {
                        $rule['enabled'] = (bool)$updates['enabled'];
                    }
                    if (isset($updates['days'])) {
                        $rule['days'] = $updates['days'];
                    }
                    if (isset($updates['from'])) {
                        $rule['from'] = $updates['from'];
                    }
                    if (isset($updates['to'])) {
                        $rule['to'] = $updates['to'];
                    }
                    if (isset($updates['percent_inc'])) {
                        $rule['percent_inc'] = floatval($updates['percent_inc']);
                    }
                    
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('Regla no encontrada');
            }
            
            // Guardar
            saveConfig($configFile, $currentConfig);
            
            echo json_encode([
                'success' => true,
                'message' => 'Regla actualizada correctamente',
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}

/**
 * Guarda la configuración en el archivo PHP
 */
function saveConfig($file, $config) {
    $php = "<?php\n";
    $php .= "// File: api/pricing_config.php\n";
    $php .= "// Centralized dynamic pricing configuration for time-based price adjustments\n";
    $php .= "// Exists to define when and how much to increase prices for specific categories/products\n";
    $php .= "// Related files: api/pricing_engine.php, api/productos_pos_optimizado.php, api/procesar_venta_ultra_rapida.php\n\n";
    $php .= "return " . var_export($config, true) . ";\n";
    
    $written = file_put_contents($file, $php);
    
    if ($written === false) {
        throw new Exception('No se pudo guardar la configuración');
    }
}

