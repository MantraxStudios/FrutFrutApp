package com.mantraxstudios.frutviewerapp

import android.net.Uri
import android.os.Bundle
import android.util.Log
import android.view.WindowManager
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.background
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material3.HorizontalDivider
import androidx.core.view.WindowCompat
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.PlayerView
import com.mantraxstudios.frutviewerapp.ui.theme.FrutViewerAppTheme
import kotlinx.coroutines.*
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.io.FileOutputStream
import java.io.FileReader
import java.io.FileWriter
import java.net.HttpURLConnection
import java.net.URL

data class ChannelConfig(
    val channelNumber: Int,
    val serverUrl: String,
    val baseUrl: String,
    val getLastVideo: String
) {
    companion object {
        fun create(channelNumber: Int): ChannelConfig {
            return ChannelConfig(
                channelNumber = channelNumber,
                serverUrl = "http://vds.srcardboard.cl/GetVideos.php?channel=Channel_$channelNumber",
                baseUrl = "http://vds.srcardboard.cl/",
                getLastVideo = "http://vds.srcardboard.cl/get_last_playback.php?channel=Channel_$channelNumber"
            )
        }
    }
}

data class VideoInfo(
    val nombre: String,
    val ruta: String,
    var duracion: Int
)

data class DownloadProgress(
    val isDownloading: Boolean = false,
    val currentVideo: String = "",
    val progress: Float = 0f,
    val totalVideos: Int = 0,
    val completedVideos: Int = 0
)

data class LastPlayedVideo(
    val channelNumber: Int,
    val videoName: String,
    val timestamp: Long
)

class MainActivity : ComponentActivity() {

    private var player: ExoPlayer? = null
    private val scope = CoroutineScope(Dispatchers.Main + Job())
    private val videoList = mutableStateListOf<VideoInfo>()
    private val configFile by lazy { File(getExternalFilesDir(null), "channel_config.json") }
    private val localListFile by lazy { File(getExternalFilesDir(null), "videos.json") }
    private val lastPlayedFile by lazy { File(getExternalFilesDir(null), "last_played.json") }
    private val videoDir by lazy { File(getExternalFilesDir(null), "videos").apply { mkdirs() } }

    private var currentIndex = 0
    private var playerListener: Player.Listener? = null
    private var lastPlayStamp: String? = null
    private var progressText = mutableStateOf("Inicializando...")
    private var playbackCheckerJob: Job? = null
    private var periodicSyncJob: Job? = null
    private var downloadProgress = mutableStateOf(DownloadProgress())

    private var selectedChannel: ChannelConfig? = null

    private var currentlyPlayingVideo: String? = null
    private var isPlayingSpecificVideo = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        Log.i("MAIN", "onCreate iniciado")

        WindowCompat.setDecorFitsSystemWindows(window, false)
        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)

        setContent {
            FrutViewerAppTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    var isReady by remember { mutableStateOf(false) }
                    var menuExpanded by remember { mutableStateOf(false) }
                    val currentProgressText by progressText
                    val currentDownloadProgress by downloadProgress

                    Box(modifier = Modifier.fillMaxSize()) {
                        if (player != null && isReady) {
                            AndroidView(
                                factory = { ctx ->
                                    PlayerView(ctx).apply {
                                        useController = false
                                        keepScreenOn = true
                                        player = this@MainActivity.player
                                    }
                                },
                                update = { view ->
                                    view.player = this@MainActivity.player
                                },
                                modifier = Modifier.fillMaxSize()
                            )
                        } else {
                            // Show loading/status screen when not ready
                            Column(
                                modifier = Modifier.align(Alignment.Center),
                                horizontalAlignment = Alignment.CenterHorizontally
                            ) {
                                CircularProgressIndicator()
                                Spacer(modifier = Modifier.height(16.dp))
                                Text(text = currentProgressText)

                                if (currentDownloadProgress.isDownloading) {
                                    Spacer(modifier = Modifier.height(16.dp))
                                    Text(
                                        text = "Descargando: ${currentDownloadProgress.currentVideo}",
                                        fontSize = 12.sp
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                    LinearProgressIndicator(
                                        progress = currentDownloadProgress.progress,
                                        modifier = Modifier
                                            .fillMaxWidth(0.8f)
                                            .height(8.dp)
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Text(
                                        text = "${currentDownloadProgress.completedVideos}/${currentDownloadProgress.totalVideos} videos",
                                        fontSize = 10.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                        }

                        /*
                        Box(
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .padding(12.dp)
                        ) {
                            IconButton(onClick = { menuExpanded = true }) {
                                Icon(
                                    imageVector = Icons.Default.Menu,
                                    contentDescription = "Menú de videos"
                                )
                            }

                            DropdownMenu(
                                expanded = menuExpanded,
                                onDismissRequest = { menuExpanded = false },
                                modifier = Modifier.widthIn(min = 200.dp, max = 300.dp)
                            ) {
                                Text(
                                    text = "Lista de Videos (${videoList.size})",
                                    modifier = Modifier.padding(16.dp, 8.dp),
                                    style = MaterialTheme.typography.titleSmall,
                                    fontWeight = FontWeight.Bold
                                )

                                HorizontalDivider()

                                if (videoList.isEmpty()) {
                                    DropdownMenuItem(
                                        text = {
                                            Text(
                                                text = "No hay videos en este canal",
                                                style = MaterialTheme.typography.bodyMedium,
                                                color = MaterialTheme.colorScheme.onSurfaceVariant
                                            )
                                        },
                                        onClick = { /* Do nothing */ }
                                    )
                                } else {
                                    videoList.forEachIndexed { index, video ->
                                        DropdownMenuItem(
                                            text = {
                                                Column {
                                                    Text(
                                                        text = video.nombre,
                                                        maxLines = 2,
                                                        style = MaterialTheme.typography.bodyMedium
                                                    )
                                                    Text(
                                                        text = "${video.duracion}s",
                                                        style = MaterialTheme.typography.bodySmall,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                }
                                            },
                                            onClick = {
                                                menuExpanded = false
                                                playSpecificVideo(index)
                                            },
                                            leadingIcon = {
                                                if (currentlyPlayingVideo == video.nombre) {
                                                    Icon(
                                                        imageVector = Icons.Default.PlayArrow,
                                                        contentDescription = "Reproduciendo",
                                                        tint = MaterialTheme.colorScheme.primary
                                                    )
                                                }
                                            }
                                        )
                                    }
                                }
                            }
                        }
                        */

                        // Texto del canal en la esquina inferior derecha
                        selectedChannel?.let { channel ->
                            Box(
                                modifier = Modifier
                                    .align(Alignment.BottomEnd)
                                    .padding(16.dp)
                            ) {
                                Text(
                                    text = "Channel: ${channel.channelNumber}",
                                    fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier
                                        .background(
                                            MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.0f),
                                            shape = MaterialTheme.shapes.small
                                        )
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
                                )
                            }
                        }
                    }

                    LaunchedEffect(Unit) {
                        initializeAppWithUniqueId { isReady = it }
                    }
                }
            }
        }
    }

    private suspend fun generateOrLoadUniqueId(): Int? {
        // Primero intentar cargar un ID guardado
        val savedChannelNumber = loadChannelConfig()
        if (savedChannelNumber != null) {
            Log.i("UNIQUE_ID", "ID guardado encontrado: $savedChannelNumber")
            return savedChannelNumber
        }

        // Si no hay ID guardado, generar uno nuevo
        return try {
            Log.i("UNIQUE_ID", "Generando nuevo ID único...")
            val uniqueId = fetchUniqueIdFromServer()
            if (uniqueId != null) {
                saveChannelConfig(uniqueId)
                Log.i("UNIQUE_ID", "Nuevo ID generado y guardado: $uniqueId")
                uniqueId
            } else {
                Log.e("UNIQUE_ID", "No se pudo generar ID único")
                null
            }
        } catch (e: Exception) {
            Log.e("UNIQUE_ID", "Error generando ID único: ${e.message}", e)
            null
        }
    }

    private suspend fun fetchUniqueIdFromServer(): Int? = withContext(Dispatchers.IO) {
        try {
            val url = URL("https://vds.srcardboard.cl/GenerateUniqueID.php")
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 10000
            conn.readTimeout = 10000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("UNIQUE_ID", "Error HTTP: ${conn.responseCode}")
                return@withContext null
            }

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            Log.d("UNIQUE_ID", "Respuesta del servidor: $response")

            val json = JSONObject(response)
            val id = json.getInt("id")

            Log.i("UNIQUE_ID", "ID único obtenido del servidor: $id")
            return@withContext id
        } catch (e: Exception) {
            Log.e("UNIQUE_ID", "Error obteniendo ID único: ${e.message}", e)
            null
        }
    }

    private suspend fun initializeAppWithUniqueId(onReady: (Boolean) -> Unit) {
        try {
            Log.i("INIT", "=== INICIANDO INICIALIZACIÓN CON ID ÚNICO ===")

            progressText.value = "Obteniendo ID de canal..."

            val uniqueId = generateOrLoadUniqueId()
            if (uniqueId == null) {
                progressText.value = "Error obteniendo ID de canal"
                onReady(false)
                return
            }

            val channelConfig = ChannelConfig.create(uniqueId)
            changeChannel(channelConfig)

            progressText.value = "Inicializando..."

            if (player == null) {
                Log.i("INIT", "Creando nuevo reproductor...")
                setupPlayer()
            } else {
                Log.i("INIT", "Reutilizando reproductor existente...")
                player?.apply {
                    playWhenReady = true
                    repeatMode = Player.REPEAT_MODE_ONE
                }
            }

            if (player == null) {
                Log.e("INIT", "Error: No hay reproductor disponible")
                progressText.value = "Error configurando reproductor"
                onReady(false)
                return
            }

            progressText.value = "Sincronizando con servidor..."
            val serverVideos = withTimeoutOrNull(10000) { fetchVideos() }

            if (serverVideos != null && serverVideos.isNotEmpty()) {
                Log.i("SYNC", "Sincronizado: ${serverVideos.size} videos")
                syncLocalWithServer(serverVideos)
            } else {
                Log.w("SYNC", "Sin conexión, cargando locales...")
                loadVideosFromFile()

                if (videoList.isEmpty()) {
                    progressText.value = "Reintentando conexión..."
                    val retryVideos = withTimeoutOrNull(15000) { fetchVideos() }
                    if (retryVideos != null && retryVideos.isNotEmpty()) {
                        syncLocalWithServer(retryVideos)
                    }
                }
            }

            if (videoList.isNotEmpty()) {
                progressText.value = "Preparando videos..."
                downloadMissingVideos()

                Log.i("INIT", "Marcando UI como listo")
                onReady(true)

                delay(500)

                startPlaybackChecker()
                startPeriodicSync()

                Log.i("INIT", "Iniciando reproducción...")
                val lastPlayed = loadLastPlayedVideo()
                if (lastPlayed != null && lastPlayed.channelNumber == selectedChannel?.channelNumber) {
                    val videoIndex = videoList.indexOfFirst { it.nombre == lastPlayed.videoName }
                    if (videoIndex != -1) {
                        Log.i("INIT", "Reproduciendo último video: ${lastPlayed.videoName}")
                        playSpecificVideo(videoIndex)
                    } else {
                        Log.i("INIT", "Reproduciendo primer video")
                        playSpecificVideo(0)
                    }
                } else {
                    Log.i("INIT", "Reproduciendo primer video")
                    playSpecificVideo(0)
                }

            } else {
                progressText.value = "No hay videos disponibles"
                Log.w("INIT", "Sin videos disponibles")
                onReady(false)
            }

            Log.i("INIT", "=== INICIALIZACIÓN COMPLETA ===")

        } catch (e: Exception) {
            Log.e("INIT", "Error en inicialización: ${e.message}", e)
            progressText.value = "Error inicializando"
            onReady(false)
        }
    }

    private fun changeChannel(channelConfig: ChannelConfig) {
        Log.i("CHANNEL", "=== INICIANDO CAMBIO DE CANAL ===")

        stopBackgroundTasks()
        cleanupPlayer()
        setupPlayer()

        selectedChannel = channelConfig
        lastPlayStamp = null
        currentIndex = 0
        isPlayingSpecificVideo = false
        currentlyPlayingVideo = null

        videoList.clear()

        downloadProgress.value = DownloadProgress()
        progressText.value = "Configurando Canal ${channelConfig.channelNumber}..."

        Log.i("CHANNEL", "=== CAMBIO DE CANAL PREPARADO ===")
    }

    private fun stopBackgroundTasks() {
        try {
            playbackCheckerJob?.cancel()
            playbackCheckerJob = null

            periodicSyncJob?.cancel()
            periodicSyncJob = null

            Log.i("TASKS", "Tareas en segundo plano detenidas")
        } catch (e: Exception) {
            Log.e("TASKS", "Error deteniendo tareas: ${e.message}", e)
        }
    }

    private fun cleanupPlayer() {
        try {
            Log.i("PLAYER", "Limpiando reproductor...")

            player?.let { player ->
                player.pause()

                playerListener?.let { listener ->
                    player.removeListener(listener)
                }

                player.stop()
                player.clearMediaItems()
                player.release()
            }

            playerListener = null
            player = null
            currentIndex = 0
            isPlayingSpecificVideo = false
            currentlyPlayingVideo = null

            Log.i("PLAYER", "Reproductor limpiado correctamente")
        } catch (e: Exception) {
            Log.e("PLAYER", "Error limpiando reproductor: ${e.message}", e)

            playerListener = null
            player = null
            currentIndex = 0
            isPlayingSpecificVideo = false
            currentlyPlayingVideo = null
        }
    }

    private fun saveChannelConfig(channelNumber: Int) {
        try {
            val json = JSONObject().apply {
                put("channelNumber", channelNumber)
            }
            FileWriter(configFile).use { it.write(json.toString()) }
            Log.i("CONFIG", "Configuración guardada: Canal $channelNumber")
        } catch (e: Exception) {
            Log.e("CONFIG", "Error guardando configuración: ${e.message}", e)
        }
    }

    private fun loadChannelConfig(): Int? {
        return try {
            if (!configFile.exists()) return null
            val json = JSONObject(FileReader(configFile).readText())
            val channelNumber = json.getInt("channelNumber")
            Log.i("CONFIG", "Configuración cargada: Canal $channelNumber")
            channelNumber
        } catch (e: Exception) {
            Log.e("CONFIG", "Error cargando configuración: ${e.message}", e)
            null
        }
    }

    private fun saveLastPlayedVideo(videoName: String) {
        try {
            selectedChannel?.let { channel ->
                val lastPlayed = LastPlayedVideo(
                    channelNumber = channel.channelNumber,
                    videoName = videoName,
                    timestamp = System.currentTimeMillis()
                )

                val json = JSONObject().apply {
                    put("channelNumber", lastPlayed.channelNumber)
                    put("videoName", lastPlayed.videoName)
                    put("timestamp", lastPlayed.timestamp)
                }

                FileWriter(lastPlayedFile).use { it.write(json.toString()) }
                Log.i("LAST_PLAYED", "Último video guardado: $videoName en canal ${channel.channelNumber}")
            }
        } catch (e: Exception) {
            Log.e("LAST_PLAYED", "Error guardando último video: ${e.message}", e)
        }
    }

    private fun loadLastPlayedVideo(): LastPlayedVideo? {
        return try {
            if (!lastPlayedFile.exists()) return null

            val json = JSONObject(FileReader(lastPlayedFile).readText())
            val lastPlayed = LastPlayedVideo(
                channelNumber = json.getInt("channelNumber"),
                videoName = json.getString("videoName"),
                timestamp = json.getLong("timestamp")
            )

            Log.i("LAST_PLAYED", "Último video cargado: ${lastPlayed.videoName} del canal ${lastPlayed.channelNumber}")
            lastPlayed
        } catch (e: Exception) {
            Log.e("LAST_PLAYED", "Error cargando último video: ${e.message}", e)
            null
        }
    }

    private fun setupPlayer() {
        try {
            player = ExoPlayer.Builder(this).build().apply {
                playWhenReady = true
                repeatMode = Player.REPEAT_MODE_ONE
            }
            Log.i("PLAYER", "ExoPlayer inicializado con autoplay y loop habilitados")
        } catch (e: Exception) {
            Log.e("PLAYER", "Error inicializando ExoPlayer: ${e.message}", e)
        }
    }

    private suspend fun fetchVideos(): List<VideoInfo>? = withContext(Dispatchers.IO) {
        try {
            val currentChannel = selectedChannel ?: return@withContext null
            val serverUrl = currentChannel.serverUrl

            Log.i("FETCH", "Obteniendo videos de: $serverUrl")

            val url = URL(serverUrl)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 8000
            conn.readTimeout = 8000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("FETCH", "Error HTTP: ${conn.responseCode} para URL: $serverUrl")
                return@withContext null
            }

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            Log.d("FETCH", "Respuesta del servidor: $response")

            val list = mutableListOf<VideoInfo>()
            val jsonArray = JSONArray(response)
            for (i in 0 until jsonArray.length()) {
                val obj = jsonArray.getJSONObject(i)
                val nombre = obj.getString("nombre")
                val ruta = obj.getString("ruta")
                val duracion = obj.getInt("duracion")
                list.add(VideoInfo(nombre, ruta, duracion))
            }
            Log.i("FETCH", "Recibidos ${list.size} videos del canal: ${currentChannel.channelNumber}")
            list
        } catch (e: Exception) {
            Log.e("FETCH", "Error conectando con servidor: ${e.message}", e)
            null
        }
    }

    private fun downloadFile(urlStr: String, outputFile: File, onProgress: (Float) -> Unit = {}) {
        try {
            val currentChannel = selectedChannel ?: return

            outputFile.parentFile?.mkdirs()
            val url = URL(urlStr)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 20000
            conn.readTimeout = 20000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("DOWNLOAD", "Error HTTP ${conn.responseCode} para: $urlStr")
                return
            }

            val contentLength = conn.contentLength
            var totalBytesRead = 0L

            conn.inputStream.use { input ->
                FileOutputStream(outputFile).use { output ->
                    val buffer = ByteArray(8192)
                    var bytesRead: Int
                    while (input.read(buffer).also { bytesRead = it } != -1) {
                        output.write(buffer, 0, bytesRead)
                        totalBytesRead += bytesRead

                        if (contentLength > 0) {
                            val progress = (totalBytesRead.toFloat() / contentLength.toFloat())
                            onProgress(progress)
                        }
                    }
                }
            }
            Log.i("DOWNLOAD", "Descarga completada: ${outputFile.name}")
        } catch (e: Exception) {
            Log.e("DOWNLOAD", "Error descargando $urlStr: ${e.message}", e)
            if (outputFile.exists()) {
                outputFile.delete()
            }
        }
    }

    private suspend fun syncLocalWithServer(serverVideos: List<VideoInfo>) {
        val currentChannel = selectedChannel ?: return

        Log.i("SYNC", "Sincronizando canal: ${currentChannel.channelNumber}")

        val currentVideoNames = videoList.map { it.nombre }.toSet()
        val serverVideoNames = serverVideos.map { it.nombre }.toSet()

        val newVideos = serverVideos.filter { it.nombre !in currentVideoNames }

        if (newVideos.isNotEmpty()) {
            Log.i("SYNC", "Detectados ${newVideos.size} videos nuevos")

            for (newVideo in newVideos) {
                videoList.add(newVideo)
                Log.i("SYNC", "   + ${newVideo.nombre}")
            }

            saveVideosToFile()
        }

        val removedVideos = videoList.filter { it.nombre !in serverVideoNames }
        if (removedVideos.isNotEmpty()) {
            Log.i("SYNC", "Eliminando ${removedVideos.size} videos obsoletos")
            for (removedVideo in removedVideos) {
                Log.i("SYNC", "   - ${removedVideo.nombre}")
                val localFile = File(videoDir, removedVideo.nombre + ".mp4")
                if (localFile.exists()) {
                    localFile.delete()
                    Log.i("SYNC", "   Archivo eliminado: ${removedVideo.nombre}.mp4")
                }
            }
            videoList.removeAll(removedVideos)
            saveVideosToFile()
        }

        Log.i("SYNC", "Sincronización completada. Playlist: ${videoList.size} videos")
    }

    private suspend fun downloadMissingVideos() {
        val currentChannel = selectedChannel ?: return

        val missingVideos = videoList.filter { video ->
            val localFile = File(videoDir, video.nombre + ".mp4")
            !localFile.exists() || localFile.length() == 0L
        }

        if (missingVideos.isEmpty()) {
            Log.i("DOWNLOAD", "Todos los videos ya están descargados")
            return
        }

        downloadProgress.value = DownloadProgress(
            isDownloading = true,
            totalVideos = missingVideos.size,
            completedVideos = 0
        )

        for ((index, video) in missingVideos.withIndex()) {
            val localFile = File(videoDir, video.nombre + ".mp4")

            downloadProgress.value = downloadProgress.value.copy(
                currentVideo = video.nombre,
                completedVideos = index
            )

            progressText.value = "Descargando ${video.nombre}... (${index + 1}/${missingVideos.size})"

            withContext(Dispatchers.IO) {
                downloadFile(currentChannel.baseUrl + video.ruta, localFile) { progress ->
                    downloadProgress.value = downloadProgress.value.copy(
                        progress = progress
                    )
                }
            }

            if (localFile.exists() && localFile.length() > 0) {
                Log.i("DOWNLOAD", "${video.nombre} descargado correctamente (${localFile.length()} bytes)")
            } else {
                Log.w("DOWNLOAD", "Error descargando ${video.nombre}")
            }
        }

        downloadProgress.value = DownloadProgress(
            isDownloading = false,
            totalVideos = missingVideos.size,
            completedVideos = missingVideos.size
        )

        Log.i("DOWNLOAD", "Descarga completa: ${missingVideos.size} videos procesados")
    }

    private fun startPeriodicSync() {
        periodicSyncJob = scope.launch {
            while (isActive) {
                delay(30_000)

                val serverVideos = withTimeoutOrNull(5000) { fetchVideos() }
                if (serverVideos != null) {
                    val currentCount = videoList.size
                    syncLocalWithServer(serverVideos)

                    if (videoList.size > currentCount) {
                        downloadMissingVideos()
                        Log.i("PERIODIC_SYNC", "Nuevos videos sincronizados y descargados")
                    }
                } else {
                    Log.w("PERIODIC_SYNC", "No se pudo conectar al servidor para sincronización")
                }
            }
        }
    }

    private fun saveVideosToFile() {
        try {
            val json = JSONArray()
            videoList.forEach {
                val obj = JSONObject()
                obj.put("nombre", it.nombre)
                obj.put("ruta", it.ruta)
                obj.put("duracion", it.duracion)
                json.put(obj)
            }
            FileWriter(localListFile).use { it.write(json.toString()) }
        } catch (e: Exception) {
            Log.e("SAVE", "Error guardando videos: ${e.message}", e)
        }
    }

    private fun loadVideosFromFile() {
        try {
            if (!localListFile.exists()) return
            val json = JSONArray(FileReader(localListFile).readText())
            videoList.clear()
            for (i in 0 until json.length()) {
                val obj = json.getJSONObject(i)
                val nombre = obj.getString("nombre")
                val ruta = obj.getString("ruta")
                val duracion = obj.getInt("duracion")
                videoList.add(VideoInfo(nombre, ruta, duracion))
            }
            Log.i("LOAD", "Cargados ${videoList.size} videos desde archivo local")
        } catch (e: Exception) {
            Log.e("LOAD", "Error cargando videos locales: ${e.message}", e)
        }
    }

    private fun playSpecificVideo(index: Int) {
        if (videoList.isEmpty() || index < 0 || index >= videoList.size) {
            Log.w("PLAYER", "Índice de video inválido: $index")
            return
        }

        val video = videoList[index]
        Log.i("PLAYER", "Reproduciendo video: ${video.nombre} (índice: $index)")

        scope.launch {
            try {
                val localFile = File(videoDir, video.nombre + ".mp4")

                if (!localFile.exists() || localFile.length() == 0L) {
                    Log.w("PLAYER", "Archivo no disponible: ${video.nombre}")
                    selectedChannel?.let { channel ->
                        withContext(Dispatchers.IO) {
                            downloadFile(channel.baseUrl + video.ruta, localFile)
                        }
                    }

                    if (!localFile.exists() || localFile.length() == 0L) {
                        Log.w("PLAYER", "No se pudo recuperar archivo: ${video.nombre}")
                        val nextIndex = if (index + 1 < videoList.size) index + 1 else 0
                        if (nextIndex != index && videoList.size > 1) {
                            delay(1000)
                            playSpecificVideo(nextIndex)
                        }
                        return@launch
                    }
                }

                currentIndex = index
                isPlayingSpecificVideo = true
                currentlyPlayingVideo = video.nombre
                saveLastPlayedVideo(video.nombre)

                Log.i("PLAYER", "Configurando reproducción: ${video.nombre}")
                progressText.value = "Reproduciendo ${video.nombre}"

                player?.let { player ->
                    player.stop()
                    player.clearMediaItems()

                    playerListener?.let { listener ->
                        player.removeListener(listener)
                    }

                    delay(200)

                    val item = MediaItem.fromUri(Uri.fromFile(localFile))
                    player.setMediaItem(item)

                    playerListener = object : Player.Listener {
                        override fun onPlaybackStateChanged(state: Int) {
                            when (state) {
                                Player.STATE_READY -> {
                                    Log.i("PLAYER", "Video listo y reproduciéndose: ${video.nombre}")
                                }
                                Player.STATE_ENDED -> {
                                    Log.i("PLAYER", "Video terminado (pero debería repetir automáticamente)")
                                }
                                Player.STATE_BUFFERING -> {
                                    Log.i("PLAYER", "Cargando: ${video.nombre}")
                                }
                            }
                        }

                        override fun onPlayerError(error: androidx.media3.common.PlaybackException) {
                            Log.e("PLAYER", "Error: ${error.message}")
                            scope.launch {
                                delay(1000)
                                val nextIndex = if (currentIndex + 1 < videoList.size) currentIndex + 1 else 0
                                if (nextIndex != currentIndex && videoList.size > 1) {
                                    playSpecificVideo(nextIndex)
                                }
                            }
                        }
                    }

                    player.addListener(playerListener!!)
                    player.prepare()
                    player.play()
                }

            } catch (e: Exception) {
                Log.e("PLAYER", "Error: ${e.message}", e)
            }
        }
    }

    private suspend fun fetchLastPlayback(): Pair<VideoInfo, String>? = withContext(Dispatchers.IO) {
        try {
            val currentChannel = selectedChannel ?: return@withContext null
            val getLastVideoUrl = currentChannel.getLastVideo

            Log.d("PLAYBOOK", "Consultando getLastVideo: $getLastVideoUrl")

            val url = URL(getLastVideoUrl)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 5000
            conn.readTimeout = 5000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("PLAYBOOK", "getLastVideo no disponible (${conn.responseCode}) para: Canal ${currentChannel.channelNumber}")
                return@withContext null
            }

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            Log.d("PLAYBOOK", "Respuesta getLastVideo: $response")

            val json = JSONObject(response)
            if (!json.getBoolean("success")) {
                Log.w("PLAYBOOK", "getLastVideo retornó success=false para: Canal ${currentChannel.channelNumber}")
                return@withContext null
            }

            val data = json.getJSONObject("data")
            val nombre = data.getString("nombre")
            val ruta = data.getString("ruta")
            val duracion = data.getString("duracion").toInt()
            val playStamp = data.getString("play_stamp")

            Log.i("PLAYBOOK", "Último playback obtenido: $nombre (stamp: $playStamp)")
            return@withContext Pair(VideoInfo(nombre, ruta, duracion), playStamp)
        } catch (e: Exception) {
            val currentChannel = selectedChannel
            Log.e("PLAYBOOK", "Error obteniendo playback para Canal ${currentChannel?.channelNumber}: ${e.message}", e)
            null
        }
    }

    private fun startPlaybackChecker() {
        playbackCheckerJob = scope.launch {
            while (isActive) {
                try {
                    val currentChannel = selectedChannel
                    if (currentChannel != null) {
                        val result = fetchLastPlayback()
                        if (result != null) {
                            val (video, playStamp) = result
                            if (lastPlayStamp != playStamp) {
                                Log.i("CHECKER", "Nuevo comando de servidor: ${video.nombre} (stamp: $playStamp vs anterior: $lastPlayStamp)")
                                lastPlayStamp = playStamp

                                val localFile = File(videoDir, video.nombre + ".mp4")

                                val existingVideo = videoList.find { it.nombre == video.nombre }
                                if (existingVideo == null) {
                                    Log.i("CHECKER", "Nuevo video desde servidor: ${video.nombre}")
                                    videoList.add(video)
                                    saveVideosToFile()
                                }

                                if (!localFile.exists() || localFile.length() == 0L) {
                                    Log.i("CHECKER", "Descargando: ${video.nombre}")
                                    progressText.value = "Descargando video solicitado: ${video.nombre}..."
                                    withContext(Dispatchers.IO) {
                                        downloadFile(currentChannel.baseUrl + video.ruta, localFile)
                                    }
                                }

                                if (localFile.exists() && localFile.length() > 0) {
                                    Log.i("CHECKER", "Reproduciendo por comando del servidor: ${video.nombre}")
                                    val videoIndex = videoList.indexOfFirst { it.nombre == video.nombre }
                                    if (videoIndex != -1) {
                                        isPlayingSpecificVideo = false
                                        delay(500)
                                        playSpecificVideo(videoIndex)
                                    }
                                } else {
                                    Log.w("CHECKER", "No se pudo reproducir, archivo faltante: ${video.nombre}")
                                }
                            } else {
                                Log.d("CHECKER", "Mismo playback stamp, no hay cambios: $playStamp")
                            }
                        } else {
                            Log.d("CHECKER", "No hay respuesta del servidor getLastVideo para: Canal ${currentChannel.channelNumber}")
                        }
                    } else {
                        Log.w("CHECKER", "No hay canal seleccionado")
                    }
                } catch (e: Exception) {
                    Log.e("CHECKER", "Error en playback checker: ${e.message}", e)
                }
                delay(10_000)
            }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        Log.i("DESTROY", "Liberando recursos...")
        stopBackgroundTasks()

        player?.let { player ->
            try {
                player.pause()
                player.stop()
                player.clearMediaItems()
                playerListener?.let { listener ->
                    player.removeListener(listener)
                }
                player.release()
            } catch (e: Exception) {
                Log.e("DESTROY", "Error liberando reproductor: ${e.message}", e)
            }
        }
        player = null

        scope.cancel()
    }
}