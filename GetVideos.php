<?php
header('Content-Type: application/json; charset=utf-8');

require 'db.php'; // Tu archivo PDO con conexión $pdo

try {
    // Traer todos los videos ordenados por ID
    $stmt = $pdo->query("SELECT id, nombre, ruta, duracion FROM videos ORDER BY id ASC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolver solo los campos que necesitamos
    $result = [];
    foreach ($videos as $v) {
        $result[] = [
            'nombre'   => $v['nombre'],
            'ruta'     => $v['ruta'],
            'duracion' => (int)$v['duracion'],
            'id' => (int)$v['id'],
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>