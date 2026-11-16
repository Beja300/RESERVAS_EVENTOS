<?php require_once __DIR__ . '/../_helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservas de Eventos · Encuentra el lugar ideal</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
  <link rel="stylesheet" href="<?= e(css_url('auth/login')) ?>">
</head>
<body class="auth-page">
  <header class="topbar auth-topbar">
    <div class="container">
      <a class="brand" href="<?= e(base_url('venue', 'catalog')) ?>">
        Reservas de Eventos
      </a>
      <nav class="nav" aria-label="Navegación principal">
        <a href="<?= e(base_url('venue', 'catalog')) ?>">Explorar espacios</a>
        <a class="btn btn-sm btn-primary" href="#login-form" data-show-login>
  Iniciar sesión
</a>
      </nav>
    </div>
  </header>

  <div class="clean-action">
    <form method="post" action="<?= e(base_url('auth', 'cleanDemo')) ?>"
          onsubmit="return confirm('¿Limpiar todas las tablas de la base de datos?');">
      <?= csrf_field() ?>
      <button class="btn btn-secondary" type="submit">Clean</button>
    </form>
  </div>

  <main class="auth-main" id="auth-main">
    <div id="welcome-content">
      <section class="auth-hero" aria-labelledby="hero-title">
        <div class="hero-content">
          <span class="hero-eyebrow">Lugares y servicios para celebrar</span>
          <h1 id="hero-title">Ocasiones especiales requieren sitios especiales.</h1>
          <p>
            Céntrate en celebrar; nosotros te ayudamos a encontrar el lugar
            y los servicios ideales para hacerlo realidad.
          </p>
        </div>
      </section>

      <section class="pathways" aria-label="Elige cómo quieres comenzar">
        <article class="path-card">
          <span class="path-kicker">Para quienes celebran</span>
          <h2>Encuentra tu próximo lugar favorito</h2>
          <p>
            Explora espacios y servicios, compara opciones y reserva el escenario
            perfecto para tu ocasión.
          </p>
          <a class="btn btn-primary"
             href="<?= e(base_url('auth', 'showRegister')) ?>&tipo=cliente">
            Crear cuenta cliente <span aria-hidden="true">→</span>
          </a>
        </article>

        <article class="path-card path-card-owner">
          <span class="path-kicker">Para negocios y anfitriones</span>
          <h2>Haz crecer tu negocio</h2>
          <p>
            Publica tus espacios y servicios, conecta con nuevos clientes
            y recibe más oportunidades.
          </p>
          <a class="btn btn-outline"
             href="<?= e(base_url('auth', 'showRegister')) ?>&tipo=propietario">
            Unirme como propietario <span aria-hidden="true">→</span>
          </a>
        </article>
      </section>
      <div class="login-prompt">
  <span>¿Ya tienes cuenta?</span>
  <a href="#login-form" data-show-login>
    Iniciar sesión <span aria-hidden="true">→</span>
  </a>
</div>
    </div>

    <section class="login-section" id="login-form"
             aria-labelledby="login-title" hidden>
      <div class="auth-card container-narrow">
        <h2 id="login-title">Inicia sesión</h2>
        <p class="subtitle">
          Accede para gestionar tus reservas, espacios o servicios.
        </p>

        <?php if (empty($hasAdmins)): ?>
          <div class="alert alert-error">
            No hay administradores en el sistema.
            <a href="<?= e(base_url('auth', 'showSetup')) ?>">
              Crea el primero aquí
            </a>.
          </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
          <div class="alert alert-success">
            Datos de prueba restaurados correctamente.
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-error"><?= e($_GET['error']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('auth', 'login')) ?>">
          <?= csrf_field() ?>

          <div class="form-group">
            <label for="email">
              Correo electrónico <span class="required-mark">*</span>
            </label>
            <input
              class="form-control"
              type="email"
              id="email"
              name="email"
              value="<?= e($_POST['email'] ?? '') ?>"
              required
              autofocus
            >
            <div class="form-hint">Ej: analucia@correo.com</div>
          </div>

          <div class="form-group">
            <label for="password">
              Contraseña <span class="required-mark">*</span>
            </label>
            <div class="password-wrapper">
              <input
                class="form-control"
                type="password"
                id="password"
                name="password"
                required
              >
              <button
                class="password-toggle"
                type="button"
                id="passwordToggle"
                aria-label="Mostrar contraseña"
              >
                Mostrar
              </button>
            </div>
            <div class="form-hint">Ej: ClaveSegura7</div>
          </div>

          <button class="btn btn-primary btn-block" type="submit">
            Entrar
          </button>
        </form>

        <div class="auth-footer">
          ¿No tienes cuenta?
          <a href="<?= e(base_url('auth', 'showRegister')) ?>">
            Regístrate aquí
          </a>
        </div>
      </div>
    </section>
  </main>

  <script src="<?= e(js_url('auth/login')) ?>"></script>
  <script>
  (function () {
    var showLoginLinks = document.querySelectorAll('[data-show-login]');
    var welcomeContent = document.getElementById('welcome-content');
    var loginSection = document.getElementById('login-form');

    if (!welcomeContent || !loginSection) {
      return;
    }

    showLoginLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();

        welcomeContent.hidden = true;
        loginSection.hidden = false;

        loginSection.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });

        var emailInput = document.getElementById('email');
        if (emailInput) {
          emailInput.focus({ preventScroll: true });
        }
      });
    });
  })();
</script>
</body>
</html>
