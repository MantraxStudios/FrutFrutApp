<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Faltan datos"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, itv_id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['itv_id']     = $user['itv_id'];
            $_SESSION['user_role']  = $user['role'];

            echo json_encode([
                "success"  => true,
                "message"  => "Login exitoso",
                "name"     => $user['name'],
                "role"     => $user['role'],
                "redirect" => "panel.php"
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Correo o contraseña incorrectos"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error en el servidor"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>
