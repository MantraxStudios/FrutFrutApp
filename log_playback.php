<?php
header('Content-Type: application/json; charset=utf-8');
require 'db.php'; // conexión $pdo con PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'] ?? null;
    $nombre   = $_POST['nombre'] ?? null;
    $ruta     = $_POST['ruta'] ?? null;
    $duracion = $_POST['duracion'] ?? null;

    if ($id && $nombre && $ruta && $duracion) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO playback (id, nombre, ruta, duracion, play_stamp)
                VALUES (:id, :nombre, :ruta, :duracion, NOW())
            ");
            $stmt->execute([
                ':id'       => $id,
                ':nombre'   => $nombre,
                ':ruta'     => $ruta,
                ':duracion' => $duracion
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Reproducción registrada correctamente'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error'   => 'Faltan datos para registrar el playback'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Método no permitido'
    ]);
}
