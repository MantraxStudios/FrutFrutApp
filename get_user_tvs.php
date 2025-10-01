<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['itv_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require 'config.php';

$itv_id = $_SESSION['itv_id'];

try {
    // Obtener todos los canales del usuario
    $stmt = $pdo->prepare("SELECT id FROM tv_ids WHERE idtvs = ? ORDER BY id ASC");
    $stmt->execute([$itv_id]);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para debugging
    error_log("Canales encontrados para IDTV {$itv_id}: " . json_encode($channels));
    
    echo json_encode($channels);
    
} catch (PDOException $e) {
    error_log("Error en get_user_tvs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>