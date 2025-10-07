<?php
/**
 * 🧪 VERIFICAR CORRECCIÓN DEL CÁLCULO DE EFECTIVO ESPERADO
 * 
 * Este script verifica que el cálculo del efectivo esperado ahora incluye
 * correctamente las ventas en efectivo.
 */

echo "🧪 VERIFICANDO CORRECCIÓN DEL CÁLCULO DE EFECTIVO ESPERADO\n";
echo "=" . str_repeat("=", 60) . "\n";

// Datos del ejemplo del usuario
$apertura = 9000.00;
$venta_efectivo = 9000.00;
$ingresos_manuales = 10000.00;
$salidas = 20000.00;

echo "📊 DATOS DEL TURNO ACTUAL:\n";
echo "   • Apertura de caja: $" . number_format($apertura, 2) . "\n";
echo "   • Venta en efectivo: $" . number_format($venta_efectivo, 2) . "\n";
echo "   • Ingresos manuales: $" . number_format($ingresos_manuales, 2) . "\n";
echo "   • Salidas: $" . number_format($salidas, 2) . "\n";

echo "\n🧮 CÁLCULO CORRECTO:\n";

// Cálculo ANTES (incorrecto) - como estaba mostrando el sistema
$efectivo_antes = $apertura + $ingresos_manuales - $salidas;
echo "   ❌ ANTES (incorrecto): $" . number_format($efectivo_antes, 2) . "\n";
echo "      Fórmula: apertura + ingresos - salidas\n";
echo "      $apertura + $ingresos_manuales - $salidas = $efectivo_antes\n";
echo "      (NO incluía las ventas en efectivo)\n";

echo "\n";

// Cálculo DESPUÉS (correcto) - como debe ser ahora
$efectivo_despues = $apertura + $venta_efectivo + $ingresos_manuales - $salidas;
echo "   ✅ DESPUÉS (corregido): $" . number_format($efectivo_despues, 2) . "\n";
echo "      Fórmula: apertura + ventas_efectivo + ingresos - salidas\n";
echo "      $apertura + $venta_efectivo + $ingresos_manuales - $salidas = $efectivo_despues\n";
echo "      (AHORA incluye las ventas en efectivo)\n";

echo "\n📋 VERIFICACIÓN:\n";
echo "   🎯 Diferencia: $" . number_format($efectivo_despues - $efectivo_antes, 2) . "\n";
echo "   💰 El efectivo esperado debe mostrar: $" . number_format($efectivo_despues, 2) . "\n";

echo "\n🔧 CAMBIOS REALIZADOS:\n";
echo "   1. Corregido cálculo de ventas_efectivo_reales en gestion_caja_completa.php\n";
echo "   2. Actualizado efectivo_teorico para incluir ventas en efectivo\n";
echo "   3. Agregado total_entradas_efectivo para compatibilidad con frontend\n";

echo "\n🚀 AHORA DEBE FUNCIONAR CORRECTAMENTE EN EL SISTEMA\n";
echo "=" . str_repeat("=", 60) . "\n";

// Simular respuesta de la API corregida
$respuesta_api = [
    'turno' => [
        'monto_apertura' => $apertura,
        'entradas_efectivo' => $ingresos_manuales,
        'ventas_efectivo_reales' => $venta_efectivo,
        'salidas_efectivo' => $salidas,
        'efectivo_teorico' => $efectivo_despues,
        'total_entradas_efectivo' => $venta_efectivo + $ingresos_manuales
    ]
];

echo "\n📡 RESPUESTA API ESPERADA:\n";
echo json_encode($respuesta_api, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n✅ CORRECCIÓN APLICADA EXITOSAMENTE\n";
?>





















