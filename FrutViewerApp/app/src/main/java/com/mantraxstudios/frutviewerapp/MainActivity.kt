package com.mantraxstudios.frutviewerapp

import android.net.Uri
import android.os.Bundle
import android.util.Log
import android.view.WindowManager
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material3.HorizontalDivider
import androidx.compose.ui.window.Dialog
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

data class Channel(
    val id: String,
    val name: String,
    val serverUrl: String,
    val baseUrl: String,
    val getLastVideo: String,
)

val availableChannels = listOf(
    Channel(
        id = "channel1",
        name = "Canal 1",
        serverUrl = "http://vds.srcardboard.cl/GetVideos.php?channel=Channel_1",
        baseUrl = "http://vds.srcardboard.cl/",
        getLastVideo = "http://vds.srcardboard.cl/get_last_playback.php?channel=Channel_1"
    ),
    Channel(
        id = "channel2",
        name = "Canal 2",
        serverUrl = "http://vds.srcardboard.cl/GetVideos.php?channel=Channel_2",
        baseUrl = "http://vds.srcardboard.cl/",
        getLastVideo = "http://vds.srcardboard.cl/get_last_playback.php?channel=Channel_2"
    ),
    Channel(
        id = "channel3",
        name = "Canal 3",
        serverUrl = "http://vds.srcardboard.cl/GetVideos.php?channel=Channel_3",
        baseUrl = "http://vds.srcardboard.cl/",
        getLastVideo = "http://vds.srcardboard.cl/get_last_playback.php?channel=Channel_3"
    ),
    Channel(
        id = "channel4",
        name = "Canal 4",
        serverUrl = "http://vds.srcardboard.cl/GetVideos.php?channel=Channel_4",
        baseUrl = "http://vds.srcardboard.cl/",
        getLastVideo = "http://vds.srcardboard.cl/get_last_playback.php?channel=Channel_4"
    )
    ,
    Channel(
        id = "channel5",
        name = "Canal 5",
        serverUrl = "http://vds.srcardboard.cl/GetVideos.php?channel=Channel_5",
        baseUrl = "http://vds.srcardboard.cl/",
        getLastVideo = "http://vds.srcardboard.cl/get_last_playback.php?channel=Channel_5"
    )
)

data class VideoInfo(
    val nombre: String,
    val ruta: String,
    var duracion: Int
)

class MainActivity : ComponentActivity() {

    private var player: ExoPlayer? = null
    private val scope = CoroutineScope(Dispatchers.Main + Job())
    private val videoList = mutableStateListOf<VideoInfo>()
    private val configFile by lazy { File(getExternalFilesDir(null), "channel_config.json") }
    private val localListFile by lazy { File(getExternalFilesDir(null), "videos.json") }
    private val videoDir by lazy { File(getExternalFilesDir(null), "videos").apply { mkdirs() } }

    private var currentIndex = 0
    private var playerListener: Player.Listener? = null
    private var lastPlayStamp: String? = null
    private var progressText = mutableStateOf("Inicializando...")
    private var playbackCheckerJob: Job? = null
    private var periodicSyncJob: Job? = null

    private var selectedChannel: Channel? = null

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
                    var showChannelSelector by remember { mutableStateOf(true) }
                    var isReady by remember { mutableStateOf(false) }
                    var menuExpanded by remember { mutableStateOf(false) }
                    var showSettings by remember { mutableStateOf(false) }
                    val currentProgressText by progressText

                    if (showChannelSelector) {
                        ChannelSelectorScreen(
                            channels = availableChannels,
                            onChannelSelected = { channel ->
                                changeChannel(channel)
                                showChannelSelector = false

                                scope.launch {
                                    initializeApp { isReady = it }
                                }
                            }
                        )
                    } else {
                        Box(modifier = Modifier.fillMaxSize()) {
                            // Always show the video player when player exists and is ready
                            if (player != null && isReady) {
                                AndroidView(
                                    factory = { ctx ->
                                        PlayerView(ctx).apply {
                                            useController = false
                                            player = this@MainActivity.player
                                        }
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
                                    selectedChannel?.let {
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Text(
                                            text = "Canal: ${it.name}",
                                            fontSize = 12.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }

                            // ALWAYS show the menus - moved outside the conditional
                            Box(
                                modifier = Modifier
                                    .align(Alignment.TopEnd)
                                    .padding(12.dp)
                            ) {
                                Row(
                                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    IconButton(onClick = { showSettings = true }) {
                                        Icon(
                                            imageVector = Icons.Default.Settings,
                                            contentDescription = "Configuración"
                                        )
                                    }

                                    Box {
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

                                            // Show message when no videos available
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
                                                // Existing video list logic
                                                if (videoList.size > 8) {
                                                    Column(
                                                        modifier = Modifier
                                                            .heightIn(max = 400.dp)
                                                    ) {
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
                                    }
                                }
                            }

                            // ALWAYS show settings dialog when requested
                            if (showSettings) {
                                SettingsDialog(
                                    currentChannel = selectedChannel,
                                    channels = availableChannels,
                                    onChannelChanged = { channel ->
                                        changeChannel(channel)

                                        scope.launch {
                                            stopBackgroundTasks()
                                            videoList.clear()
                                            isReady = false
                                            cleanupPlayer()
                                            initializeApp { isReady = it }
                                        }
                                    },
                                    onDismiss = { showSettings = false }
                                )
                            }
                        }
                    }

                    LaunchedEffect(Unit) {
                        val savedChannel = loadChannelConfig()
                        if (savedChannel != null) {
                            changeChannel(savedChannel)
                            showChannelSelector = false
                            initializeApp { isReady = it }
                        }
                    }

                }
            }
        }
    }

    private fun changeChannel(channel: Channel) {
        selectedChannel = channel
        lastPlayStamp = null
        saveChannelConfig(channel)
        Log.i("CHANNEL", "🔄 Canal cambiado a: ${channel.name}")
        Log.i("CHANNEL", "   📡 Server URL: ${channel.serverUrl}")
        Log.i("CHANNEL", "   🌐 Base URL: ${channel.baseUrl}")
        Log.i("CHANNEL", "   📺 GetLastVideo URL: ${channel.getLastVideo}")
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

    private suspend fun initializeApp(onReady: (Boolean) -> Unit) {
        progressText.value = "Configurando reproductor..."

        stopBackgroundTasks()
        cleanupPlayer()
        setupPlayer()

        progressText.value = "Sincronizando con servidor..."
        val serverVideos = withTimeoutOrNull(5000) { fetchVideos() }

        if (serverVideos != null) {
            Log.i("SYNC", "Servidor sincronizado correctamente")
            syncLocalWithServer(serverVideos)
        } else {
            Log.w("SYNC", "Sin conexión con servidor, cargando videos locales...")
            loadVideosFromFile()
        }

        progressText.value = "Descargando videos..."
        downloadMissingVideos()

        if (videoList.isNotEmpty()) {
            onReady(true)
            startPlaybackChecker()
            startPeriodicSync()

            delay(500)
            playSpecificVideo(0)
        } else {
            progressText.value = "No hay videos en este canal"
            onReady(false)
        }
    }

    private fun cleanupPlayer() {
        try {
            Log.i("PLAYER", "🧹 Limpiando reproductor...")

            player?.let { player ->
                player.stop()
                player.clearMediaItems()

                playerListener?.let { listener ->
                    player.removeListener(listener)
                }
            }

            playerListener = null
            player?.release()
            player = null

            currentIndex = 0
            isPlayingSpecificVideo = false
            currentlyPlayingVideo = null

            Log.i("PLAYER", "✅ Reproductor limpiado correctamente")
        } catch (e: Exception) {
            Log.e("PLAYER", "❌ Error limpiando reproductor: ${e.message}", e)
        }
    }

    private fun saveChannelConfig(channel: Channel) {
        try {
            val json = JSONObject().apply {
                put("id", channel.id)
                put("name", channel.name)
                put("serverUrl", channel.serverUrl)
                put("baseUrl", channel.baseUrl)
                put("getLastVideo", channel.getLastVideo)
            }
            FileWriter(configFile).use { it.write(json.toString()) }
            Log.i("CONFIG", "Configuración guardada: ${channel.name}")
        } catch (e: Exception) {
            Log.e("CONFIG", "Error guardando configuración: ${e.message}", e)
        }
    }

    private fun loadChannelConfig(): Channel? {
        return try {
            if (!configFile.exists()) return null
            val json = JSONObject(FileReader(configFile).readText())

            val channelId = json.getString("id")

            val getLastVideoUrl = if (json.has("getLastVideo")) {
                json.getString("getLastVideo")
            } else {
                availableChannels.find { it.id == channelId }?.getLastVideo ?: ""
            }

            val channel = Channel(
                id = channelId,
                name = json.getString("name"),
                serverUrl = json.getString("serverUrl"),
                baseUrl = json.getString("baseUrl"),
                getLastVideo = getLastVideoUrl
            )
            Log.i("CONFIG", "Configuración cargada: ${channel.name}")
            channel
        } catch (e: Exception) {
            Log.e("CONFIG", "Error cargando configuración: ${e.message}", e)
            null
        }
    }

    private fun setupPlayer() {
        try {
            player = ExoPlayer.Builder(this).build().apply {
                playWhenReady = false
                repeatMode = Player.REPEAT_MODE_OFF
            }
            Log.i("PLAYER", "ExoPlayer inicializado correctamente")
        } catch (e: Exception) {
            Log.e("PLAYER", "Error inicializando ExoPlayer: ${e.message}", e)
        }
    }

    private suspend fun fetchVideos(): List<VideoInfo>? = withContext(Dispatchers.IO) {
        try {
            val currentChannel = selectedChannel ?: return@withContext null
            val serverUrl = currentChannel.serverUrl

            Log.i("FETCH", "🔍 Obteniendo videos de: $serverUrl")

            val url = URL(serverUrl)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 3000
            conn.readTimeout = 3000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("FETCH", "❌ Error HTTP: ${conn.responseCode} para URL: $serverUrl")
                return@withContext null
            }

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            Log.d("FETCH", "📥 Respuesta del servidor: $response")

            val list = mutableListOf<VideoInfo>()
            val jsonArray = JSONArray(response)
            for (i in 0 until jsonArray.length()) {
                val obj = jsonArray.getJSONObject(i)
                val nombre = obj.getString("nombre")
                val ruta = obj.getString("ruta")
                val duracion = obj.getInt("duracion")
                list.add(VideoInfo(nombre, ruta, duracion))
            }
            Log.i("FETCH", "✅ Recibidos ${list.size} videos del canal: ${currentChannel.name}")
            list
        } catch (e: Exception) {
            Log.e("FETCH", "❌ Error conectando con servidor: ${e.message}", e)
            null
        }
    }

    private fun downloadFile(urlStr: String, outputFile: File) {
        try {
            val currentChannel = selectedChannel ?: return

            outputFile.parentFile?.mkdirs()
            val url = URL(urlStr)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 15000
            conn.readTimeout = 15000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("DOWNLOAD", "❌ Error HTTP ${conn.responseCode} para: $urlStr")
                return
            }

            conn.inputStream.use { input ->
                FileOutputStream(outputFile).use { output ->
                    val buffer = ByteArray(4096)
                    var bytesRead: Int
                    while (input.read(buffer).also { bytesRead = it } != -1) {
                        output.write(buffer, 0, bytesRead)
                    }
                }
            }
            Log.i("DOWNLOAD", "✅ Descarga completada: ${outputFile.name}")
        } catch (e: Exception) {
            Log.e("DOWNLOAD", "❌ Error descargando $urlStr: ${e.message}", e)
        }
    }

    private suspend fun syncLocalWithServer(serverVideos: List<VideoInfo>) {
        val currentChannel = selectedChannel ?: return

        Log.i("SYNC", "🔄 Sincronizando canal: ${currentChannel.name}")

        val currentVideoNames = videoList.map { it.nombre }.toSet()
        val serverVideoNames = serverVideos.map { it.nombre }.toSet()

        val newVideos = serverVideos.filter { it.nombre !in currentVideoNames }

        if (newVideos.isNotEmpty()) {
            Log.i("SYNC", "📥 Detectados ${newVideos.size} videos nuevos")

            for (newVideo in newVideos) {
                videoList.add(newVideo)
                Log.i("SYNC", "   ➕ ${newVideo.nombre}")
            }

            saveVideosToFile()

            for (newVideo in newVideos) {
                val localFile = File(videoDir, newVideo.nombre + ".mp4")
                if (!localFile.exists()) {
                    progressText.value = "Descargando nuevo video: ${newVideo.nombre}..."
                    withContext(Dispatchers.IO) {
                        downloadFile(currentChannel.baseUrl + newVideo.ruta, localFile)
                    }
                }
            }
        }

        val removedVideos = videoList.filter { it.nombre !in serverVideoNames }
        if (removedVideos.isNotEmpty()) {
            Log.i("SYNC", "🗑️ Eliminando ${removedVideos.size} videos obsoletos")
            for (removedVideo in removedVideos) {
                Log.i("SYNC", "   ➖ ${removedVideo.nombre}")
            }
            videoList.removeAll(removedVideos)
            saveVideosToFile()
        }

        Log.i("SYNC", "✅ Sincronización completada. Playlist: ${videoList.size} videos")
    }

    private suspend fun downloadMissingVideos() {
        val currentChannel = selectedChannel ?: return

        for (video in videoList) {
            val localFile = File(videoDir, video.nombre + ".mp4")
            if (!localFile.exists()) {
                progressText.value = "Descargando ${video.nombre}..."
                withContext(Dispatchers.IO) {
                    downloadFile(currentChannel.baseUrl + video.ruta, localFile)
                }
                Log.i("DOWNLOAD", "📥 ${video.nombre} descargado")
            }
        }
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
        Log.i("PLAYER", "🎬 Solicitud para reproducir video específico: ${video.nombre} (índice: $index)")

        scope.launch {
            try {
                val localFile = File(videoDir, video.nombre + ".mp4")
                if (!localFile.exists() || localFile.length() == 0L) {
                    Log.w("PLAYER", "Archivo no disponible: ${video.nombre}")
                    if (index + 1 < videoList.size) {
                        playSpecificVideo(index + 1)
                    }
                    return@launch
                }

                currentIndex = index
                isPlayingSpecificVideo = true
                currentlyPlayingVideo = video.nombre

                Log.i("PLAYER", "▶ Configurando reproducción: ${video.nombre}")
                progressText.value = "Reproduciendo ${video.nombre}"

                player?.let { player ->
                    player.stop()
                    player.clearMediaItems()

                    playerListener?.let { listener ->
                        player.removeListener(listener)
                    }
                }

                delay(300)

                player?.let { player ->
                    val item = MediaItem.fromUri(Uri.fromFile(localFile))
                    player.setMediaItem(item)

                    playerListener = object : Player.Listener {
                        override fun onPlaybackStateChanged(state: Int) {
                            when (state) {
                                Player.STATE_READY -> {
                                    Log.i("PLAYER", "✅ Video listo: ${video.nombre}")
                                    player.play()
                                }
                                Player.STATE_ENDED -> {
                                    Log.i("PLAYER", "🔄 Video terminado, repitiendo: ${video.nombre}")
                                    scope.launch {
                                        delay(100)
                                        if (currentlyPlayingVideo == video.nombre && isPlayingSpecificVideo) {
                                            player.seekTo(0)
                                            player.play()
                                        }
                                    }
                                }
                                Player.STATE_IDLE -> {
                                    Log.i("PLAYER", "⏸ Reproductor en idle")
                                }
                                Player.STATE_BUFFERING -> {
                                    Log.i("PLAYER", "⏳ Cargando: ${video.nombre}")
                                }
                            }
                        }

                        override fun onPlayerError(error: androidx.media3.common.PlaybackException) {
                            Log.e("PLAYER", "❌ Error reproduciendo ${video.nombre}: ${error.message}")
                            if (currentIndex + 1 < videoList.size) {
                                scope.launch {
                                    delay(1000)
                                    playSpecificVideo(currentIndex + 1)
                                }
                            }
                        }
                    }

                    player.addListener(playerListener!!)
                    player.prepare()
                }

            } catch (e: Exception) {
                Log.e("PLAYER", "❌ Error configurando video ${video.nombre}: ${e.message}", e)
            }
        }
    }

    private suspend fun fetchLastPlayback(): Pair<VideoInfo, String>? = withContext(Dispatchers.IO) {
        try {
            val currentChannel = selectedChannel ?: return@withContext null
            val getLastVideoUrl = currentChannel.getLastVideo

            Log.d("PLAYBOOK", "🔍 Consultando getLastVideo: $getLastVideoUrl")

            val url = URL(getLastVideoUrl)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 3000
            conn.readTimeout = 3000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("PLAYBOOK", "❌ getLastVideo no disponible (${conn.responseCode}) para: ${currentChannel.name}")
                return@withContext null
            }

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            Log.d("PLAYBOOK", "📥 Respuesta getLastVideo: $response")

            val json = JSONObject(response)
            if (!json.getBoolean("success")) {
                Log.w("PLAYBOOK", "⚠️ getLastVideo retornó success=false para: ${currentChannel.name}")
                return@withContext null
            }

            val data = json.getJSONObject("data")
            val nombre = data.getString("nombre")
            val ruta = data.getString("ruta")
            val duracion = data.getString("duracion").toInt()
            val playStamp = data.getString("play_stamp")

            Log.i("PLAYBOOK", "✅ Último playback obtenido: $nombre (stamp: $playStamp)")
            return@withContext Pair(VideoInfo(nombre, ruta, duracion), playStamp)
        } catch (e: Exception) {
            val currentChannel = selectedChannel
            Log.e("PLAYBOOK", "❌ Error obteniendo playback para ${currentChannel?.name}: ${e.message}", e)
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
                                Log.i("CHECKER", "🔔 Nuevo comando de servidor: ${video.nombre} (stamp: $playStamp vs anterior: $lastPlayStamp)")
                                lastPlayStamp = playStamp

                                val localFile = File(videoDir, video.nombre + ".mp4")

                                val existingVideo = videoList.find { it.nombre == video.nombre }
                                if (existingVideo == null) {
                                    Log.i("CHECKER", "📥 Nuevo video desde servidor: ${video.nombre}")
                                    videoList.add(video)
                                    saveVideosToFile()
                                }

                                if (!localFile.exists()) {
                                    Log.i("CHECKER", "⬇️ Descargando: ${video.nombre}")
                                    progressText.value = "Descargando video solicitado: ${video.nombre}..."
                                    withContext(Dispatchers.IO) {
                                        downloadFile(currentChannel.baseUrl + video.ruta, localFile)
                                    }
                                }

                                if (localFile.exists()) {
                                    Log.i("CHECKER", "🎬 Reproduciendo por comando del servidor: ${video.nombre}")
                                    val videoIndex = videoList.indexOfFirst { it.nombre == video.nombre }
                                    if (videoIndex != -1) {
                                        isPlayingSpecificVideo = false
                                        delay(500)
                                        playSpecificVideo(videoIndex)
                                    }
                                } else {
                                    Log.w("CHECKER", "❌ No se pudo reproducir, archivo faltante: ${video.nombre}")
                                }
                            } else {
                                Log.d("CHECKER", "📡 Mismo playback stamp, no hay cambios: $playStamp")
                            }
                        } else {
                            Log.d("CHECKER", "📡 No hay respuesta del servidor getLastVideo para: ${currentChannel.name}")
                        }
                    } else {
                        Log.w("CHECKER", "⚠️ No hay canal seleccionado")
                    }
                } catch (e: Exception) {
                    Log.e("CHECKER", "❌ Error en playback checker: ${e.message}", e)
                }
                delay(10_000)
            }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        stopBackgroundTasks()
        player?.release()
        scope.cancel()
    }
}

@Composable
fun ChannelSelectorScreen(
    channels: List<Channel>,
    onChannelSelected: (Channel) -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text(
            text = "Selecciona un Canal",
            fontSize = 24.sp,
            fontWeight = FontWeight.Bold
        )

        Spacer(modifier = Modifier.height(32.dp))

        LazyColumn(
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(channels) { channel ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(80.dp),
                    onClick = { onChannelSelected(channel) }
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp),
                        verticalArrangement = Arrangement.Center
                    ) {
                        Text(
                            text = channel.name,
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Medium
                        )
                        Text(
                            text = channel.serverUrl,
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun SettingsDialog(
    currentChannel: Channel?,
    channels: List<Channel>,
    onChannelChanged: (Channel) -> Unit,
    onDismiss: () -> Unit
) {
    Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Column(
                modifier = Modifier.padding(24.dp)
            ) {
                Text(
                    text = "Configuración",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold
                )

                Spacer(modifier = Modifier.height(16.dp))

                Text(
                    text = "Canal Actual: ${currentChannel?.name ?: "Ninguno"}",
                    fontSize = 14.sp
                )

                Spacer(modifier = Modifier.height(16.dp))

                Text(
                    text = "Cambiar Canal:",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Medium
                )

                Spacer(modifier = Modifier.height(8.dp))

                LazyColumn(
                    modifier = Modifier.heightIn(max = 200.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(channels) { channel ->
                        OutlinedButton(
                            onClick = {
                                onChannelChanged(channel)
                                onDismiss()
                            },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(channel.name)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End
                ) {
                    TextButton(onClick = onDismiss) {
                        Text("Cerrar")
                    }
                }
            }
        }
    }
}