<?php
header('Content-Type: application/json; charset=utf-8');
require 'config.php';

$channel = trim($_GET['channel'] ?? '');

try {
    if ($channel) {
        $stmt = $pdo->prepare("
            SELECT id, nombre, ruta, duracion, play_stamp, tvid
            FROM playback
            WHERE tvid = :tvid
            ORDER BY play_stamp DESC
            LIMIT 1
        ");
        $stmt->execute([':tvid' => $channel]);
    } else {
        $stmt = $pdo->query("
            SELECT id, nombre, ruta, duracion, play_stamp, tvid
            FROM playback
            ORDER BY play_stamp DESC
            LIMIT 1
        ");
    }

    $last = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($last) {
        echo json_encode([
            'success' => true,
            'data' => $last
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No hay registros en playback para este canal'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
