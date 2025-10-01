<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID de video no especificado.");
}

$video_id = intval($_GET['id']);
$user_id  = $_SESSION['user_id'];

// Obtener ruta del video y verificar que el usuario es propietario
$stmt = $pdo->prepare("SELECT ruta FROM videos WHERE id = ? AND uid = ?");
$stmt->execute([$video_id, $user_id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    die("Video no encontrado o no tienes permiso para eliminarlo.");
}

// Eliminar archivo del servidor
if (file_exists($video['ruta'])) {
    unlink($video['ruta']);
}

// Eliminar registro de la base de datos
$stmt = $pdo->prepare("DELETE FROM videos WHERE id = ? AND uid = ?");
$stmt->execute([$video_id, $user_id]);

// Redirigir al panel con mensaje
header("Location: panel.php?delete=success");
exit;
?>
