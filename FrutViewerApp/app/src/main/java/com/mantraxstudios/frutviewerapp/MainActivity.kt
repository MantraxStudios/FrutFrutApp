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

val serverIP: String = "http://192.168.1.19/FrutFrut/GetVideos.php"
val baseUrl = "http://192.168.1.19/FrutFrut/"

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

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        Log.i("MAIN", "onCreate iniciado")

        WindowCompat.setDecorFitsSystemWindows(window, false)
        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)

        setContent {
            FrutViewerAppTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    var progressText by remember { mutableStateOf("Sincronizando con servidor...") }
                    var isReady by remember { mutableStateOf(false) }
                    var menuExpanded by remember { mutableStateOf(false) }

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
                                text = progressText,
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

                        // Descargar videos faltantes
                        for (video in videoList) {
                            val localFile = File(videoDir, video.nombre + ".mp4")
                            if (!localFile.exists()) {
                                progressText = "Descargando ${video.nombre}..."
                                withContext(Dispatchers.IO) {
                                    downloadFile(baseUrl + video.ruta, localFile)
                                }
                                Log.i("DOWNLOAD", "${video.nombre} descargado")
                            }
                        }

                        if (videoList.isNotEmpty()) {
                            isReady = true
                            startLoop { status -> progressText = status }
                        } else {
                            progressText = "No hay videos disponibles"
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
        } catch (e: Exception) {
            Log.e("DOWNLOAD", "Error descargando archivo: ${e.message}", e)
        }
    }

    private fun syncLocalWithServer(serverVideos: List<VideoInfo>) {
        Log.i("SYNC", "Sincronizando con servidor...")
        videoList.clear()
        for (serverVideo in serverVideos) {
            videoList.add(serverVideo)
        }
        saveVideosToFile()
        Log.i("SYNC", "Sincronización completada. Playlist: ${videoList.size} videos")
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

    private fun startLoop(onStatus: (String) -> Unit) {
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

                onStatus("Reproduciendo ${video.nombre} (${video.duracion}s)")

                val item = MediaItem.fromUri(Uri.fromFile(localFile))

                // Pequeño delay para asegurar que el archivo está listo
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

                // Limitar la reproducción al tiempo de la API
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

            Log.i("PLAYER", "▶ Cambio manual: ${video.nombre}")

            val item = MediaItem.fromUri(Uri.fromFile(localFile))
            delay(100)
            player?.setMediaItem(item)
            player?.prepare()
            player?.play()
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        player?.release()
        scope.cancel()
    }
}
