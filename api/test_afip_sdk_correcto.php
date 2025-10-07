<?php
/**
 * 🎯 TEST AFIP SDK - ENDPOINT CORRECTO
 */

header('Content-Type: text/plain; charset=utf-8');

$access_token = 'ZaGIyLvwsMSiwPrwTFkA2oU2ktoDqjuQ9Gt6QN26quHvQrdcKJzc76L6ch9Wu4uW';
$base_url = 'https://app.afipsdk.com/api/v1/';
$cuit = '20944515411';

echo "🎯 TESTING AFIP SDK - ENDPOINTS CORRECTOS...\n\n";

// Probar endpoint de información primero
echo "📊 TEST 1: Información del token\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . 'status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'X-CUIT: ' . $cuit,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "- Status Code: {$http_code}\n";
echo "- Response: " . substr($response, 0, 200) . "\n\n";

// Probar diferentes variaciones del endpoint de facturas
$endpoints_facturas = [
    'fe/comprobantes',
    'wsfe/comprobantes', 
    'facturas',
    'comprobantes',
    'fe/cae',
    'wsfe/cae'
];

echo "📋 TEST 2: Endpoints de facturación\n";

foreach ($endpoints_facturas as $endpoint) {
    echo "🧪 Probando POST {$endpoint}: ";
    
    $test_data = [
        'punto_venta' => 3,
        'tipo_comprobante' => 6,
        'cliente' => [
            'nombre' => 'Consumidor Final',
            'tipo_documento' => 99,
            'numero_documento' => 0
        ],
        'items' => [
            [
                'descripcion' => 'Producto Test',
                'cantidad' => 1,
                'precio_unitario' => 100.00
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'X-CUIT: ' . $cuit,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 || $http_code === 201) {
        echo "✅ FUNCIONA ({$http_code})\n";
        echo "   Response: " . substr($response, 0, 100) . "\n";
    } elseif ($http_code === 404) {
        echo "❌ No encontrado\n";
    } elseif ($http_code === 405) {
        echo "⚠️ Método no permitido\n";
    } elseif ($http_code === 401 || $http_code === 403) {
        echo "🔑 Error de autorización\n";
    } else {
        echo "❓ HTTP {$http_code}\n";
        if (strlen($response) < 100) {
            echo "   Response: {$response}\n";
        }
    }
}

echo "\n📚 TEST 3: Documentación del SDK\n";
$doc_endpoints = ['docs', 'documentation', 'help', 'endpoints'];

foreach ($doc_endpoints as $endpoint) {
    echo "📖 GET {$endpoint}: ";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP {$http_code}";
    if ($http_code === 200 && strlen($response) > 50) {
        echo " ✅ Disponible";
    }
    echo "\n";
}

echo "\n🎯 PRÓXIMOS PASOS:\n";
echo "1. Si ningún endpoint funciona → Token puede estar vencido\n";
echo "2. Si encontramos endpoint correcto → Actualizar código\n";
echo "3. Si todo falla → Mantener sistema simulado (válido)\n";

?>
