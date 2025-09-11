package com.mantraxstudios.frutviewerapp

import android.net.Uri
import android.os.Bundle
import android.util.Log
import android.view.WindowManager
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
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
import kotlinx.coroutines.TimeoutCancellationException
import kotlinx.coroutines.CompletableDeferred

val serverIP: String = "http://vds.srcardboard.cl/GetVideos.php"
val baseUrl = "http://vds.srcardboard.cl/"

data class VideoInfo(
    val nombre: String,
    val ruta: String,
    var duracion: Int
)

class MainActivity : ComponentActivity() {

    private var player: ExoPlayer? = null
    private val scope = CoroutineScope(Dispatchers.Main + Job())
    private val videoList = mutableStateListOf<VideoInfo>()
    private val localListFile by lazy { File(getExternalFilesDir(null), "videos.json") }
    private val videoDir by lazy { File(getExternalFilesDir(null), "videos").apply { mkdirs() } }

    private var currentIndex = 0
    private var playerListener: Player.Listener? = null
    private var lastPlayStamp: String? = null
    private var progressText = mutableStateOf("Sincronizando con servidor...")

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

                    Box(modifier = Modifier.fillMaxSize()) {
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
                                    onDismissRequest = { menuExpanded = false }
                                ) {
                                    videoList.forEachIndexed { index, video ->
                                        DropdownMenuItem(
                                            text = { Text(video.nombre) },
                                            onClick = {
                                                menuExpanded = false
                                                playNow(index)
                                            }
                                        )
                                    }
                                }
                            }
                        } else {
                            Text(
                                text = currentProgressText,
                                modifier = Modifier.align(Alignment.Center)
                            )
                        }
                    }

                    LaunchedEffect(Unit) {
                        setupPlayer()

                        val serverVideos = withTimeoutOrNull(5000) { fetchVideos() }

                        if (serverVideos != null) {
                            Log.i("SYNC", "Servidor sincronizado correctamente")
                            syncLocalWithServer(serverVideos)
                        } else {
                            Log.w("SYNC", "Sin conexión con servidor, cargando videos locales...")
                            loadVideosFromFile()
                        }

                        // Descargar videos faltantes inicial
                        downloadMissingVideos()

                        if (videoList.isNotEmpty()) {
                            isReady = true
                            startLoop()
                            startPlaybackChecker()
                            startPeriodicSync() // 🔥 Sincronización periódica
                        } else {
                            progressText.value = "No hay videos disponibles"
                        }
                    }
                }
            }
        }
    }

    private fun setupPlayer() {
        player = ExoPlayer.Builder(this).build()
        Log.i("PLAYER", "ExoPlayer inicializado")
    }

    private suspend fun fetchVideos(): List<VideoInfo>? = withContext(Dispatchers.IO) {
        try {
            val url = URL(serverIP)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 3000
            conn.readTimeout = 3000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) {
                Log.w("FETCH", "Error HTTP: ${conn.responseCode}")
                return@withContext null
            }

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            val list = mutableListOf<VideoInfo>()
            val jsonArray = JSONArray(response)
            for (i in 0 until jsonArray.length()) {
                val obj = jsonArray.getJSONObject(i)
                val nombre = obj.getString("nombre")
                val ruta = obj.getString("ruta")
                val duracion = obj.getInt("duracion")
                list.add(VideoInfo(nombre, ruta, duracion))
            }
            Log.i("FETCH", "Recibidos ${list.size} videos del servidor")
            list
        } catch (e: Exception) {
            Log.e("FETCH", "Error al conectar con servidor: ${e.message}", e)
            null
        }
    }

    private fun downloadFile(urlStr: String, outputFile: File) {
        try {
            outputFile.parentFile?.mkdirs()
            val url = URL(urlStr)
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 15000
            conn.readTimeout = 15000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) return

            conn.inputStream.use { input ->
                FileOutputStream(outputFile).use { output ->
                    val buffer = ByteArray(4096)
                    var bytesRead: Int
                    while (input.read(buffer).also { bytesRead = it } != -1) {
                        output.write(buffer, 0, bytesRead)
                    }
                }
            }
            Log.i("DOWNLOAD", "Descarga completada: ${outputFile.name}")
        } catch (e: Exception) {
            Log.e("DOWNLOAD", "Error descargando archivo: ${e.message}", e)
        }
    }

    // 🔥 Mejorada: detecta nuevos videos y los añade
    private suspend fun syncLocalWithServer(serverVideos: List<VideoInfo>) {
        Log.i("SYNC", "Sincronizando con servidor...")

        val currentVideoNames = videoList.map { it.nombre }.toSet()
        val serverVideoNames = serverVideos.map { it.nombre }.toSet()

        // Detectar nuevos videos en el servidor
        val newVideos = serverVideos.filter { it.nombre !in currentVideoNames }

        if (newVideos.isNotEmpty()) {
            Log.i("SYNC", "Detectados ${newVideos.size} videos nuevos")

            // Añadir nuevos videos a la lista
            for (newVideo in newVideos) {
                videoList.add(newVideo)
                Log.i("SYNC", "Añadido nuevo video: ${newVideo.nombre}")
            }

            // Guardar la lista actualizada
            saveVideosToFile()

            // Descargar los nuevos videos
            for (newVideo in newVideos) {
                val localFile = File(videoDir, newVideo.nombre + ".mp4")
                if (!localFile.exists()) {
                    progressText.value = "Descargando nuevo video: ${newVideo.nombre}..."
                    withContext(Dispatchers.IO) {
                        downloadFile(baseUrl + newVideo.ruta, localFile)
                    }
                }
            }
        }

        // Detectar videos eliminados del servidor (opcional)
        val removedVideos = videoList.filter { it.nombre !in serverVideoNames }
        if (removedVideos.isNotEmpty()) {
            Log.i("SYNC", "Detectados ${removedVideos.size} videos eliminados del servidor")
            // Opcional: eliminar videos que ya no están en el servidor
            videoList.removeAll(removedVideos)
            saveVideosToFile()
        }

        Log.i("SYNC", "Sincronización completada. Playlist: ${videoList.size} videos")
    }

    // 🔥 Función separada para descargar videos faltantes
    private suspend fun downloadMissingVideos() {
        for (video in videoList) {
            val localFile = File(videoDir, video.nombre + ".mp4")
            if (!localFile.exists()) {
                progressText.value = "Descargando ${video.nombre}..."
                withContext(Dispatchers.IO) {
                    downloadFile(baseUrl + video.ruta, localFile)
                }
                Log.i("DOWNLOAD", "${video.nombre} descargado")
            }
        }
    }

    // 🔥 Sincronización periódica cada 30 segundos
    private fun startPeriodicSync() {
        scope.launch {
            while (isActive) {
                delay(30_000) // Esperar 30 segundos

                val serverVideos = withTimeoutOrNull(5000) { fetchVideos() }
                if (serverVideos != null) {
                    val currentCount = videoList.size
                    syncLocalWithServer(serverVideos)

                    if (videoList.size > currentCount) {
                        // Se añadieron nuevos videos, descargarlos
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

    private fun startLoop() {
        scope.launch {
            while (isActive) {
                if (videoList.isEmpty()) { delay(1000); continue }

                val video = videoList[currentIndex]
                val localFile = File(videoDir, video.nombre + ".mp4")

                if (!localFile.exists() || localFile.length() == 0L) {
                    Log.w("PLAYER", "Archivo no disponible o vacío: ${video.nombre}")
                    currentIndex = (currentIndex + 1) % videoList.size
                    continue
                }

                progressText.value = "Reproduciendo ${video.nombre} (${video.duracion}s)"

                val item = MediaItem.fromUri(Uri.fromFile(localFile))

                delay(100)

                player?.setMediaItem(item)
                player?.prepare()
                player?.play()

                playerListener?.let { player?.removeListener(it) }

                val videoFinished = CompletableDeferred<Unit>()
                playerListener = object : Player.Listener {
                    override fun onPlaybackStateChanged(state: Int) {
                        if (state == Player.STATE_ENDED && !videoFinished.isCompleted) {
                            videoFinished.complete(Unit)
                        }
                    }
                }
                player?.addListener(playerListener!!)

                try {
                    withTimeout(video.duracion * 1000L) {
                        videoFinished.await()
                    }
                } catch (_: TimeoutCancellationException) {
                    Log.i("PLAYER", "Timeout alcanzado para ${video.nombre}, pasando al siguiente")
                    player?.pause()
                }

                currentIndex = (currentIndex + 1) % videoList.size
            }
        }
    }

    private fun playNow(index: Int) {
        currentIndex = index
        scope.launch {
            val video = videoList[currentIndex]
            val localFile = File(videoDir, video.nombre + ".mp4")
            if (!localFile.exists() || localFile.length() == 0L) return@launch

            Log.i("PLAYER", "▶ Cambio inmediato a: ${video.nombre}")

            player?.stop()

            val item = MediaItem.fromUri(Uri.fromFile(localFile))
            delay(100)
            player?.setMediaItem(item)
            player?.prepare()
            player?.play()
        }
    }

    private suspend fun fetchLastPlayback(): Pair<VideoInfo, String>? = withContext(Dispatchers.IO) {
        try {
            val url = URL(baseUrl + "get_last_playback.php")
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 3000
            conn.readTimeout = 3000
            conn.requestMethod = "GET"
            conn.connect()

            if (conn.responseCode != HttpURLConnection.HTTP_OK) return@withContext null

            val response = conn.inputStream.bufferedReader().use { it.readText() }
            val json = JSONObject(response)
            if (!json.getBoolean("success")) return@withContext null

            val data = json.getJSONObject("data")
            val nombre = data.getString("nombre")
            val ruta = data.getString("ruta")
            val duracion = data.getString("duracion").toInt()
            val playStamp = data.getString("play_stamp")

            return@withContext Pair(VideoInfo(nombre, ruta, duracion), playStamp)
        } catch (e: Exception) {
            Log.e("PLAYBACK", "Error obteniendo último playback: ${e.message}", e)
            null
        }
    }

    private fun startPlaybackChecker() {
        scope.launch {
            while (isActive) {
                val result = fetchLastPlayback()
                if (result != null) {
                    val (video, playStamp) = result
                    if (lastPlayStamp != playStamp) {
                        lastPlayStamp = playStamp
                        val localFile = File(videoDir, video.nombre + ".mp4")

                        // Verificar si el video está en la lista, si no, añadirlo
                        val existingVideo = videoList.find { it.nombre == video.nombre }
                        if (existingVideo == null) {
                            Log.i("CHECKER", "Nuevo video detectado desde playback: ${video.nombre}")
                            videoList.add(video)
                            saveVideosToFile()
                        }

                        if (!localFile.exists()) {
                            Log.i("CHECKER", "Descargando nuevo video: ${video.nombre}")
                            progressText.value = "Descargando video solicitado: ${video.nombre}..."
                            withContext(Dispatchers.IO) {
                                downloadFile(baseUrl + video.ruta, localFile)
                            }
                        }

                        if (localFile.exists()) {
                            Log.i("CHECKER", "Reproduciendo video detectado: ${video.nombre}")
                            player?.stop()
                            val item = MediaItem.fromUri(Uri.fromFile(localFile))
                            delay(100)
                            player?.setMediaItem(item)
                            player?.prepare()
                            player?.play()
                        } else {
                            Log.w("CHECKER", "No se pudo reproducir, archivo faltante: ${video.nombre}")
                        }
                    }
                }
                delay(10_000)
            }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        player?.release()
        scope.cancel()
    }
}