<?php
require 'config.php';
header('Content-Type: application/json');

function generarITV() {
    $letras = strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2));
    $numeros = rand(10, 99);
    return $letras . $numeros;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Correo inválido"]);
        exit;
    }

    if (strlen($pass) < 8) {
        echo json_encode(["success" => false, "message" => "La contraseña debe tener al menos 8 caracteres"]);
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    do {
        $itv = generarITV();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE itv_id = ?");
        $stmt->execute([$itv]);
        $existe = $stmt->fetch();
    } while ($existe);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (itv_id, name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$itv, $name, $email, $hash]);
        echo json_encode([
            "success" => true,
            "message" => "Registro exitoso 🎉",
            "itv_id"  => $itv
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(["success" => false, "message" => "El correo ya está registrado"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error en el servidor"]);
        }
    }
}
?>