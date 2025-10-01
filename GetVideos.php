<?php
header('Content-Type: application/json; charset=utf-8');
require 'config.php'; 

try {
    $channel = isset($_GET['channel']) ? trim($_GET['channel']) : null;

    if ($channel !== null && $channel !== '') {
        $stmt = $pdo->prepare("SELECT id, nombre, ruta, duracion, Channel 
                               FROM videos 
                               WHERE Channel = :channel 
                               ORDER BY id ASC");
        $stmt->execute(['channel' => $channel]);

        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($videos as $v) {
            $result[] = [
                'id'       => (int)$v['id'],
                'nombre'   => $v['nombre'],
                'ruta'     => $v['ruta'],
                'duracion' => (int)$v['duracion'],
                'channel'  => $v['Channel'],
            ];
        }
    } else {
        $result = [];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
