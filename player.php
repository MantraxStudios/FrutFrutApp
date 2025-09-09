<?php
require 'db.php'; // PDO connection

$stmt = $pdo->query("SELECT id, nombre, ruta, duracion FROM videos ORDER BY id ASC");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reproductor de Videos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

<div class="w-full max-w-3xl bg-white shadow-lg rounded-xl p-6">
    <h1 class="text-2xl font-bold text-center mb-4">Reproductor de Videos</h1>

    <video id="videoPlayer" class="w-full rounded-lg mb-2" controls autoplay muted></video>

    <p class="text-center text-gray-700 font-semibold" id="videoTitle"></p>
    <p class="text-center text-gray-500 text-sm" id="videoCountdown"></p>

    <div class="flex items-center justify-center mt-4 gap-3">
        <label class="text-gray-700 font-semibold">Volumen:</label>
        <input type="range" id="volumeControl" min="0" max="1" step="0.05" value="0.5" class="w-64">
        <button id="muteBtn" class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">Mute</button>
    </div>
</div>

<script>
const videos = <?php echo json_encode($videos); ?>;
let currentIndex = 0;
let countdownTimer;

const player = document.getElementById('videoPlayer');
const titleEl = document.getElementById('videoTitle');
const countdownEl = document.getElementById('videoCountdown');
const volumeControl = document.getElementById('volumeControl');
const muteBtn = document.getElementById('muteBtn');

player.volume = parseFloat(volumeControl.value);

// Función para reproducir un video
function playVideo(index){
    if(index >= videos.length){
        titleEl.textContent = "¡Fin de la lista!";
        countdownEl.textContent = "";
        player.src = "";
        return;
    }

    currentIndex = index;
    const video = videos[index];
    player.src = video.ruta;
    titleEl.textContent = video.nombre;

    let duration = parseInt(video.duracion);
    countdownEl.textContent = `Tiempo restante: ${duration} s`;

    if(countdownTimer) clearInterval(countdownTimer);

    countdownTimer = setInterval(()=>{
        duration--;
        countdownEl.textContent = `Tiempo restante: ${duration} s`;
        if(duration <= 0){
            clearInterval(countdownTimer);
            playVideo(currentIndex + 1);
        }
    }, 1000);

    // Forzar autoplay
    player.play().catch(()=>{
        // Si bloquea autoplay con audio
        player.muted = true;
        player.play().catch(()=>{});
    });
}

// Cambiar volumen con slider
volumeControl.addEventListener('input', ()=>{
    player.volume = parseFloat(volumeControl.value);
    if(player.volume > 0) player.muted = false;
});

// Botón mute/unmute
muteBtn.addEventListener('click', ()=>{
    player.muted = !player.muted;
    muteBtn.textContent = player.muted ? 'Unmute' : 'Mute';
});

// Iniciar primer video automáticamente
if(videos.length > 0){
    playVideo(currentIndex);
} else {
    titleEl.textContent = "No hay videos en la base de datos";
}
</script>

</body>
</html>
