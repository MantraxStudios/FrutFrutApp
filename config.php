<?php
$entorno = 0; // Cambia a 1 para producción

if ($entorno === 1) {
    $host = "localhost";
    $db   = "srcardbo_vds";
    $user = "srcardbo_vdsuser";
    $pass = "46.oL7?Me12W";
} else {
    $host = "localhost";
    $db   = "sdrv";
    $user = "root";
    $pass = "";
}

$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
