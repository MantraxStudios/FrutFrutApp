<?php
header('Content-Type: application/json; charset=utf-8');
require 'db.php'; // conexión $pdo con PDO

try {
    // Obtener el último registro ordenando por play_stamp descendente
    $stmt = $pdo->query("SELECT id, nombre, ruta, duracion, play_stamp FROM playback ORDER BY play_stamp DESC LIMIT 1");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($last) {
        echo json_encode([
            'success' => true,
            'data' => $last
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No hay registros en playback'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
