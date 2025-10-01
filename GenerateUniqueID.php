<?php
require_once "config.php";

header('Content-Type: application/json; charset=utf-8');

$idtvs = trim($_POST['idtvs'] ?? $_GET['idtvs'] ?? '');

if (empty($idtvs)) {
    http_response_code(400);
    echo json_encode(["error" => "Debe proporcionar idtvs"]);
    exit;
}

error_log("DEBUG - IDTV recibido: '" . $idtvs . "'");

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE itv_id = ?");
    $stmt->execute([$idtvs]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "No existe un usuario con ese itv_id"]);
        exit;
    }

    function generarIdRandom() {
        $letras = '';
        for ($i = 0; $i < 4; $i++) {
            $letras .= chr(rand(65, 90));
        }
        $numeros = rand(1000, 9999);
        return $letras . '-' . $numeros;
    }

    do {
        $idGenerado = generarIdRandom();
        $stmt = $pdo->prepare("SELECT id FROM tv_ids WHERE id = ?");
        $stmt->execute([$idGenerado]);
        $existe = $stmt->fetch();
    } while ($existe);

    $stmt = $pdo->prepare("INSERT INTO tv_ids (id, idtvs) VALUES (?, ?)");
    $stmt->execute([$idGenerado, $idtvs]);

    echo json_encode([
        "id" => $idGenerado,
        "idtvs" => $idtvs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en la base de datos: " . $e->getMessage());
    echo json_encode([
        "error" => "Error en la base de datos",
        "detalle" => $e->getMessage()
    ]);
}
?>
