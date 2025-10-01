// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    function switchTab(activeTab) {
        // Remove active class from all tabs and contents
        tabButtons.forEach(btn => {
            btn.classList.remove('active', 'border-blue-500', 'border-green-500', 'text-blue-400', 'text-green-400');
            btn.classList.add('text-slate-400', 'border-transparent');
        });
        tabContents.forEach(content => content.classList.add('hidden'));
        
        // Add active class to selected tab
        const activeButton = document.getElementById(activeTab + 'Button') || document.getElementById('tab' + activeTab.charAt(0).toUpperCase() + activeTab.slice(1));
        if (activeButton) {
            activeButton.classList.remove('text-slate-400', 'border-transparent');
            activeButton.classList.add('active');
            if (activeTab === 'Videos') {
                activeButton.classList.add('border-blue-500', 'text-blue-400');
            } else {
                activeButton.classList.add('border-green-500', 'text-green-400');
            }
        }
        
        // Show active content
        const activeContent = document.getElementById(activeTab.toLowerCase() + 'Content');
        if (activeContent) {
            activeContent.classList.remove('hidden');
        }
    }

    // Tab click events
    const tabVideos = document.getElementById('tabVideos');
    const tabPlayback = document.getElementById('tabPlayback');
    
    if (tabVideos) {
        tabVideos.addEventListener('click', () => switchTab('Videos'));
    }
    
    if (tabPlayback) {
        tabPlayback.addEventListener('click', () => switchTab('Playback'));
    }

    // Initialize first tab as active
    switchTab('Videos');

    // Mobile Menu
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');

    // Initialize mobile menu state on page load
    function initializeMobileMenu() {
        if (window.innerWidth < 768) {
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (mobileOverlay) mobileOverlay.classList.add('hidden');
        } else {
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (mobileOverlay) mobileOverlay.classList.add('hidden');
        }
    }

    // Call on page load
    initializeMobileMenu();

    if (mobileMenuBtn && sidebar && mobileOverlay) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.classList.toggle('hidden');
        });

        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    }

    // ========================================
    // UPLOAD MODAL - VERSIÓN CORREGIDA
    // ========================================
    
    // Buscar todos los posibles elementos del modal
    let uploadModal = document.getElementById('uploadModal');
    let openUploadBtn = document.getElementById('openUploadModal');
    let openUploadBtnAlt = document.getElementById('openUploadModalAlt');
    let closeUploadBtn = document.getElementById('closeModal');

    // Diagnóstico completo
    console.log('=== UPLOAD MODAL DIAGNOSTIC ===');
    console.log('Upload Modal:', uploadModal);
    console.log('Open Upload Btn:', openUploadBtn);
    console.log('Open Upload Btn Alt:', openUploadBtnAlt);
    console.log('Close Upload Btn:', closeUploadBtn);

    // Si no encuentra los elementos, buscarlos de otra manera
    if (!uploadModal) {
        uploadModal = document.querySelector('[id*="upload"]');
        console.log('Alternative upload modal search:', uploadModal);
    }

    if (!openUploadBtn) {
        openUploadBtn = document.querySelector('button[id*="upload"]');
        console.log('Alternative upload button search:', openUploadBtn);
    }

    // Función para mostrar modal de upload - MEJORADA
    function showUploadModal() {
        console.log('=== SHOWING UPLOAD MODAL ===');
        
        if (uploadModal) {
            // Remover todas las clases que pueden ocultar el modal
            uploadModal.classList.remove('hidden');
            uploadModal.style.display = 'flex';
            uploadModal.style.visibility = 'visible';
            uploadModal.style.opacity = '1';
            
            // Agregar z-index alto para asegurar que esté encima
            uploadModal.style.zIndex = '9999';
            
            console.log('Upload modal classes after show:', uploadModal.className);
            console.log('Upload modal style after show:', uploadModal.style.cssText);
            
            // Verificar si realmente está visible
            const computedStyle = window.getComputedStyle(uploadModal);
            console.log('Computed display:', computedStyle.display);
            console.log('Computed visibility:', computedStyle.visibility);
            
        } else {
            console.error('❌ Upload modal not found!');
            // Crear modal dinámicamente como fallback
            createUploadModalFallback();
        }
    }

    // Función para ocultar modal de upload
    function hideUploadModal() {
        console.log('=== HIDING UPLOAD MODAL ===');
        
        if (uploadModal) {
            uploadModal.classList.add('hidden');
            uploadModal.style.display = 'none';
            uploadModal.style.visibility = 'hidden';
            uploadModal.style.opacity = '0';
        }
    }

    // Función de fallback para crear el modal si no existe
    function createUploadModalFallback() {
        console.log('Creating upload modal fallback...');
        
        const modalHTML = `
        <div id="uploadModalFallback" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display: flex;">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl w-full max-w-md p-8 relative border border-slate-700/50 shadow-2xl">
                <button id="closeFallbackModal" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors" type="button">&times;</button>
                
                <div class="text-center mb-6">
                    <div class="mx-auto w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Subir Video</h2>
                    <p class="text-slate-400 text-sm">Selecciona un archivo de video para subir</p>
                </div>
                
                <form id="uploadFormFallback" enctype="multipart/form-data" class="space-y-6">
                    <div class="relative">
                        <input type="file" name="video" accept="video/*" required 
                               class="w-full p-4 rounded-xl border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                    </div>
                    
                    <div class="space-y-2">
                        <div class="w-full bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div id="progressBarFallback" class="bg-gradient-to-r from-blue-500 to-purple-500 h-full rounded-full w-0 transition-all duration-300"></div>
                        </div>
                        <p id="progressTextFallback" class="text-sm text-slate-400 text-center">0%</p>
                    </div>
                    
                    <button type="submit" 
                            class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                        Subir Video
                    </button>
                </form>
            </div>
        </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Configurar eventos del modal fallback
        const fallbackModal = document.getElementById('uploadModalFallback');
        const closeFallbackBtn = document.getElementById('closeFallbackModal');
        
        if (closeFallbackBtn) {
            closeFallbackBtn.addEventListener('click', () => {
                fallbackModal.remove();
            });
        }
        
        // Configurar formulario fallback
        setupUploadForm('uploadFormFallback', 'progressBarFallback', 'progressTextFallback');
    }

    // Event listeners para abrir modal de upload
    if (openUploadBtn) {
        console.log('✅ Setting up main upload button');
        openUploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔄 Main upload button clicked');
            showUploadModal();
        });
    } else {
        console.warn('⚠️ Main upload button not found, searching alternatives...');
        // Buscar por texto del botón
        const altBtn = Array.from(document.querySelectorAll('button')).find(btn => 
            btn.textContent.includes('Subir Video') || btn.textContent.includes('Subir')
        );
        if (altBtn) {
            console.log('✅ Found alternative upload button');
            altBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showUploadModal();
            });
        }
    }

    if (openUploadBtnAlt) {
        console.log('✅ Setting up alt upload button');
        openUploadBtnAlt.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔄 Alt upload button clicked');
            showUploadModal();
        });
    }

    // Event listener para cerrar modal de upload
    if (closeUploadBtn) {
        console.log('✅ Setting up close upload button');
        closeUploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔄 Close upload button clicked');
            hideUploadModal();
        });
    }

    // Cerrar modal al hacer clic fuera
    if (uploadModal) {
        uploadModal.addEventListener('click', function(e) {
            if (e.target === uploadModal) {
                console.log('🔄 Clicked outside upload modal');
                hideUploadModal();
            }
        });
    }

    // Función para configurar el formulario de upload
    function setupUploadForm(formId, progressBarId, progressTextId) {
        const uploadForm = document.getElementById(formId);
        const progressBar = document.getElementById(progressBarId);
        const progressText = document.getElementById(progressTextId);

        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('📤 Upload form submitted');
                
                const formData = new FormData(uploadForm);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'upload_video.php', true);

                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        if (progressBar) progressBar.style.width = percent + '%';
                        if (progressText) progressText.textContent = percent + '%';
                    }
                };

                xhr.onload = function() {
                    console.log('📤 Upload completed, status:', xhr.status);
                    if (xhr.status === 200) {
                        if (progressText) progressText.textContent = 'Subida completada!';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        if (progressText) progressText.textContent = 'Error al subir el video.';
                    }
                };

                xhr.onerror = function() {
                    console.error('📤 Upload error');
                    if (progressText) progressText.textContent = 'Error de conexión.';
                };

                xhr.send(formData);
            });
        }
    }

    // Configurar formulario principal
    setupUploadForm('uploadForm', 'progressBar', 'progressText');

    // ========================================
    // TV MODAL - VERSIÓN CORREGIDA
    // ========================================
    
    let tvModal = document.getElementById('tvModal');
    let closeTvModalBtn = document.getElementById('closeTvModal');
    let tvList = document.getElementById('tvList');

    console.log('=== TV MODAL DIAGNOSTIC ===');
    console.log('TV Modal:', tvModal);
    console.log('Close TV Modal Btn:', closeTvModalBtn);
    console.log('TV List:', tvList);

    // Función para mostrar TV modal - MEJORADA
    function showTvModal() {
        console.log('=== SHOWING TV MODAL ===');
        
        if (tvModal) {
            tvModal.classList.remove('hidden');
            tvModal.style.display = 'flex';
            tvModal.style.visibility = 'visible';
            tvModal.style.opacity = '1';
            tvModal.style.zIndex = '9999';
            
            console.log('TV modal should be visible now');
        } else {
            console.error('❌ TV modal not found!');
            createTvModalFallback();
        }
    }

    // Función para ocultar TV modal
    function hideTvModal() {
        console.log('=== HIDING TV MODAL ===');
        
        if (tvModal) {
            tvModal.classList.add('hidden');
            tvModal.style.display = 'none';
            tvModal.style.visibility = 'hidden';
            tvModal.style.opacity = '0';
        }
        
        // También ocultar modal fallback si existe
        const fallbackTvModal = document.getElementById('tvModalFallback');
        if (fallbackTvModal) {
            fallbackTvModal.remove();
        }
    }

    // Función de fallback para crear el TV modal si no existe
    function createTvModalFallback() {
        console.log('Creating TV modal fallback...');
        
        const modalHTML = `
        <div id="tvModalFallback" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display: flex;">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl w-full max-w-md p-8 relative border border-slate-700/50 shadow-2xl">
                <button id="closeTvModalFallback" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
                
                <div class="text-center mb-6">
                    <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-500 to-blue-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Seleccionar Canal</h2>
                    <p class="text-slate-400 text-sm">Elige el canal donde quieres asignar el video</p>
                </div>
                
                <div id="tvListFallback" class="space-y-3 max-h-64 overflow-y-auto">
                    <div class="text-center py-4 text-slate-400">Cargando canales...</div>
                </div>
            </div>
        </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Configurar eventos del modal fallback
        const fallbackTvModal = document.getElementById('tvModalFallback');
        const closeTvModalFallbackBtn = document.getElementById('closeTvModalFallback');
        
        if (closeTvModalFallbackBtn) {
            closeTvModalFallbackBtn.addEventListener('click', () => {
                fallbackTvModal.remove();
            });
        }
        
        // Actualizar referencia para usar el fallback
        tvModal = fallbackTvModal;
        tvList = document.getElementById('tvListFallback');
    }

    // Event listener para cerrar TV modal
    if (closeTvModalBtn) {
        console.log('✅ Setting up close TV modal button');
        closeTvModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔄 TV modal close button clicked');
            hideTvModal();
        });
    }

    // Cerrar TV modal al hacer clic fuera
    if (tvModal) {
        tvModal.addEventListener('click', function(e) {
            if (e.target === tvModal) {
                console.log('🔄 Clicked outside TV modal');
                hideTvModal();
            }
        });
    }

    // TV Modal buttons - MEJORADO CON RETRY LOGIC
    function setupTvModalButtons() {
        const tvModalButtons = document.querySelectorAll('.openTvModal');
        console.log('🔍 Found TV modal buttons:', tvModalButtons.length);

        tvModalButtons.forEach((button, index) => {
            console.log(`✅ Setting up TV modal button ${index}`);
            
            // Remover listeners anteriores si existen
            button.removeEventListener('click', button._tvModalHandler);
            
            // Crear nuevo handler
            button._tvModalHandler = function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(`🔄 TV modal button ${index} clicked`);
                
                try {
                    const videoDataAttr = button.dataset.video;
                    console.log('Raw video data attr:', videoDataAttr);
                    
                    if (!videoDataAttr) {
                        console.error('❌ No video data found in button');
                        alert('Error: No se encontraron datos del video');
                        return;
                    }
                    
                    const videoData = JSON.parse(videoDataAttr);
                    console.log('✅ Parsed video data:', videoData);
                    
                    // Mostrar modal (crear fallback si no existe)
                    if (!tvModal || !tvModal.parentNode) {
                        createTvModalFallback();
                    }
                    
                    showTvModal();
                    
                    // Actualizar contenido del listado
                    const currentTvList = document.getElementById('tvList') || document.getElementById('tvListFallback');
                    if (currentTvList) {
                        currentTvList.innerHTML = '<div class="text-center py-4 text-slate-400">Cargando canales...</div>';
                    }
                    
                    // Cargar canales
                    loadChannelsForVideo(videoData, currentTvList);
                    
                } catch (error) {
                    console.error('❌ Error processing video data:', error);
                    alert('Error al procesar datos del video: ' + error.message);
                }
            };
            
            // Agregar nuevo listener
            button.addEventListener('click', button._tvModalHandler);
        });
    }

    // Función para cargar canales
    function loadChannelsForVideo(videoData, targetList) {
        console.log('📡 Loading channels for video:', videoData);
        
        fetch('get_user_tvs.php')
            .then(res => {
                console.log('📡 Fetch response status:', res.status);
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(channels => {
                console.log('📡 Channels received:', channels);
                
                if (targetList) {
                    targetList.innerHTML = '';
                    
                    if (!channels || channels.length === 0) {
                        targetList.innerHTML = '<div class="text-center py-8 text-slate-400">No hay canales disponibles</div>';
                        return;
                    }

                    channels.forEach((tv, tvIndex) => {
                        console.log(`🔄 Creating channel element ${tvIndex}:`, tv);
                        
                        const div = document.createElement('div');
                        div.className = 'p-4 bg-gradient-to-r from-slate-700/50 to-slate-600/50 hover:from-blue-600/20 hover:to-purple-600/20 rounded-xl cursor-pointer transition-all duration-300 transform hover:scale-105 border border-slate-600/30 hover:border-blue-500/50';
                        div.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-white">Canal TV</div>
                                    <div class="text-sm text-slate-400">ID: ${tv.id}</div>
                                </div>
                            </div>
                        `;
                        
                        div.addEventListener('click', function() {
                            console.log('📺 Channel selected:', tv.id);
                            assignVideoToChannel(videoData, tv.id);
                        });
                        
                        targetList.appendChild(div);
                    });
                }
            })
            .catch(err => {
                console.error('📡 Fetch error:', err);
                if (targetList) {
                    targetList.innerHTML = `<div class="text-center py-8 text-red-400">Error al cargar canales: ${err.message}</div>`;
                }
            });
    }

    // Función para asignar video a canal
    function assignVideoToChannel(videoData, tvId) {
        console.log('📺 Assigning video to channel:', { videoData, tvId });
        
        fetch('assign_video_to_channel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                video: videoData,
                tvid: tvId
            })
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(resp => {
            console.log('📺 Assignment response:', resp);
            if (resp.success) {
                //alert('Video asignado correctamente al canal: ' + tvId);
                hideTvModal();
            } else {
                alert('Error: ' + (resp.message || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('📺 Assignment error:', err);
            alert('Error al asignar el video: ' + err.message);
        });
    }

    // Configurar botones inicialmente
    setupTvModalButtons();

    // Reconfigurar botones si el DOM cambia
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                // Esperar un poco para que el DOM se estabilice
                setTimeout(setupTvModalButtons, 100);
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Close mobile menu on window resize
    window.addEventListener('resize', () => {
        initializeMobileMenu();
    });

    // Auto-refresh playback content every 30 seconds when on that tab
    let autoRefreshInterval;

    function startAutoRefresh() {
        autoRefreshInterval = setInterval(() => {
            const playbackContent = document.getElementById('playbackContent');
            if (playbackContent && !playbackContent.classList.contains('hidden')) {
                console.log('🔄 Auto-refreshing playback data...');
                location.reload();
            }
        }, 30000); // 30 seconds
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    // Start auto-refresh on page load
    startAutoRefresh();

    // Stop auto-refresh when page is hidden/unfocused
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    });
    
    console.log('🎉 All JavaScript initialized successfully');
    
    // Test de funcionalidad - ejecutar después de 2 segundos
    setTimeout(() => {
        console.log('🧪 RUNNING FUNCTIONALITY TEST...');
        console.log('Upload modal exists:', !!document.getElementById('uploadModal'));
        console.log('TV modal exists:', !!document.getElementById('tvModal'));
        console.log('Upload buttons found:', document.querySelectorAll('[id*="upload"], button:contains("Subir")').length);
        console.log('TV modal buttons found:', document.querySelectorAll('.openTvModal').length);
        
        // Test click simulado (comentado para no interferir)
        // console.log('Running simulated click test...');
        // const testUploadBtn = document.getElementById('openUploadModal');
        // if (testUploadBtn) {
        //     testUploadBtn.click();
        //     setTimeout(() => {
        //         const modal = document.getElementById('uploadModal');
        //         if (modal && modal.style.display === 'flex') {
        //             console.log('✅ Upload modal test PASSED');
        //             hideUploadModal();
        //         } else {
        //             console.log('❌ Upload modal test FAILED');
        //         }
        //     }, 100);
        // }
    }, 2000);

    //////// PANTALLAS
     // Elementos del modal de pantallas
    const manageScreensModal = document.getElementById('manageScreensModal');
    const openManageScreensBtn = document.getElementById('openManageScreensModal');
    const closeManageScreensBtn = document.getElementById('closeManageScreensModal');
    const screensList = document.getElementById('screensList');
    const addScreenForm = document.getElementById('addScreenForm');

    // Función para mostrar el modal
    function showManageScreensModal() {
        if (manageScreensModal) {
            manageScreensModal.classList.remove('hidden');
            manageScreensModal.style.display = 'flex';
            loadUserScreens();
        }
    }

    // Función para ocultar el modal
    function hideManageScreensModal() {
        if (manageScreensModal) {
            manageScreensModal.classList.add('hidden');
            manageScreensModal.style.display = 'none';
            // Limpiar formulario
            if (addScreenForm) {
                addScreenForm.reset();
            }
        }
    }

    // Event listeners
    if (openManageScreensBtn) {
        openManageScreensBtn.addEventListener('click', showManageScreensModal);
    }

    if (closeManageScreensBtn) {
        closeManageScreensBtn.addEventListener('click', hideManageScreensModal);
    }

    // Cerrar modal al hacer clic fuera
    if (manageScreensModal) {
        manageScreensModal.addEventListener('click', function(e) {
            if (e.target === manageScreensModal) {
                hideManageScreensModal();
            }
        });
    }

    // Función para cargar las pantallas del usuario
    function loadUserScreens() {
        if (screensList) {
            screensList.innerHTML = '<div class="text-center py-4 text-slate-400">Cargando pantallas...</div>';
        }

        fetch('get_user_tvs.php')
            .then(res => res.json())
            .then(screens => {
                if (screensList) {
                    screensList.innerHTML = '';
                    
                    if (!screens || screens.length === 0) {
                        screensList.innerHTML = `
                            <div class="text-center py-8 text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <p>No tienes pantallas registradas</p>
                                <p class="text-sm mt-1">Crea tu primera pantalla usando el formulario de arriba</p>
                            </div>
                        `;
                        return;
                    }

                    screens.forEach((screen, index) => {
                        const screenElement = createScreenElement(screen, index);
                        screensList.appendChild(screenElement);
                    });
                }
            })
            .catch(err => {
                console.error('Error loading screens:', err);
                if (screensList) {
                    screensList.innerHTML = '<div class="text-center py-8 text-red-400">Error al cargar las pantallas</div>';
                }
            });
    }

    // Función para crear el elemento de pantalla CON botón de eliminar
    function createScreenElement(screen, index) {
        const div = document.createElement('div');
        div.className = 'bg-gradient-to-r from-slate-700/50 to-slate-600/50 rounded-xl p-4 border border-slate-600/30';
        
        div.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">Pantalla ${index + 1}</h3>
                        <p class="text-slate-400 text-sm">ID: <span class="font-mono text-green-300">${screen.id}</span></p>
                        <div class="flex items-center space-x-2 mt-1">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-xs text-green-300">Disponible</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Botón de copiar ID -->
                    <button class="copy-id-btn px-3 py-2 bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/30 rounded-lg transition-all duration-200 text-sm text-blue-300" 
                            data-screen-id="${screen.id}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    
                    <!-- Botón de eliminar -->
                    <button class="delete-screen-btn px-3 py-2 bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 rounded-lg transition-all duration-200 text-sm text-red-300" 
                            data-screen-id="${screen.id}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        // Agregar event listeners
        const copyBtn = div.querySelector('.copy-id-btn');
        const deleteBtn = div.querySelector('.delete-screen-btn');

        if (copyBtn) {
            copyBtn.addEventListener('click', () => copyScreenId(screen.id));
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => deleteScreen(screen.id));
        }

        return div;
    }

    // Función para copiar ID de pantalla
    function copyScreenId(screenId) {
        navigator.clipboard.writeText(screenId).then(() => {
            // Crear notificación temporal
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            notification.textContent = 'ID copiado al portapapeles';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 2000);
        }).catch(() => {
            alert('ID de pantalla: ' + screenId);
        });
    }

        // Función para eliminar pantalla
    function deleteScreen(screenId) {
        if (!confirm('¿Estás seguro de que quieres eliminar esta pantalla? Esta acción no se puede deshacer.')) {
            return;
        }

        fetch('delete_screen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ screen_id: screenId })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                // Mostrar notificación de éxito
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                notification.textContent = 'Pantalla eliminada correctamente';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
                
                // Recargar la lista de pantallas
                loadUserScreens();
            } else {
                alert('Error al eliminar la pantalla: ' + (response.message || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            alert('Error al eliminar la pantalla');
        });
    }
        
    // Manejar envío del formulario
    if (addScreenForm) {
        addScreenForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(addScreenForm);
            const screenName = formData.get('screenName') || 'Pantalla sin nombre';
            const screenLocation = formData.get('screenLocation') || '';

            // Mostrar estado de carga
            const submitBtn = addScreenForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="flex items-center justify-center space-x-2"><svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><span>Creando...</span></span>';
            submitBtn.disabled = true;

            fetch('add_screen.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    screen_name: screenName,
                    screen_location: screenLocation 
                })
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    // Mostrar éxito
                    const successNotification = document.createElement('div');
                    successNotification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    successNotification.textContent = 'Pantalla creada correctamente';
                    document.body.appendChild(successNotification);
                    
                    setTimeout(() => {
                        successNotification.remove();
                    }, 3000);
                    
                    // Limpiar formulario y recargar lista
                    addScreenForm.reset();
                    loadUserScreens();
                } else {
                    alert('Error al crear la pantalla: ' + (response.message || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error('Add screen error:', err);
                alert('Error al crear la pantalla');
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }


    // ========================================
// IMAGE MODAL
// ========================================

const imageModal = document.getElementById('imageModal');
const openImageBtn = document.getElementById('openImageModal');
const closeImageBtn = document.getElementById('closeImageModal');

console.log('=== IMAGE MODAL DIAGNOSTIC ===');
console.log('Image Modal:', imageModal);
console.log('Open Image Btn:', openImageBtn);
console.log('Close Image Btn:', closeImageBtn);

// Función para mostrar modal de imagen
function showImageModal() {
    console.log('=== SHOWING IMAGE MODAL ===');
    
    if (imageModal) {
        imageModal.classList.remove('hidden');
        imageModal.style.display = 'flex';
        imageModal.style.visibility = 'visible';
        imageModal.style.opacity = '1';
        imageModal.style.zIndex = '9999';
        
        console.log('Image modal should be visible now');
    } else {
        console.error('Image modal not found!');
    }
}

// Función para ocultar modal de imagen
function hideImageModal() {
    console.log('=== HIDING IMAGE MODAL ===');
    
    if (imageModal) {
        imageModal.classList.add('hidden');
        imageModal.style.display = 'none';
        imageModal.style.visibility = 'hidden';
        imageModal.style.opacity = '0';
    }
}

// Event listeners para modal de imagen
if (openImageBtn) {
    console.log('Setting up image upload button');
    openImageBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Image upload button clicked');
        showImageModal();
    });
}

if (closeImageBtn) {
    console.log('Setting up close image button');
    closeImageBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Close image button clicked');
        hideImageModal();
    });
}

// Cerrar modal al hacer clic fuera
if (imageModal) {
    imageModal.addEventListener('click', function(e) {
        if (e.target === imageModal) {
            console.log('Clicked outside image modal');
            hideImageModal();
        }
    });
}

// Configurar formulario de imagen con progreso
const imageUploadForm = document.getElementById('imageUploadForm');
const imageProgressBar = document.getElementById('imageProgressBar');
const imageProgressText = document.getElementById('imageProgressText');

if (imageUploadForm) {
    imageUploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Image form submitted');
        
        const formData = new FormData(imageUploadForm);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload_video.php', true);

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                if (imageProgressBar) imageProgressBar.style.width = percent + '%';
                if (imageProgressText) imageProgressText.textContent = percent + '%';
            }
        };

        xhr.onload = function() {
            console.log('Image upload completed, status:', xhr.status);
            if (xhr.status === 200) {
                if (imageProgressText) imageProgressText.textContent = 'Conversión completada!';
                setTimeout(() => location.reload(), 1500);
            } else {
                if (imageProgressText) imageProgressText.textContent = 'Error al procesar la imagen.';
            }
        };

        xhr.onerror = function() {
            console.error('Image upload error');
            if (imageProgressText) imageProgressText.textContent = 'Error de conexión.';
        };

        xhr.send(formData);
    });
}
});