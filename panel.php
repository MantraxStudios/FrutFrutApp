<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: adminpanel.php");
    exit;
}

require 'config.php';

$user_id = $_SESSION['user_id'];
$itv_id  = $_SESSION['itv_id'];
$name    = $_SESSION['user_name'];
$email   = $_SESSION['user_email'];
$avatar  = $_SESSION['avatar'] ?? 'assets/icons/Default-Icon.png';

// Obtener TVs del usuario
$tv_ids = [];
$tv_count = 0;
try {
    $stmt = $pdo->prepare("SELECT * FROM tv_ids WHERE idtvs = ?");
    $stmt->execute([$itv_id]);
    $tv_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tv_count = count($tv_ids);
} catch (PDOException $e) { error_log($e->getMessage()); }

// Obtener videos del usuario
$videos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE uid = ? ORDER BY fecha_subida DESC");
    $stmt->execute([$user_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log($e->getMessage()); }

// Obtener el último video reproducido de cada canal
$playback_videos = [];
try {
    // Paso 1: Obtener los IDs de los canales del usuario
    $stmt = $pdo->prepare("SELECT id FROM tv_ids WHERE idtvs = ?");
    $stmt->execute([$itv_id]);
    $user_channels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($user_channels)) {
        // Crear placeholders para la consulta IN
        $placeholders = str_repeat('?,', count($user_channels) - 1) . '?';
        
        // Paso 2: Obtener el último video de cada canal
        $stmt = $pdo->prepare("
            SELECT p.*, tv.id as channel_id
            FROM playback p
            INNER JOIN tv_ids tv ON p.tvid = tv.id
            WHERE p.tvid IN ($placeholders)
            AND p.play_stamp = (
                SELECT MAX(p2.play_stamp)
                FROM playback p2
                WHERE p2.tvid = p.tvid
            )
            ORDER BY p.play_stamp DESC
        ");
        $stmt->execute($user_channels);
        $playback_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { 
    error_log("Error en playback query: " . $e->getMessage()); 
}

// Calcular espacio usado
$uploadDir = 'uploads/videos/' . $itv_id . '/';
$usedStorage = 0;
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            if (is_file($filePath)) {
                $usedStorage += filesize($filePath);
            }
        }
    }
}
$maxStorage = 100 * 1024 * 1024; // 100 MB
$usedPercent = ($usedStorage / $maxStorage) * 100;
?>

<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrutGlifoTV — Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        dark: {
                            50: '#18181b',
                            100: '#27272a',
                            200: '#3f3f46',
                            300: '#52525b',
                            400: '#71717a',
                            500: '#a1a1aa',
                            600: '#d4d4d8',
                            700: '#e4e4e7',
                            800: '#f4f4f5',
                            900: '#fafafa',
                        }
                    }
                }
            }
        }
    </script>
    <script src="panel.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen text-white">

<!-- Mobile Menu Button -->
<button id="mobileMenuBtn" class="fixed bottom-6 right-6 z-50 md:hidden w-14 h-14 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110">
    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-80 bg-gradient-to-b from-slate-800/95 to-slate-900/98 backdrop-blur-xl border-r border-slate-700/50 p-6 flex flex-col justify-between shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out md:relative md:translate-x-0 md:w-80">
        <!-- Profile Section -->
        <div class="space-y-6">
            <div class="text-center space-y-4">
                <div class="relative mx-auto w-32 h-32">
                    <img src="<?= htmlspecialchars($avatar) ?>" 
                         class="w-full h-full rounded-full object-cover border-4 border-gradient-to-r from-blue-500 to-purple-500 shadow-lg">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-500/20"></div>
                </div>
                <div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                        <?= htmlspecialchars($name) ?>
                    </h2>
                    <p class="text-slate-400 text-sm break-words mt-1"><?= htmlspecialchars($email) ?></p>
                    <div class="inline-flex items-center bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-blue-500/30 px-3 py-1 rounded-full mt-3">
                        <span class="text-xs font-medium text-blue-300">IDTV: <?= htmlspecialchars($itv_id) ?></span>
                    </div>
                </div>
                
                <button id="openUploadModal" 
                        class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                    <span class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <span>Subir Video</span>
                    </span>
                </button>        
                
                <button id="openImageModal" 
                        class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl mt-3">
                    <span class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Subir Imagen</span>
                    </span>
                </button>

                <button id="openManageScreensModal" 
                        class="w-full px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg mt-4">
                    <span class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Administrar Pantallas</span>
                    </span>
                </button>

            </div>

            <!-- Stats Section -->
            <div class="bg-gradient-to-r from-slate-800/50 to-slate-700/50 backdrop-blur rounded-2xl p-4 border border-slate-600/30">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-blue-400"><?= $tv_count ?></div>
                        <div class="text-xs text-slate-400 font-medium">Canales</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-purple-400"><?= count($videos) ?></div>
                        <div class="text-xs text-slate-400 font-medium">Videos</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-green-400"><?= count($playback_videos) ?></div>
                        <div class="text-xs text-slate-400 font-medium">En Reproducción</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage & Logout Section -->
        <div class="space-y-6">
            <!-- Storage Usage -->
            <div class="bg-gradient-to-r from-slate-800/50 to-slate-700/50 backdrop-blur rounded-2xl p-4 border border-slate-600/30">
                <h4 class="text-sm font-semibold text-slate-300 mb-3 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.79 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.79 4 8 4s8-1.79 8-4M4 7c0-2.21 3.79-4 8-4s8 1.79 8 4"/>
                    </svg>
                    <span>Espacio Usado</span>
                </h4>
                <div class="w-full bg-slate-700 rounded-full h-3 mb-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full transition-all duration-500 shadow-sm" 
                         style="width: <?= min($usedPercent,100) ?>%"></div>
                </div>
                <p class="text-xs text-slate-400">
                    <span class="font-semibold text-slate-300"><?= round($usedStorage / (1024*1024), 2) ?> MB</span> de 100 MB
                </p>
            </div>

            <!-- Logout Button -->
            <a href="logout.php" 
               class="flex items-center justify-center space-x-2 w-full px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile menu -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="flex-1 p-8 lg:p-8 md:p-6 sm:p-4 overflow-auto md:ml-0">
        <!-- Tab Navigation -->
        <div class="mb-8">
            <div class="flex space-x-4 border-b border-slate-700/50">
                <button id="tabVideos" class="tab-button px-6 py-3 font-semibold transition-all duration-300 border-b-2 border-transparent hover:border-blue-500 text-slate-400 hover:text-white active">
                    Mis Videos (<?= count($videos) ?>)
                </button>
                <button id="tabPlayback" class="tab-button px-6 py-3 font-semibold transition-all duration-300 border-b-2 border-transparent hover:border-green-500 text-slate-400 hover:text-white">
                    En Reproducción (<?= count($playback_videos) ?>)
                </button>
            </div>
        </div>

        <!-- Videos Tab Content -->
        <div id="videosContent" class="tab-content">
            <div class="mb-6">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mb-2">
                    Mis Videos
                </h1>
                <p class="text-slate-400">Gestiona y organiza tu contenido multimedia</p>
            </div>

            <!-- Videos Grid -->
            <?php if(count($videos) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                <?php foreach($videos as $video): ?>
                <div class="group bg-gradient-to-br from-slate-800/80 to-slate-900/80 backdrop-blur-xl rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-slate-700/50 overflow-hidden">
                    <!-- Video Preview -->
                    <div class="relative overflow-hidden rounded-t-2xl">
                        <video class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110" controls>
                            <source src="<?= htmlspecialchars($video['ruta']) ?>" type="video/mp4">
                            Tu navegador no soporta video.
                        </video>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    </div>
                    
                    <!-- Video Actions -->
                    <div class="p-4 space-y-3">
                        <div class="flex gap-2">
                            <!-- Assign to Channel Button -->
                            <button 
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 openTvModal"
                                data-video='<?= htmlspecialchars(json_encode([
                                    "id" => $video["id"],
                                    "nombre" => $video["ruta"],
                                    "ruta" => $video["ruta"],
                                    "duracion" => $video["duracion"] ?? 0,
                                    "fecha_subida" => $video["fecha_subida"] ?? date('Y-m-d H:i:s')
                                ]), ENT_QUOTES, "UTF-8") ?>'>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m4 0H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2z"/>
                                </svg>
                                <span class="text-sm">Canal</span>
                            </button>
                            
                            <!-- Delete Button -->
                            <a href="delete_video.php?id=<?= $video['id'] ?>"
                               class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105"
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este video?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span class="text-sm">Eliminar</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-16">
                <div class="mx-auto w-24 h-24 bg-gradient-to-br from-slate-700 to-slate-800 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-300 mb-2">No hay videos</h3>
                <p class="text-slate-400 mb-6">Aún no has subido ningún video. ¡Comienza subiendo tu primer contenido!</p>
                <button id="openUploadModalAlt" 
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                    Subir mi primer video
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Playback Tab Content -->
        <div id="playbackContent" class="tab-content hidden">
            <div class="mb-6">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-green-400 via-blue-400 to-purple-400 bg-clip-text text-transparent mb-2">
                    Videos en Reproducción
                </h1>
                <p class="text-slate-400">Últimos videos reproducidos por canal, agrupados por nombre</p>
            </div>

            <!-- Playback Videos Grid -->
            <?php if(count($playback_videos) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                <?php foreach($playback_videos as $playback): ?>
                <div class="group bg-gradient-to-br from-slate-800/80 to-slate-900/80 backdrop-blur-xl rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-slate-700/50 overflow-hidden">
                    <!-- Video Preview -->
                    <div class="relative overflow-hidden rounded-t-2xl">
                        <?php if(isset($playback['ruta']) && !empty($playback['ruta'])): ?>
                            <video class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110" controls>
                                <source src="<?= htmlspecialchars($playback['ruta']) ?>" type="video/mp4">
                                Tu navegador no soporta video.
                            </video>
                        <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center">
                                <svg class="w-16 h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-300 border border-green-500/30">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-1 animate-pulse"></span>
                                En reproducción
                            </span>
                        </div>
                    </div>
                    
                    <!-- Video Info -->
                    <div class="p-4 space-y-3">
                        <div class="space-y-2">
                            <!--
                            <h3 class="font-semibold text-white text-sm truncate"><?= htmlspecialchars($playback['nombre']) ?></h3>
                            -->
                            
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span class="flex items-center space-x-1">
                                    
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Canal: <?= htmlspecialchars($playback['tvid']) ?></span>
                                </span>
                            </div>
                            
                            <?php if(isset($playback['play_stamp'])): ?>
                            <div class="text-xs text-slate-400">
                                <span class="flex items-center space-x-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Reproducido: <?= date('d/m/Y H:i:s', strtotime($playback['play_stamp'])) ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(isset($playback['duracion'])): ?>
                            <div class="text-xs text-slate-400">
                                <span class="flex items-center space-x-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-5-8h2a3 3 0 013 3v8a3 3 0 01-3 3H8a3 3 0 01-3-3V9a3 3 0 013-3z"/>
                                    </svg>
                                    <span>Duración: <?= htmlspecialchars($playback['duracion']) ?>s</span>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-16">
                <div class="mx-auto w-24 h-24 bg-gradient-to-br from-slate-700 to-slate-800 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-300 mb-2">Sin reproducciones activas</h3>
                <p class="text-slate-400 mb-6">No hay videos en reproducción en este momento en tus canales</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4" style="display: none;">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl w-full max-w-md p-8 relative border border-slate-700/50 shadow-2xl">
        <button id="closeModal" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors" type="button">&times;</button>
        
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Subir Video</h2>
            <p class="text-slate-400 text-sm">Selecciona un archivo de video para subir</p>
        </div>
        
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-6">
            <div class="relative">
                <input type="file" name="video" accept="video/*" required 
                       class="w-full p-4 rounded-xl border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
            </div>
            
            <div class="space-y-2">
                <div class="w-full bg-slate-700 rounded-full h-3 overflow-hidden">
                    <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full w-0 transition-all duration-300"></div>
                </div>
                <p id="progressText" class="text-sm text-slate-400 text-center">0%</p>
            </div>
            
            <button type="submit" 
                    class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                Subir Video
            </button>
        </form>
    </div>
</div>

<!-- TV Modal -->
<div id="tvModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl w-full max-w-lg p-8 relative border border-slate-700/50 shadow-2xl">
        <button id="closeTvModal" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
        
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-blue-500 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Seleccionar Canal</h2>
            <p class="text-slate-400 text-sm">Elige el canal donde quieres asignar el video</p>
        </div>
        
        <div id="tvList" class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin scrollbar-track-slate-700 scrollbar-thumb-slate-500 hover:scrollbar-thumb-slate-400">
            <!-- TV channels will be loaded here -->
        </div>
    </div>
</div>

<!-- Modal para administrar pantallas -->
<div id="manageScreensModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl w-full max-w-2xl p-8 relative border border-slate-700/50 shadow-2xl">
        <button id="closeManageScreensModal" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
        
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-blue-500 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Administrar Pantallas</h2>
            <p class="text-slate-400 text-sm">Agrega nuevas pantallas y administra las existentes</p>
        </div>

        <!-- Lista de pantallas existentes -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-white mb-3">Pantallas Existentes</h3>
            <div id="screensList" class="space-y-3 max-h-64 overflow-y-auto scrollbar-thin scrollbar-track-slate-700 scrollbar-thumb-slate-500 hover:scrollbar-thumb-slate-400">
                <div class="text-center py-8 text-slate-400">Cargando pantallas...</div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="bg-blue-600/10 border border-blue-500/20 rounded-xl p-4">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-200">
                    <p class="font-medium mb-1">¿Cómo usar las pantallas?</p>
                    <p>Cada pantalla generará un ID único que podrás usar para conectar dispositivos y mostrar tus videos. Una vez creada, podrás asignar videos específicos a cada pantalla.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Upload Modal -->
<div id="imageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4" style="display: none;">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl w-full max-w-md p-8 relative border border-slate-700/50 shadow-2xl">
        <button id="closeImageModal" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors" type="button">&times;</button>
        
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Subir Imagen</h2>
            <p class="text-slate-400 text-sm">La imagen se convertirá en un video de 1 segundo</p>
        </div>
        
        <form id="imageUploadForm" action="upload_video.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="relative">
                <input type="file" name="video" accept="image/jpeg,image/png,image/jpg" required 
                       class="w-full p-4 rounded-xl border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                <p class="text-xs text-slate-400 mt-2">Formatos permitidos: JPG, PNG</p>
            </div>
            
            <div class="space-y-2">
                <div class="w-full bg-slate-700 rounded-full h-3 overflow-hidden">
                    <div id="imageProgressBar" class="bg-gradient-to-r from-green-500 to-teal-500 h-full rounded-full w-0 transition-all duration-300"></div>
                </div>
                <p id="imageProgressText" class="text-sm text-slate-400 text-center">0%</p>
            </div>
            
            <button type="submit" 
                    class="w-full px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                Convertir y Subir
            </button>
        </form>
    </div>
</div>
</body>
</html>