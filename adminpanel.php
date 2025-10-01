<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

// Procesar actualizaciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_user':
                $id = intval($_POST['id']);
                $campo = $_POST['campo'];
                $valor = $_POST['valor'];
                
                $campos_permitidos = ['name', 'email', 'itv_id', 'role'];
                if (in_array($campo, $campos_permitidos)) {
                    if ($campo === 'role' && !in_array($valor, ['admin', 'user'])) {
                        echo json_encode(['success' => false, 'message' => 'Rol inválido']);
                        exit;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET $campo = ? WHERE id = ?");
                    if ($stmt->execute([$valor, $id])) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Campo no permitido']);
                }
                exit;
                
            case 'change_password':
                $id = intval($_POST['id']);
                $password = $_POST['password'];
                
                if (strlen($password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Contraseña muy corta']);
                    exit;
                }
                
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $id])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al cambiar contraseña']);
                }
                exit;
                
            case 'delete_user':
                $id = intval($_POST['id']);
                
                // Eliminar videos del usuario primero
                $stmt = $pdo->prepare("DELETE FROM videos WHERE uid = ?");
                $stmt->execute([$id]);
                
                // Eliminar usuario
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
                }
                exit;
                
            case 'delete_video':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar video']);
                }
                exit;
                
            case 'delete_user_videos':
                $userId = intval($_POST['user_id']);
                
                // Contar videos antes de eliminar
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE uid = ?");
                $countStmt->execute([$userId]);
                $videoCount = $countStmt->fetchColumn();
                
                // Eliminar videos del usuario
                $stmt = $pdo->prepare("DELETE FROM videos WHERE uid = ?");
                if ($stmt->execute([$userId])) {
                    echo json_encode(['success' => true, 'deleted_count' => $videoCount]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar videos del usuario']);
                }
                exit;
        }
    }
}

// Obtener todos los usuarios con su rol
$usuarios = $pdo->query("SELECT id, name, email, profile_pic, itv_id, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los videos con información del usuario
$videos = $pdo->query("SELECT v.id, v.nombre, v.ruta, v.duracion, v.fecha_subida, v.Channel, u.name AS usuario, u.id AS user_id
                       FROM videos v
                       JOIN users u ON v.uid = u.id
                       ORDER BY v.fecha_subida DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — FrutGlifoTV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    <style>
        .editable {
            cursor: pointer;
            border: 2px solid transparent;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            background: rgba(30, 41, 59, 0.5);
        }
        .editable:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }
        .editing {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .notification {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            opacity: 0;
            transform: translateX(100%) scale(0.9);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .notification.show {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        .notification.success { 
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .notification.error { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .tab-active {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        .screen {
            display: none;
        }
        .screen.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen text-white">

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-2xl border border-slate-700/50 p-8 max-w-md mx-4 transform scale-95 transition-all duration-300" id="modalContent">
            <div class="text-center space-y-6">
                <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center mx-auto">
                    <i class="fas fa-exclamation-triangle text-2xl text-white"></i>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold text-white mb-2" id="modalTitle">Confirmar eliminación</h3>
                    <p class="text-slate-400" id="modalMessage">¿Estás seguro de que quieres continuar?</p>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="closeModal()" 
                            class="flex-1 px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-xl text-white font-medium transition-all duration-300">
                        Cancelar
                    </button>
                    <button id="confirmBtn" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl text-white font-medium transition-all duration-300">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-80 bg-gradient-to-b from-slate-800/95 to-slate-900/98 backdrop-blur-xl border-r border-slate-700/50 p-6 flex flex-col justify-between shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out md:relative md:translate-x-0 md:w-80">
            <div class="space-y-8">
                <!-- Header -->
                <div class="text-center space-y-4">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-500 rounded-2xl flex items-center justify-center mx-auto">
                        <i class="fas fa-crown text-2xl text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                            Admin Panel
                        </h2>
                        <p class="text-slate-400 text-sm">Gestión del sistema</p>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <nav class="space-y-3">
                    <button onclick="showScreen('usuarios')" class="nav-tab tab-active group flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 w-full text-left">
                        <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center group-hover:bg-blue-500/30 transition-colors">
                            <i class="fas fa-users text-blue-400"></i>
                        </div>
                        <span class="font-medium">Usuarios</span>
                        <span id="user-count" class="ml-auto bg-blue-500/20 px-2 py-1 rounded-full text-xs"><?= count($usuarios) ?></span>
                    </button>
                    <button onclick="showScreen('videos')" class="nav-tab group flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-slate-700/50 transition-all duration-300 w-full text-left">
                        <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center group-hover:bg-purple-500/30 transition-colors">
                            <i class="fas fa-video text-purple-400"></i>
                        </div>
                        <span class="font-medium">Videos</span>
                        <span id="video-count" class="ml-auto bg-purple-500/20 px-2 py-1 rounded-full text-xs"><?= count($videos) ?></span>
                    </button>
                </nav>

                <!-- Quick Guide -->
                <div class="bg-gradient-to-r from-slate-800/50 to-slate-700/50 backdrop-blur rounded-2xl p-4 border border-slate-600/30">
                    <h4 class="font-semibold text-slate-300 mb-3 flex items-center space-x-2">
                        <i class="fas fa-lightbulb text-yellow-400"></i>
                        <span>Guía rápida</span>
                    </h4>
                    <ul class="text-xs space-y-2 text-slate-400">
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-mouse-pointer text-blue-400 mt-0.5 flex-shrink-0"></i>
                            <span>Haz clic en cualquier campo para editar</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-keyboard text-green-400 mt-0.5 flex-shrink-0"></i>
                            <span>Presiona Enter para guardar</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-times text-red-400 mt-0.5 flex-shrink-0"></i>
                            <span>Esc para cancelar</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Logout -->
            <a href="logout.php" 
               class="flex items-center justify-center space-x-2 w-full px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar sesión</span>
            </a>
        </aside>

        <!-- Mobile Menu Button -->
        <button id="mobileMenuBtn" class="fixed bottom-6 right-6 z-50 md:hidden w-14 h-14 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110">
            <i class="fas fa-bars text-white"></i>
        </button>

        <!-- Overlay for mobile menu -->
        <div id="mobileOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden md:hidden"></div>

        <!-- Main Content -->
        <main class="flex-1 p-8 lg:p-8 md:p-6 sm:p-4 overflow-auto md:ml-0">

            <!-- Usuarios Screen -->
            <div id="usuarios-screen" class="screen active">
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-8 space-y-4 lg:space-y-0">
                    <div>
                        <h3 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent mb-2">
                            <i class="fas fa-users mr-3"></i>Gestión de Usuarios
                        </h3>
                        <p class="text-slate-400">Administra todos los usuarios del sistema</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="expandAllUsers()" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg text-white text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-expand-arrows-alt mr-2"></i>Expandir todos
                        </button>
                        <button onclick="collapseAllUsers()" 
                                class="px-4 py-2 bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 rounded-lg text-white text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-compress-arrows-alt mr-2"></i>Contraer todos
                        </button>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <?php foreach($usuarios as $u): ?>
                    <div class="bg-gradient-to-br from-slate-800/80 to-slate-900/80 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden user-card card-hover" 
                         data-user-id="<?= $u['id'] ?>">
                        
                        <!-- User Header -->
                        <div class="p-6 flex items-center justify-between cursor-pointer hover:bg-slate-700/30 transition-all duration-300" 
                             onclick="toggleUserDetails(<?= $u['id'] ?>)">
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <img src="<?= htmlspecialchars($u['profile_pic'] ?? 'assets/icons/Default-Icon.png') ?>" 
                                         class="w-16 h-16 rounded-full object-cover border-4 border-gradient-to-r from-blue-500 to-purple-500 shadow-lg">
                                    <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-500/20"></div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-xl text-white"><?= htmlspecialchars($u['name']) ?></h4>
                                    <p class="text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                                    <div class="flex gap-2 mt-2">
                                        <span class="inline-flex items-center bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-blue-500/30 px-3 py-1 rounded-full text-xs font-medium text-blue-300">
                                            <i class="fas fa-tv mr-1"></i>IDTV: <?= htmlspecialchars($u['itv_id']) ?>
                                        </span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= ($u['role'] ?? 'user') === 'admin' ? 'bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-500/30 text-purple-300' : 'bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 text-green-300' ?>">
                                            <i class="fas <?= ($u['role'] ?? 'user') === 'admin' ? 'fa-crown' : 'fa-user' ?> mr-1"></i>
                                            <?= ucfirst($u['role'] ?? 'user') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-2xl text-slate-400 transform transition-transform duration-300 expand-icon">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>

                        <!-- User Details -->
                        <div class="user-details hidden p-6 pt-0 space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                
                                <!-- Basic Info -->
                                <div class="space-y-4">
                                    <h5 class="font-bold text-sm text-slate-300 uppercase tracking-wide flex items-center space-x-2">
                                        <i class="fas fa-info-circle text-blue-400"></i>
                                        <span>Información básica</span>
                                    </h5>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-2">Nombre</label>
                                            <div class="editable" 
                                                 data-field="name" 
                                                 data-user-id="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['name']) ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-2">Email</label>
                                            <div class="editable" 
                                                 data-field="email" 
                                                 data-user-id="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['email']) ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-2">ID TV</label>
                                            <div class="editable" 
                                                 data-field="itv_id" 
                                                 data-user-id="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['itv_id']) ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-400 mb-2">Rol</label>
                                            <select class="role-select w-full px-4 py-3 bg-slate-800/50 border border-slate-600/50 rounded-xl text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-300" 
                                                    data-user-id="<?= $u['id'] ?>"
                                                    data-original="<?= $u['role'] ?? 'user' ?>">
                                                <option value="user" <?= ($u['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>Usuario</option>
                                                <option value="admin" <?= ($u['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="space-y-4">
                                    <h5 class="font-bold text-sm text-slate-300 uppercase tracking-wide flex items-center space-x-2">
                                        <i class="fas fa-cogs text-purple-400"></i>
                                        <span>Acciones</span>
                                    </h5>
                                    
                                    <div class="space-y-3">
                                        <button onclick="changePassword(<?= $u['id'] ?>)" 
                                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-700 hover:to-orange-700 rounded-xl text-white font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                                            <i class="fas fa-key"></i>
                                            <span>Cambiar contraseña</span>
                                        </button>
                                        
                                        <button onclick="deleteUserVideos(<?= $u['id'] ?>)" 
                                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 rounded-xl text-white font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Eliminar todos los videos</span>
                                        </button>
                                        
                                        <button onclick="deleteUser(<?= $u['id'] ?>)" 
                                                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl text-white font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                                            <i class="fas fa-user-times"></i>
                                            <span>Eliminar usuario</span>
                                        </button>
                                    </div>
                                    
                                    <div class="bg-gradient-to-r from-slate-800/50 to-slate-700/50 backdrop-blur rounded-xl p-4 border border-slate-600/30 text-sm space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Creado:</span>
                                            <span class="text-slate-300 font-medium"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Videos:</span>
                                            <span id="video-count-<?= $u['id'] ?>" class="text-slate-300 font-medium">
                                                <?= count(array_filter($videos, fn($v) => $v['user_id'] == $u['id'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Videos Screen -->
            <div id="videos-screen" class="screen">
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-8 space-y-4 lg:space-y-0">
                    <div>
                        <h3 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent mb-2">
                            <i class="fas fa-video mr-3"></i>Gestión de Videos
                        </h3>
                        <p class="text-slate-400">Administra todo el contenido multimedia del sistema</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <select id="filter-user" 
                                class="px-4 py-2 bg-slate-800/50 border border-slate-600/50 rounded-xl text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all duration-300">
                            <option value="">Todos los usuarios</option>
                            <?php 
                            $usuarios_con_videos = array_unique(array_column($videos, 'usuario'));
                            foreach($usuarios_con_videos as $usuario): ?>
                                <option value="<?= htmlspecialchars($usuario) ?>"><?= htmlspecialchars($usuario) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if(count($videos) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6" id="videos-grid">
                    <?php foreach($videos as $v): ?>
                    <div class="video-card group bg-gradient-to-br from-slate-800/80 to-slate-900/80 backdrop-blur-xl rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-slate-700/50 overflow-hidden card-hover" 
                         data-user="<?= htmlspecialchars($v['usuario']) ?>"
                         data-video-id="<?= $v['id'] ?>">
                        
                        <!-- Video Preview -->
                        <div class="relative overflow-hidden">
                            <video class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110" preload="metadata">
                                <source src="<?= htmlspecialchars($v['ruta']) ?>" type="video/mp4">
                                Tu navegador no soporta video.
                            </video>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            <div class="absolute top-3 right-3">
                                <button onclick="deleteVideo(<?= $v['id'] ?>)" 
                                        class="w-10 h-10 bg-red-600/90 hover:bg-red-700 backdrop-blur rounded-full text-white flex items-center justify-center opacity-80 hover:opacity-100 transition-all duration-300 transform hover:scale-110">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                            <div class="absolute bottom-3 left-3">
                                <div class="bg-black/60 backdrop-blur rounded-lg px-2 py-1 text-xs text-white">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= $v['duracion'] ? gmdate("H:i:s", $v['duracion']) : '00:00' ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Video Info -->
                        <div class="p-4 space-y-3">
                            <div>
                                <h4 class="font-bold text-white truncate flex items-center space-x-2">
                                    <i class="fas fa-user text-blue-400 flex-shrink-0"></i>
                                    <span><?= htmlspecialchars($v['usuario']) ?></span>
                                </h4>
                                <p class="text-xs text-slate-400 flex items-center space-x-1">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?= date('d/m/Y H:i', strtotime($v['fecha_subida'])) ?></span>
                                </p>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="window.open('<?= htmlspecialchars($v['ruta']) ?>', '_blank')" 
                                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg text-white text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                                    <i class="fas fa-eye"></i>
                                    <span>Ver</span>
                                </button>
                                <button onclick="downloadVideo('<?= htmlspecialchars($v['ruta']) ?>')" 
                                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 rounded-lg text-white text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                                    <i class="fas fa-download"></i>
                                    <span>Descargar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gradient-to-br from-slate-700 to-slate-800 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-video text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-300 mb-2">No hay videos</h3>
                    <p class="text-slate-400">No se han subido videos al sistema todavía</p>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        // Modal System
        let modalAction = null;
        let modalData = null;

        function showModal(title, message, confirmAction, data = null) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            modalAction = confirmAction;
            modalData = data;
            
            const modal = document.getElementById('confirmModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.style.transform = 'scale(1)';
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('confirmModal');
            const content = document.getElementById('modalContent');
            
            content.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.classList.add('hidden');
                modalAction = null;
                modalData = null;
            }, 300);
        }

        // Confirm button click handler
        document.getElementById('confirmBtn').addEventListener('click', () => {
            if (modalAction && modalData) {
                modalAction(modalData);
            }
            closeModal();
        });

        // Close modal when clicking outside
        document.getElementById('confirmModal').addEventListener('click', (e) => {
            if (e.target.id === 'confirmModal') {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !document.getElementById('confirmModal').classList.contains('hidden')) {
                closeModal();
            }
        });

        // Screen Management
        function showScreen(screenName) {
            // Hide all screens
            document.querySelectorAll('.screen').forEach(screen => {
                screen.classList.remove('active');
            });
            
            // Show selected screen
            document.getElementById(screenName + '-screen').classList.add('active');
            
            // Update tab styles
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('tab-active');
                tab.classList.add('hover:bg-slate-700/50');
            });
            
            // Highlight active tab
            event.target.classList.add('tab-active');
            event.target.classList.remove('hover:bg-slate-700/50');
            
            currentScreen = screenName;
            
            // Close mobile menu if open
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
            }
        }

        // Mobile Menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');

        // Initialize mobile menu state on page load
        function initializeMobileMenu() {
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
                mobileOverlay.classList.add('hidden');
            }
        }

        // Call on page load
        initializeMobileMenu();

        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.classList.toggle('hidden');
        });

        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });

        // Close mobile menu on window resize
        window.addEventListener('resize', () => {
            initializeMobileMenu();
        });

        // Notification System
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // User Management Functions
        function toggleUserDetails(userId) {
            const card = document.querySelector(`[data-user-id="${userId}"]`);
            const details = card.querySelector('.user-details');
            const icon = card.querySelector('.expand-icon i');
            
            details.classList.toggle('hidden');
            
            if (details.classList.contains('hidden')) {
                icon.style.transform = 'rotate(0deg)';
            } else {
                icon.style.transform = 'rotate(90deg)';
            }
        }

        function expandAllUsers() {
            document.querySelectorAll('.user-details').forEach(details => {
                details.classList.remove('hidden');
            });
            document.querySelectorAll('.expand-icon i').forEach(icon => {
                icon.style.transform = 'rotate(90deg)';
            });
        }

        function collapseAllUsers() {
            document.querySelectorAll('.user-details').forEach(details => {
                details.classList.add('hidden');
            });
            document.querySelectorAll('.expand-icon i').forEach(icon => {
                icon.style.transform = 'rotate(0deg)';
            });
        }

        // Editable Fields
        document.addEventListener('DOMContentLoaded', function() {
            // Make fields editable
            document.querySelectorAll('.editable').forEach(element => {
                element.addEventListener('click', function() {
                    if (this.querySelector('input')) return; // Already editing
                    
                    const currentValue = this.textContent.trim();
                    const field = this.dataset.field;
                    const userId = this.dataset.userId;
                    
                    // Create input
                    const input = document.createElement('input');
                    input.type = field === 'email' ? 'email' : 'text';
                    input.value = currentValue;
                    input.className = 'w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:border-blue-500 focus:outline-none';
                    
                    // Replace content
                    this.innerHTML = '';
                    this.appendChild(input);
                    this.classList.add('editing');
                    input.focus();
                    input.select();
                    
                    // Save on Enter
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            saveField(element, field, userId, this.value, currentValue);
                        } else if (e.key === 'Escape') {
                            cancelEdit(element, currentValue);
                        }
                    });
                    
                    // Save on blur
                    input.addEventListener('blur', function() {
                        if (this.value !== currentValue) {
                            saveField(element, field, userId, this.value, currentValue);
                        } else {
                            cancelEdit(element, currentValue);
                        }
                    });
                });
            });
            
            // Role select changes
            document.querySelectorAll('.role-select').forEach(select => {
                select.addEventListener('change', function() {
                    const userId = this.dataset.userId;
                    const originalValue = this.dataset.original;
                    const newValue = this.value;
                    
                    if (newValue !== originalValue) {
                        updateUser(userId, 'role', newValue)
                            .then(success => {
                                if (success) {
                                    this.dataset.original = newValue;
                                    showNotification('Rol actualizado correctamente', 'success');
                                    // Update role badge
                                    updateRoleBadge(userId, newValue);
                                } else {
                                    this.value = originalValue; // Revert
                                    showNotification('Error al actualizar el rol', 'error');
                                }
                            });
                    }
                });
            });
        });

        function saveField(element, field, userId, newValue, oldValue) {
            if (newValue.trim() === '') {
                showNotification('El campo no puede estar vacío', 'error');
                cancelEdit(element, oldValue);
                return;
            }
            
            updateUser(userId, field, newValue.trim())
                .then(success => {
                    if (success) {
                        element.textContent = newValue.trim();
                        element.classList.remove('editing');
                        showNotification('Campo actualizado correctamente', 'success');
                        
                        // Update header if name or email changed
                        if (field === 'name' || field === 'email') {
                            updateUserHeader(userId, field, newValue.trim());
                        }
                    } else {
                        cancelEdit(element, oldValue);
                        showNotification('Error al actualizar el campo', 'error');
                    }
                });
        }

        function cancelEdit(element, originalValue) {
            element.textContent = originalValue;
            element.classList.remove('editing');
        }

        function updateUserHeader(userId, field, value) {
            const card = document.querySelector(`[data-user-id="${userId}"]`);
            if (field === 'name') {
                const nameElement = card.querySelector('h4');
                nameElement.textContent = value;
            } else if (field === 'email') {
                const emailElement = card.querySelector('p.text-slate-400');
                emailElement.textContent = value;
            }
        }

        function updateRoleBadge(userId, role) {
            const card = document.querySelector(`[data-user-id="${userId}"]`);
            const badge = card.querySelector('span:last-child');
            
            if (role === 'admin') {
                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-500/30 text-purple-300';
                badge.innerHTML = '<i class="fas fa-crown mr-1"></i>Admin';
            } else {
                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 text-green-300';
                badge.innerHTML = '<i class="fas fa-user mr-1"></i>User';
            }
        }

        // API Functions
        async function updateUser(userId, field, value) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_user',
                        id: userId,
                        campo: field,
                        valor: value
                    })
                });
                
                const data = await response.json();
                return data.success;
            } catch (error) {
                console.error('Error:', error);
                return false;
            }
        }

        async function changePassword(userId) {
            const password = prompt('Ingresa la nueva contraseña (mínimo 6 caracteres):');
            if (!password) return;
            
            if (password.length < 6) {
                showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'change_password',
                        id: userId,
                        password: password
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('Contraseña cambiada correctamente', 'success');
                } else {
                    showNotification(data.message || 'Error al cambiar la contraseña', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        async function deleteUser(userId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción eliminará también todos sus videos.')) {
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete_user',
                        id: userId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('Usuario eliminado correctamente', 'success');
                    // Remove user card
                    document.querySelector(`[data-user-id="${userId}"]`).remove();
                    // Update counters
                    updateCounters();
                } else {
                    showNotification(data.message || 'Error al eliminar el usuario', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        async function deleteVideo(videoId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este video?')) {
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete_video',
                        id: videoId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('Video eliminado correctamente', 'success');
                    // Remove video card
                    document.querySelector(`[data-video-id="${videoId}"]`).remove();
                    // Update counters
                    updateCounters();
                } else {
                    showNotification(data.message || 'Error al eliminar el video', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        function downloadVideo(videoUrl) {
            const link = document.createElement('a');
            link.href = videoUrl;
            link.download = videoUrl.split('/').pop();
            link.click();
        }

        function removeUserVideosFromGrid(userId) {
            // Encontrar y eliminar todas las tarjetas de video de este usuario
            const videoCards = document.querySelectorAll('.video-card');
            const userName = getUsernameById(userId);
            let removedCount = 0;
            
            videoCards.forEach(card => {
                // Buscar el usuario en la tarjeta
                const userSpan = card.querySelector('h4 span');
                if (userSpan && userSpan.textContent.trim() === userName) {
                    card.remove();
                    removedCount++;
                }
            });
            
            return removedCount;
        }

        // Función auxiliar para obtener el nombre de usuario por ID
        function getUsernameById(userId) {
            const userCard = document.querySelector(`[data-user-id="${userId}"]`);
            if (userCard) {
                const nameElement = userCard.querySelector('h4');
                return nameElement ? nameElement.textContent.trim() : '';
            }
            return '';
        }

        function updateCounters() {
            const userCount = document.querySelectorAll('.user-card').length;
            const videoCount = document.querySelectorAll('.video-card').length;
            
            document.getElementById('user-count').textContent = userCount;
            document.getElementById('video-count').textContent = videoCount;
        }

        // Video Filter
        document.getElementById('filter-user').addEventListener('change', function() {
            const selectedUser = this.value;
            const videoCards = document.querySelectorAll('.video-card');
            
            videoCards.forEach(card => {
                if (selectedUser === '' || card.dataset.user === selectedUser) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>

</body>
</html>