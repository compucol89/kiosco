<?php
header('Content-Type: text/plain');
require_once 'bd_conexion.php';

$pdo = Conexion::obtenerConexion();
$stmt = $pdo->query("SELECT id, metodo_pago, subtotal, descuento, monto_total FROM ventas WHERE metodo_pago = 'efectivo' ORDER BY id DESC LIMIT 1");
$venta = $stmt->fetch();

echo "Última venta en efectivo:\n";
echo json_encode($venta, JSON_PRETTY_PRINT);
?>






