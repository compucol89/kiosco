<?php
/**
 * 🔍 TEST DE ENDPOINTS AFIP SDK
 * Probar diferentes endpoints para encontrar el correcto
 */

header('Content-Type: text/plain; charset=utf-8');

$access_token = 'ZaGIyLvwsMSiwPrwTFkA2oU2ktoDqjuQ9Gt6QN26quHvQrdcKJzc76L6ch9Wu4uW';
$base_url = 'https://app.afipsdk.com/api/v1/';
$cuit = '20944515411';

echo "🔍 TESTING ENDPOINTS AFIP SDK...\n\n";

// Endpoints a probar
$endpoints_to_test = [
    'status' => 'GET',
    'health' => 'GET', 
    'ping' => 'GET',
    'invoices' => 'GET',
    'invoices' => 'POST',
    'afip/invoices' => 'GET',
    'afip/invoices' => 'POST',
    'fe/invoices' => 'POST',
    'v1/invoices' => 'POST'
];

foreach ($endpoints_to_test as $endpoint => $method) {
    echo "🧪 Testing: {$method} {$base_url}{$endpoint}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'X-CUIT: ' . $cuit,
        'Content-Type: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"test": "data"}');
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "   - HTTP: {$http_code}";
    if ($curl_error) {
        echo " - Error: {$curl_error}";
    }
    
    if ($http_code === 200 || $http_code === 201) {
        echo " ✅ FUNCIONA";
        if (strlen($response) < 200) {
            echo " - Response: " . substr($response, 0, 100);
        }
    } elseif ($http_code === 401) {
        echo " 🔑 REQUIERE AUTH";
    } elseif ($http_code === 404) {
        echo " ❌ NO ENCONTRADO";
    } elseif ($http_code === 405) {
        echo " ⚠️ MÉTODO NO PERMITIDO";
    } else {
        echo " ❓ OTRO";
    }
    
    echo "\n";
}

echo "\n🎯 CONCLUSIONES:\n";
echo "- Buscar endpoints que respondan 200/201\n";
echo "- Verificar documentación oficial del SDK\n";
echo "- Considerar que el token pueda estar vencido\n";

?>
