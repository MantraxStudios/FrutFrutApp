<?php
require 'db.php'; // incluimos la conexión PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nuevoNombre = $_POST['nombre'];

    $sql = "UPDATE videos SET nombre = :nombre WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([':nombre' => $nuevoNombre, ':id' => $id])) {
        echo "Nombre actualizado correctamente.";
    } else {
        echo "Error al actualizar.";
    }
}
?>

<form method="post">
    <label>ID del video:</label>
    <input type="number" name="id" required><br><br>

    <label>Nuevo nombre:</label>
    <input type="text" name="nombre" required><br><br>

    <button type="submit">Actualizar</button>
</form>
