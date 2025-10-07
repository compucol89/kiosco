<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy&_t=" . time();
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "=== ESTRUCTURA DEL ENDPOINT ===\n\n";

if ($data && $data['success']) {
    echo "📊 resumen_general:\n";
    if (isset($data['resumen_general'])) {
        foreach ($data['resumen_general'] as $key => $value) {
            echo "   - $key: $value\n";
        }
    }
} else {
    echo "❌ Error o sin datos\n";
}
?>






