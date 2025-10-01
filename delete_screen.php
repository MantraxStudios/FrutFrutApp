<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$screen_id = $input['screen_id'] ?? null;
$user_itv_id = $_SESSION['itv_id'];

if (!$screen_id) {
    echo json_encode(['success' => false, 'message' => 'ID de pantalla requerido']);
    exit;
}

try {
    // Verificar que la pantalla pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM tv_ids WHERE id = ? AND idtvs = ?");
    $stmt->execute([$screen_id, $user_itv_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Pantalla no encontrada']);
        exit;
    }
    
    // Eliminar la pantalla
    $stmt = $pdo->prepare("DELETE FROM tv_ids WHERE id = ? AND idtvs = ?");
    $stmt->execute([$screen_id, $user_itv_id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>