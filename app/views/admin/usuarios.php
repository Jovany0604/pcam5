<?php
/* =============================================================================
   app/views/admin/usuarios.php  -  Contenido de la VENTANA FLOTANTE de Usuarios
   =============================================================================
   NO es una página: AdminController::usuarios() la devuelve y app.js la mete
   dentro de #usuarios-contenido (ventana flotante de inicio.php).
   Variables: $usuarios (lista), $editar (usuario del formulario; id 0 = nuevo),
              $csrf (token), $roles, $usuario (el admin con sesión)
   Formulario   -> app.js guardarUsuario()  -> admin&action=guardarUsuario (POST, JSON)
   Botón Borrar -> app.js borrarUsuario()   -> admin&action=borrarUsuario  (POST, JSON)
   Botón Editar -> app.js cargarUsuarios(N) -> admin&action=usuarios&id=N  (llena el formulario)
   ============================================================================= */
$nuevo = ($editar['id'] == 0);
?>
<div class="admin-grid">

    <!-- ---------- Formulario: nuevo / editar ---------- -->
    <form class="formulario" onsubmit="guardarUsuario(event)" autocomplete="off">
        <h3><?= $nuevo ? 'Nuevo usuario' : 'Editar usuario' ?></h3>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int) $editar['id'] ?>">

        <label>Nombre completo
            <input type="text" name="nombre" value="<?= e($editar['nombre']) ?>" required>
        </label>
        <label>Usuario
            <input type="text" name="username" value="<?= e($editar['username']) ?>" required>
        </label>
        <label>Contraseña <?= $nuevo ? '' : '<small>(vacía = no cambiar)</small>' ?>
            <input type="password" name="password" minlength="6" autocomplete="new-password" <?= $nuevo ? 'required' : '' ?>>
        </label>
        <label>Correo
            <input type="email" name="email" value="<?= e($editar['email']) ?>">
        </label>
        <label>IP del equipo <small>(entra sin login desde esa IP)</small>
            <input type="text" name="ip" value="<?= e($editar['ip']) ?>" placeholder="10.26.5.120">
        </label>
        <label>Rol
            <select name="rol">
                <option value="2" <?= $editar['rol'] == 2 ? 'selected' : '' ?>>Usuario (solo reporte)</option>
                <option value="1" <?= $editar['rol'] == 1 ? 'selected' : '' ?>>Administrador</option>
            </select>
        </label>

        <div class="modal-botones">
            <?php if (!$nuevo): ?>
                <button type="button" class="boton gris" onclick="cargarUsuarios(0)">Cancelar</button>
            <?php endif; ?>
            <button type="submit" class="boton btn-entrar"><?= $nuevo ? 'Crear' : 'Guardar' ?></button>
        </div>
    </form>

    <!-- ---------- Lista de usuarios ---------- -->
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>IP</th><th>Rol</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u['nombre']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= $u['ip'] !== '' ? e($u['ip']) : '—' ?></td>
                    <td><?= e($roles[$u['rol']] ?? '') ?></td>
                    <td class="acciones">
                        <button type="button" class="boton-chico" onclick="cargarUsuarios(<?= (int) $u['id'] ?>)">Editar</button>
                        <?php if ($u['id'] != $usuario['id']): // no se puede borrar a sí mismo ?>
                            <button type="button" class="boton-chico rojo"
                                    data-id="<?= (int) $u['id'] ?>" data-nombre="<?= e($u['username']) ?>"
                                    onclick="borrarUsuario(this)">Borrar</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
