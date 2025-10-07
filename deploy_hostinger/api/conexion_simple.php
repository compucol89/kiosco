<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$database = "kiosco_db";

// Crear conexión mysqli
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Conexión fallida: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8mb4");
?>
