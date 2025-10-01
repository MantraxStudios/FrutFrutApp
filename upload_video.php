<?php
session_start();
require 'config.php';

// Mostrar errores PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Archivo de log
$logFile = __DIR__ . '/upload_debug.log';
function logMessage($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

logMessage("=== NUEVA SUBIDA ===");

if (!isset($_SESSION['user_id'])) {
    logMessage("Usuario no autenticado.");
    header("Location: auth.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['video'])) {
        logMessage("No se recibió ningún archivo.");
        die("Error en la subida del archivo.");
    }

    if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        logMessage("Error en el archivo: " . $_FILES['video']['error']);
        die("Error en la subida del archivo.");
    }

    $fileTmpPath   = $_FILES['video']['tmp_name'];
    $fileName      = $_FILES['video']['name'];
    $fileSize      = $_FILES['video']['size'];
    $fileType      = mime_content_type($fileTmpPath);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    logMessage("Archivo recibido: " . print_r($_FILES['video'], true));
    logMessage("Tipo MIME: $fileType, Extensión: $fileExtension, Tamaño: $fileSize");

    $maxSize = 100 * 1024 * 1024; // 100 MB
    $allowedVideoTypes = ['video/mp4'];
    $allowedImageTypes = ['image/jpeg', 'image/png'];

    // Carpeta de subida
    $itv_id = $_SESSION['itv_id'];
    $uploadDirAbsolute = $_SERVER['DOCUMENT_ROOT'] . '/uploads/videos/' . $itv_id . '/';
    $relativeDir = 'uploads/videos/' . $itv_id . '/';

    if (!is_dir($uploadDirAbsolute)) {
        mkdir($uploadDirAbsolute, 0755, true);
        logMessage("Carpeta creada: $uploadDirAbsolute");
    }

    // Calcular espacio usado
    $usedStorage = 0;
    foreach (scandir($uploadDirAbsolute) as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDirAbsolute . $file;
            if (is_file($filePath)) {
                $usedStorage += filesize($filePath);
            }
        }
    }
    logMessage("Espacio usado en carpeta: $usedStorage bytes");

    if ($usedStorage + $fileSize > $maxSize) {
        logMessage("No hay suficiente espacio. Límite 100MB alcanzado.");
        die("Error: No hay suficiente espacio. Límite de 100MB alcanzado.");
    }

    $newFileName = uniqid('video_', true) . '.mp4';
    $destPathAbsolute = $uploadDirAbsolute . $newFileName;
    $destPathRelative = $relativeDir . $newFileName;

    // === Caso 1: Video MP4 ===
    if (in_array($fileType, $allowedVideoTypes)) {
        if (move_uploaded_file($fileTmpPath, $destPathAbsolute)) {
            logMessage("Archivo movido correctamente: $destPathAbsolute");

            $stmt = $pdo->prepare("INSERT INTO videos (nombre, ruta, duracion, fecha_subida, Channel, uid) VALUES (?, ?, ?, NOW(), ?, ?)");
            $result = $stmt->execute([$fileName, $destPathRelative, 1, $_SESSION['itv_id'], $_SESSION['user_id']]);

            if ($result) {
                logMessage("Registro insertado correctamente en BD.");
                header("Location: panel.php?upload=success");
                exit;
            } else {
                logMessage("ERROR: No se pudo insertar en BD. PDO error info: " . print_r($stmt->errorInfo(), true));
                die("Error al insertar en la base de datos. Revisa el log.");
            }
        } else {
            logMessage("ERROR: No se pudo mover el archivo de video.");
            die("Error al mover el archivo de video. Revisa el log.");
        }

    // === Caso 2: Imagen a video ===
    } elseif (in_array($fileType, $allowedImageTypes)) {
        $tmpImage = $uploadDirAbsolute . uniqid('img_', true) . '.' . $fileExtension;

        if (move_uploaded_file($fileTmpPath, $tmpImage)) {
            logMessage("Imagen movida correctamente: $tmpImage");

            $command = "ffmpeg -y -loop 1 -i " . escapeshellarg($tmpImage) . " -c:v libx264 -t 1 -pix_fmt yuv420p -vf \"scale=iw:ih\" " . escapeshellarg($destPathAbsolute) . " 2>&1";
            exec($command, $output, $return_var);

            logMessage("FFmpeg output: " . implode("\n", $output));
            logMessage("FFmpeg return code: $return_var");

            unlink($tmpImage);

            if ($return_var === 0 && file_exists($destPathAbsolute)) {
                $stmt = $pdo->prepare("INSERT INTO videos (nombre, ruta, duracion, fecha_subida, Channel, uid) VALUES (?, ?, ?, NOW(), ?, ?)");
                $result = $stmt->execute([$fileName, $destPathRelative, 1, $_SESSION['itv_id'], $_SESSION['user_id']]);

                if ($result) {
                    logMessage("Registro insertado correctamente en BD después de conversión.");
                    header("Location: panel.php?upload=success");
                    exit;
                } else {
                    logMessage("ERROR: No se pudo insertar en BD. PDO error info: " . print_r($stmt->errorInfo(), true));
                    die("Error al insertar en la base de datos. Revisa el log.");
                }
            } else {
                logMessage("ERROR: FFmpeg falló o video no creado.");
                die("Error al convertir la imagen a video. Revisa el log.");
            }

        } else {
            logMessage("ERROR: No se pudo mover la imagen.");
            die("Error al mover la imagen. Revisa el log.");
        }

    } else {
        logMessage("ERROR: Formato de archivo no permitido: $fileType");
        die("Error: Formato de archivo no permitido.");
    }

} else {
    logMessage("No se recibió POST.");
    die("Error en la subida del archivo.");
}
?>
