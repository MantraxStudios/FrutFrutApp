// Variables globales
let editingElement = null;

// Función para mostrar notificaciones
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Función para realizar peticiones AJAX
async function ajaxRequest(data) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        });
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return { success: false, message: 'Error de conexión' };
    }
}

// Expandir/contraer usuarios
function toggleUserDetails(userId) {
    const card = document.querySelector(`[data-user-id="${userId}"]`);
    const details = card.querySelector('.user-details');
    const icon = card.querySelector('.expand-icon');
    
    details.classList.toggle('hidden');
    icon.style.transform = details.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(90deg)';
}

function expandAllUsers() {
    document.querySelectorAll('.user-details').forEach(detail => detail.classList.remove('hidden'));
    document.querySelectorAll('.expand-icon').forEach(icon => icon.style.transform = 'rotate(90deg)');
}

function collapseAllUsers() {
    document.querySelectorAll('.user-details').forEach(detail => detail.classList.add('hidden'));
    document.querySelectorAll('.expand-icon').forEach(icon => icon.style.transform = 'rotate(0deg)');
}

// Edición inline
document.addEventListener('DOMContentLoaded', function() {
    // Hacer editables los campos
    document.querySelectorAll('.editable').forEach(element => {
        element.addEventListener('click', function() {
            if (editingElement) return;
            startEditing(this);
        });
    });
    
    // Cambio de rol
    document.querySelectorAll('.role-select').forEach(select => {
        select.addEventListener('change', async function() {
            const userId = this.dataset.userId;
            const newRole = this.value;
            const originalRole = this.dataset.original;
            
            const result = await ajaxRequest({
                action: 'update_user',
                id: userId,
                campo: 'user_role',
                valor: newRole
            });
            
            if (result.success) {
                this.dataset.original = newRole;
                showNotification('Rol actualizado correctamente');
                
                // Actualizar la etiqueta visual
                const card = this.closest('.user-card');
                const roleSpan = card.querySelector('.bg-purple-200, .bg-green-200');
                roleSpan.textContent = newRole === 'admin' ? 'Admin' : 'Usuario';
                roleSpan.className = roleSpan.className.replace(
                    /(bg-purple-200|bg-green-200|dark:bg-purple-800|dark:bg-green-800)/g, 
                    newRole === 'admin' ? 'bg-purple-200 dark:bg-purple-800' : 'bg-green-200 dark:bg-green-800'
                );
            } else {
                this.value = originalRole;
                showNotification(result.message || 'Error al cambiar el rol', 'error');
            }
        });
    });
    
    // Filtro de videos
    document.getElementById('filter-user').addEventListener('change', function() {
        const selectedUser = this.value;
        const videoCards = document.querySelectorAll('.video-card');
        
        videoCards.forEach(card => {
            const cardUser = card.dataset.user;
            card.style.display = selectedUser === '' || cardUser === selectedUser ? 'block' : 'none';
        });
    });
});

function startEditing(element) {
    if (editingElement) return;
    
    editingElement = element;
    const originalValue = element.textContent.trim();
    const field = element.dataset.field;
    
    element.classList.add('editing');
    
    let input;
    if (field === 'email') {
        input = document.createElement('input');
        input.type = 'email';
    } else {
        input = document.createElement('input');
        input.type = 'text';
    }
    
    input.value = originalValue;
    input.className = 'w-full bg-transparent border-none outline-none';
    
    element.innerHTML = '';
    element.appendChild(input);
    input.focus();
    input.select();
    
    async function saveEdit() {
        const newValue = input.value.trim();
        if (newValue === originalValue) {
            cancelEdit();
            return;
        }
        
        const result = await ajaxRequest({
            action: 'update_user',
            id: element.dataset.userId,
            campo: field,
            valor: newValue
        });
        
        if (result.success) {
            element.textContent = newValue;
            element.classList.remove('editing');
            editingElement = null;
            showNotification('Campo actualizado correctamente');
        } else {
            showNotification(result.message || 'Error al actualizar', 'error');
            cancelEdit();
        }
    }
    
    function cancelEdit() {
        element.textContent = originalValue;
        element.classList.remove('editing');
        editingElement = null;
    }
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveEdit();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEdit();
        }
    });
    
    input.addEventListener('blur', saveEdit);
}

// Cambiar contraseña
async function changePassword(userId) {
    const newPassword = prompt('Ingresa la nueva contraseña (mínimo 6 caracteres):');
    if (!newPassword) return;
    
    if (newPassword.length < 6) {
        showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }
    
    const result = await ajaxRequest({
        action: 'change_password',
        id: userId,
        password: newPassword
    });
    
    if (result.success) {
        showNotification('Contraseña cambiada correctamente');
    } else {
        showNotification(result.message || 'Error al cambiar contraseña', 'error');
    }
}

// Eliminar usuario
async function deleteUser(userId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este usuario y todos sus videos?')) return;
    
    const result = await ajaxRequest({
        action: 'delete_user',
        id: userId
    });
    
    if (result.success) {
        document.querySelector(`[data-user-id="${userId}"]`).remove();
        document.querySelectorAll(`[data-video-id]`).forEach(card => {
            // Eliminar videos del usuario también de la vista
            const videoUserId = card.closest('[data-user]')?.dataset.user;
            if (videoUserId === userId) card.remove();
        });
        showNotification('Usuario eliminado correctamente');
    } else {
        showNotification(result.message || 'Error al eliminar usuario', 'error');
    }
}

// Eliminar videos de un usuario
async function deleteUserVideos(userId) {
    if (!confirm('¿Eliminar todos los videos de este usuario?')) return;
    
    // Obtener todos los videos del usuario
    const userVideos = document.querySelectorAll(`.video-card`);
    const userName = document.querySelector(`[data-user-id="${userId}"] h4`).textContent;
    
    let deletedCount = 0;
    for (let videoCard of userVideos) {
        if (videoCard.dataset.user === userName) {
            const videoId = videoCard.dataset.videoId;
            const result = await ajaxRequest({
                action: 'delete_video',
                id: videoId
            });
            
            if (result.success) {
                videoCard.remove();
                deletedCount++;
            }
        }
    }
    
    // Actualizar contador
    document.getElementById(`video-count-${userId}`).textContent = '0';
    showNotification(`${deletedCount} videos eliminados`);
}

// Eliminar video individual
async function deleteVideo(videoId) {
    if (!confirm('¿Eliminar este video?')) return;
    
    const result = await ajaxRequest({
        action: 'delete_video',
        id: videoId
    });
    
    if (result.success) {
        const videoCard = document.querySelector(`[data-video-id="${videoId}"]`);
        videoCard.remove();
        showNotification('Video eliminado correctamente');
    } else {
        showNotification(result.message || 'Error al eliminar video', 'error');
    }
}

// Descargar video
function downloadVideo(videoUrl) {
    const link = document.createElement('a');
    link.href = videoUrl;
    link.download = videoUrl.split('/').pop();
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}