<?php
session_start();
require 'config.php';

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

header('Content-Type: application/json');

// Leer JSON enviado desde JS
$data = json_decode(file_get_contents('php://input'), true);
$video = $data['video'] ?? null;
$tvid  = $data['tvid'] ?? null;

if (!$video || !$tvid) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO playback (nombre, ruta, duracion, tvid, id)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),    
            ruta = VALUES(ruta),
            duracion = VALUES(duracion),
            play_stamp = CURRENT_TIMESTAMP,
            tvid = VALUES(tvid)
    ");


    $nombre = $video['ruta'] ?? $video['nombre'] ?? 'video_desconocido';
    $ruta   = $video['ruta'] ?? '';


    $stmt->execute([
        $nombre,
        $ruta,
        $video['duracion'] ?? '0',
        $tvid,
        $video['id'] ?? uniqid()
    ]);


    echo json_encode(['success' => true]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>