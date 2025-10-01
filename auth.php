<!doctype html>
<html lang="es" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FrutGlifoTV — Login / Registro</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .focus-ring:focus { outline: none; box-shadow: 0 0 0 3px rgba(168,85,247,0.4); border-color: rgba(168,85,247,1); }
  </style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-6 text-gray-100">
  <main class="w-full max-w-2xl">
    <section class="bg-gray-800 shadow-xl rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-gray-700">
      <!-- Left: Branding -->
      <div class="hidden md:flex flex-col justify-center items-start gap-6 p-10 bg-gradient-to-b from-purple-700 to-indigo-600 text-white">
        <h1 class="text-3xl font-semibold">FrutGlifoTV</h1>
        <p class="text-sm opacity-90">Inicia sesión o crea tu cuenta para acceder al panel. Rápido, seguro y adaptado al modo oscuro.</p>
        <ul class="text-sm space-y-2 opacity-95">
        <li>• Acceso rápido a tus videos favoritos</li>
        <li>• Navegación sencilla desde tu TV</li>
        <li>• Reproducción optimizada para pantalla grande</li>
        </ul>
        <div class="mt-auto text-xs opacity-80">¿Necesitas ayuda? soporte@frutglifotv.com</div>
      </div>

      <!-- Right: Forms -->
      <div class="p-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-medium">Acceso</h2>
          <div class="text-sm text-gray-400">Usa tu correo para ingresar</div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 bg-gray-700 rounded-lg inline-flex p-1 gap-1" role="tablist" aria-label="Login or Register">
          <button id="tab-login" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-900 text-white shadow-sm" role="tab" aria-selected="true">Iniciar sesión</button>
          <button id="tab-register" class="px-4 py-2 rounded-md text-sm font-medium text-gray-300" role="tab" aria-selected="false">Registrarse</button>
        </div>

        <!-- Forms container -->
        <div id="forms">
          <!-- Login form -->
          <form id="form-login" class="space-y-4" novalidate>
            <div>
              <label for="login-email" class="block text-sm font-medium">Correo</label>
              <input id="login-email" name="email" type="email" required class="mt-1 block w-full rounded-md border-gray-700 bg-gray-900 text-gray-100 shadow-sm focus-ring p-2">
            </div>
            <div>
              <label for="login-password" class="block text-sm font-medium">Contraseña</label>
              <div class="relative">
                <input id="login-password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-700 bg-gray-900 text-gray-100 shadow-sm focus-ring p-2 pr-10">
                <button type="button" id="login-toggle-pwd" class="absolute right-2 top-2 text-sm opacity-70 text-purple-400 hover:text-purple-300">Mostrar</button>
              </div>
            </div>
            <button type="submit" class="w-full py-2 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">Iniciar sesión</button>
            <div id="login-feedback" class="text-sm mt-2 hidden"></div>
          </form>

          <!-- Register form -->
          <form id="form-register" class="space-y-4 hidden" novalidate>
            <div>
              <label for="reg-name" class="block text-sm font-medium">Nombre</label>
              <input id="reg-name" name="name" type="text" required class="mt-1 block w-full rounded-md border-gray-700 bg-gray-900 text-gray-100 shadow-sm focus-ring p-2" placeholder="Tu nombre">
              <p class="mt-1 text-xs text-red-500 hidden" id="reg-name-error">El nombre es obligatorio.</p>
            </div>
            <div>
              <label for="reg-email" class="block text-sm font-medium">Correo</label>
              <input id="reg-email" name="email" type="email" required class="mt-1 block w-full rounded-md border-gray-700 bg-gray-900 text-gray-100 shadow-sm focus-ring p-2" placeholder="tucorreo@ejemplo.com">
              <p class="mt-1 text-xs text-red-500 hidden" id="reg-email-error">Introduce un correo válido.</p>
            </div>
            <div>
              <label for="reg-password" class="block text-sm font-medium">Contraseña</label>
              <div class="relative">
                <input id="reg-password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-700 bg-gray-900 text-gray-100 shadow-sm focus-ring p-2 pr-10" placeholder="Mínimo 8 caracteres">
                <button type="button" class="absolute right-2 top-2 text-sm opacity-70 text-purple-400 hover:text-purple-300" id="reg-toggle-pwd">Mostrar</button>
              </div>
              <p class="mt-1 text-xs text-gray-400">Usa al menos 8 caracteres. Incluye números y letras.</p>
              <p class="mt-1 text-xs text-red-500 hidden" id="reg-password-error">La contraseña debe tener al menos 8 caracteres.</p>
            </div>
            <div>
              <label for="reg-password2" class="block text-sm font-medium">Confirmar contraseña</label>
              <input id="reg-password2" name="password2" type="password" required class="mt-1 block w-full rounded-md border-gray-700 bg-gray-900 text-gray-100 shadow-sm focus-ring p-2" placeholder="Repite la contraseña">
              <p class="mt-1 text-xs text-red-500 hidden" id="reg-password2-error">Las contraseñas no coinciden.</p>
            </div>
            <div>
              <button type="submit" class="w-full py-2 rounded-md bg-green-600 text-white font-medium hover:bg-green-700">Crear cuenta</button>
            </div>
            <div id="register-feedback" class="text-sm mt-2 hidden"></div>
          </form>
        </div>

        <hr class="my-6 border-gray-700">
        <p class="text-xs text-center text-gray-500">Al registrarte aceptas los <a href="#" class="underline">Términos</a> y la <a href="#" class="underline">Política de Privacidad</a>.</p>
      </div>
    </section>
  </main>

  <script>
    // --- Tab switching ---
    const tabLogin = document.getElementById('tab-login');
    const tabRegister = document.getElementById('tab-register');
    const formLogin = document.getElementById('form-login');
    const formRegister = document.getElementById('form-register');

    function showLogin() {
      tabLogin.classList.add('bg-gray-900','text-white'); 
      tabRegister.classList.remove('bg-gray-900','text-white');
      formLogin.classList.remove('hidden'); 
      formRegister.classList.add('hidden');
    }
    function showRegister() {
      tabRegister.classList.add('bg-gray-900','text-white'); 
      tabLogin.classList.remove('bg-gray-900','text-white');
      formRegister.classList.remove('hidden'); 
      formLogin.classList.add('hidden');
    }

    tabLogin.addEventListener('click', showLogin);
    tabRegister.addEventListener('click', showRegister);

    // --- Password toggles ---
    document.getElementById('login-toggle-pwd').addEventListener('click', () => {
      const i = document.getElementById('login-password');
      i.type = i.type === 'password' ? 'text' : 'password';
    });
    document.getElementById('reg-toggle-pwd').addEventListener('click', () => {
      const i = document.getElementById('reg-password');
      i.type = i.type === 'password' ? 'text' : 'password';
    });

    // --- Form login ---
    formLogin.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('login-email').value.trim();
      const pwd = document.getElementById('login-password').value;
      const feedback = document.getElementById('login-feedback');

      feedback.classList.remove('hidden','text-red-600','text-green-600');
      feedback.textContent = 'Enviando...';

      try {
        const res = await fetch('login.php', {
          method: 'POST',
          body: new URLSearchParams({ email, password: pwd })
        });
        const data = await res.json();

        if (data.success) {
          feedback.classList.add('text-green-600');
          feedback.textContent = data.message + " Bienvenido " + data.name;
          setTimeout(() => { window.location.href = data.redirect; }, 1000);
        } else {
          feedback.classList.add('text-red-600');
          feedback.textContent = data.message;
        }
      } catch {
        feedback.classList.add('text-red-600');
        feedback.textContent = 'Error de red o servidor';
      }
    });

    // --- Form register ---
    formRegister.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('reg-name').value.trim();
      const email = document.getElementById('reg-email').value.trim();
      const pwd = document.getElementById('reg-password').value;
      const pwd2 = document.getElementById('reg-password2').value;

      const feedback = document.getElementById('register-feedback');
      feedback.classList.remove('hidden','text-red-600','text-green-600');
      feedback.textContent = 'Enviando...';

      if (pwd.length < 8) {
        feedback.classList.add('text-red-600');
        feedback.textContent = 'La contraseña debe tener mínimo 8 caracteres';
        return;
      }
      if (pwd !== pwd2) {
        feedback.classList.add('text-red-600');
        feedback.textContent = 'Las contraseñas no coinciden';
        return;
      }

      try {
        const res = await fetch('register.php', {
          method: 'POST',
          body: new URLSearchParams({ name, email, password: pwd })
        });
        const data = await res.json();

        if (data.success) {
          feedback.classList.add('text-green-600');
          feedback.textContent = data.message;
          formRegister.reset();
          setTimeout(() => tabLogin.click(), 1000);
        } else {
          feedback.classList.add('text-red-600');
          feedback.textContent = data.message;
        }
      } catch {
        feedback.classList.add('text-red-600');
        feedback.textContent = 'Error de red o servidor';
      }
    });

    // Accessibility: switch with keyboard
    tabLogin.addEventListener('keydown', (e)=>{ if(e.key==='ArrowRight') tabRegister.focus(); });
    tabRegister.addEventListener('keydown', (e)=>{ if(e.key==='ArrowLeft') tabLogin.focus(); });
  </script>
</body>
</html>
