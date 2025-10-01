-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 01-10-2025 a las 22:19:15
-- Versión del servidor: 8.0.43-0ubuntu0.24.04.2
-- Versión de PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sdrv`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `playback`
--

CREATE TABLE `playback` (
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `duracion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `play_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tvid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Channel_1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `playback`
--

INSERT INTO `playback` (`nombre`, `id`, `ruta`, `duracion`, `play_stamp`, `tvid`) VALUES
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:44:51', 'QTMF-6972'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:44:51', 'QTMF-6972'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:44:51', 'QTMF-6972'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:55:19', 'QTMF-6972'),
('uploads/videos/AP54/video_68d759c657e577.81735603.mp4', '17', 'uploads/videos/AP54/video_68d759c657e577.81735603.mp4', '1', '2025-09-28 20:55:26', 'QTMF-6972'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:57:25', 'QTMF-6972'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:58:45', 'GTQP-8025'),
('uploads/videos/AP54/video_68d759c657e577.81735603.mp4', '17', 'uploads/videos/AP54/video_68d759c657e577.81735603.mp4', '1', '2025-09-28 20:59:37', 'QTMF-6972'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 21:09:34', 'GTQP-8025'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 21:15:12', 'UMBL-5354'),
('uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '18', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 21:15:14', 'UMBL-5354');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tv_ids`
--

CREATE TABLE `tv_ids` (
  `idtvs` varchar(10) COLLATE utf8mb3_unicode_ci NOT NULL,
  `id` varchar(10) COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Volcado de datos para la tabla `tv_ids`
--

INSERT INTO `tv_ids` (`idtvs`, `id`) VALUES
('DE20', 'EDMT-2458'),
('AE83', 'OELQ-3643'),
('OH50', 'MWCZ-1179'),
('OH50', 'AWOI-2001'),
('OH50', 'GIXE-7067'),
('AE83', 'BYWA-9419'),
('OH50', 'AWVM-3960'),
('OH50', 'YTQT-6939'),
('OH50', 'AHBS-1661'),
('NE55', 'SOWF-3549'),
('AP54', 'UMBL-5354');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `itv_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `itv_id`, `name`, `email`, `profile_pic`, `password`, `created_at`, `role`) VALUES
(1, 'DE20', 'Esteban', 'estebannova2020@gmail.com', NULL, '$2y$10$./5jZc3mdk9AaDC0e88wceVz73P8oLNYpdNP9RbS/zz0wSlN3tIlC', '2025-09-24 20:19:33', 'admin'),
(25, 'AP54', 'a@gmail.com', 'a@gmail.com', NULL, '$2y$10$HMZ2XbcoQKE9W3ED2iw7UezNb18ONRuf4lwcvLZ8tXuyudK450BMq', '2025-09-27 03:27:48', 'user'),
(26, 'FM18', 'a', 's@gmail.com', NULL, '$2y$10$kViaLyw.HM1cXXnlKHxEo..8fO0ypNHSIYHKzDgGXoBk7tCjiMLFW', '2025-09-30 15:19:51', 'user'),
(27, 'BX82', 'aaa', 'aa@gmail.com', NULL, '$2y$10$q7nzAmgZn20xS4cgbaQ40.HBwxLAUfzsa2QvSOVRYdA.TpmBA8KX2', '2025-09-30 16:28:52', 'user');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `duracion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Channel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Channel_1',
  `uid` varchar(10) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `videos`
--

INSERT INTO `videos` (`id`, `nombre`, `ruta`, `duracion`, `fecha_subida`, `Channel`, `uid`) VALUES
(17, '298643_tiny.mp4', 'uploads/videos/AP54/video_68d759c657e577.81735603.mp4', '1', '2025-09-27 03:28:06', 'AP54', '25'),
(18, '306169_medium.mp4', 'uploads/videos/AP54/video_68d99e43ae8276.36908002.mp4', '1', '2025-09-28 20:44:51', 'AP54', '25'),
(19, '293788_tiny.mp4', 'uploads/videos/BX82/video_68dc0803eb3681.40915540.mp4', '1', '2025-09-30 16:40:35', 'BX82', '27'),
(20, '1293291.png', 'uploads/videos/BX82/video_68dc08252249a8.94402327.mp4', '1', '2025-09-30 16:41:11', 'BX82', '27'),
(21, 'wallpaper_dead_by_daylight_07_1920x1080.jpg', 'uploads/videos/BX82/video_68dc088351de22.32352801.mp4', '1', '2025-09-30 16:42:43', 'BX82', '27');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `itv_id` (`itv_id`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
