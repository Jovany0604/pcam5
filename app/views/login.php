<?php

/**
 * Variables proporcionadas por el controlador.
 *
 * @var SimpleXMLElement $config
 * @var string|null $error
 * @var string $contenedor_privado
 */


/* =============================================================================
   app/views/login.php  -  VISTA del inicio de sesión
   =============================================================================
   La muestra public/index.php cuando el usuario no entró por IP.
   Variables disponibles: $config, $error, $contenedor_privado
   Formulario  -> controller=login&action=loginUser  (POST username, password)
   Invitado    -> controller=login&action=nologin
   ============================================================================= */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config->pagina->titulo_pestana) ?> - Inicio de sesión</title>
    <link rel="stylesheet" href="<?= URL_BASE ?>css/estilos.css">
</head>
<body>

<?php require VIEW_PATH . '/header.php'; ?>

<main class="login-container" style="background-image: url('<?= URL_BASE . e($config->imagenes->imagen_fondo) ?>')">
    <div class="login-box">

        <!-- Columna izquierda: imagen del usuario CFE -->
        <div class="login-izquierda">
            <img class="logo" src="<?= URL_BASE . e($config->imagenes->imagen_login) ?>" alt="Usuario CFE">
        </div>

        <!-- Columna derecha: formulario -->
        <div class="login-derecha">
            <h1>Inicio de sesión</h1>

            <?php if (!empty($error)): ?>
                <div class="mensaje-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= ruta('login', 'loginUser') ?>">
                <div class="campo">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" placeholder="Ingresa tu usuario"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>

                <div class="campo">
                    <label for="password">Contraseña:</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                        <!-- Muestra/oculta la contraseña (función verPassword en app.js) -->
                        <button type="button" class="ver-password" onclick="verPassword()">Ver</button>
                    </div>
                </div>

                <div class="botones-login">
                    <?php if ($contenedor_privado == '0'): ?>
                        <!-- Botón amarillo: solo si el contenedor NO es privado -->
                        <a class="boton btn-invitado" href="<?= ruta('login', 'nologin') ?>">Entrar como invitado</a>
                    <?php endif; ?>
                    <button type="submit" class="boton btn-entrar">Entrar</button>
                </div>
            </form>

            <!-- Ayuda para registrar el equipo: el administrador guarda esta IP en la base de datos -->
            <p class="ip-detectada">IP de este equipo: <?= e($_SERVER['REMOTE_ADDR'] ?? '') ?></p>
        </div>

    </div>
</main>

<script src="<?= URL_BASE ?>js/app.js"></script>
</body>
</html>
