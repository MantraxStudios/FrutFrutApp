<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

require 'config.php';

$user_id = $_SESSION['user_id'];
$itv_id  = $_SESSION['itv_id'];

// Configuración
$uploadDir = __DIR__ . "/uploads/videos/" . $itv_id . "/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Tipos permitidos
$allowedVideos = ['video/mp4', 'video/webm', 'video/ogg'];
$allowedImages = ['image/png', 'image/jpeg', 'image/jpg'];

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No se subió archivo"]);
    exit;
}

$tmpPath = $_FILES['file']['tmp_name'];
$fileType = mime_content_type($tmpPath);

try {
    // ==============================
    // Caso 1: VIDEO
    // ==============================
    if (in_array($fileType, $allowedVideos)) {
        $fileName = uniqid("vid_") . ".mp4";
        $destPath = $uploadDir . $fileName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            throw new Exception("Error al mover el archivo de video.");
        }

        // Guardar en DB
        $stmt = $pdo->prepare("INSERT INTO videos (uid, ruta, fecha_subida) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, "uploads/videos/$itv_id/$fileName"]);

        echo json_encode(["success" => true, "message" => "Video subido"]);
        exit;
    }

    // ==============================
    // Caso 2: IMAGEN → GIF de 1s
    // ==============================
    elseif (in_array($fileType, $allowedImages)) {
        if (!extension_loaded('imagick')) {
            throw new Exception("El servidor no tiene Imagick instalado.");
        }

        $image = new Imagick($tmpPath);
        $image->setImageFormat("gif");
        $image->setImageDelay(100); // 100 = 1s de duración
        $image->setImageIterations(1); // no loop infinito

        $fileName = uniqid("img_") . ".gif";
        $destPath = $uploadDir . $fileName;

        if (!$image->writeImages($destPath, true)) {
            throw new Exception("Error al generar el GIF.");
        }

        // Guardar en DB como si fuera video (para integrarlo en tu panel)
        $stmt = $pdo->prepare("INSERT INTO videos (uid, ruta, fecha_subida) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, "uploads/videos/$itv_id/$fileName"]);

        echo json_encode(["success" => true, "message" => "Imagen convertida en GIF"]);
        exit;
    }

    // ==============================
    // Caso 3: Tipo no soportado
    // ==============================
    else {
        throw new Exception("Tipo no permitido: $fileType");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
