<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir la conexión a la base de datos
require_once 'db.php';

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener el ID del video
    $videoId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($videoId <= 0) {
        throw new Exception('ID de video inválido');
    }

    // Primero, obtener la información del video para eliminar el archivo físico
    $stmt = $pdo->prepare("SELECT nombre, ruta FROM videos WHERE id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        throw new Exception('Video no encontrado en la base de datos');
    }

    // Eliminar el archivo físico si existe
    if (!empty($video['ruta']) && file_exists($video['ruta'])) {
        if (!unlink($video['ruta'])) {
            // Log del error pero continúa con la eliminación de la BD
            error_log("No se pudo eliminar el archivo físico: " . $video['ruta']);
        }
    }

    // Eliminar el registro de la base de datos
    $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
    $result = $stmt->execute([$videoId]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Video "' . $video['nombre'] . '" eliminado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo eliminar el video de la base de datos');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>