<?php
require 'db.php'; 

$maxSize = 200 * 1024 * 1024;

if (!isset($_FILES['video'])) {
    die("No se envió ningún archivo.");
}

$nombre   = trim($_POST['nombre'] ?? '');
$duracion = intval($_POST['duracion'] ?? 0);
$archivo  = $_FILES['video'];

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    die("Error en la subida (código {$archivo['error']}).");
}

if ($archivo['size'] > $maxSize) {
    die("El archivo excede los 200MB permitidos.");
}

if ($duracion <= 0) {
    die("Duración inválida. Ingresa un número mayor a 0.");
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
$filename = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
$rutaServidor = $uploadDir . $filename;
$rutaPublica  = 'uploads/' . $filename;

if (!move_uploaded_file($archivo['tmp_name'], $rutaServidor)) {
    die("Error al mover el archivo.");
}

try {
    $sql = "INSERT INTO videos (nombre, ruta, duracion) VALUES (:nombre, :ruta, :duracion)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre'   => $nombre,
        ':ruta'     => $rutaPublica,
        ':duracion' => $duracion
    ]);

    echo "Video subido con éxito.<br>";
    echo "Nombre: " . htmlspecialchars($nombre) . "<br>";
    echo "Ruta: " . htmlspecialchars($rutaPublica) . "<br>";
    echo "Duración (segundos): " . htmlspecialchars($duracion) . "<br>";
} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}
_: