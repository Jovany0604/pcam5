/* =============================================================================
   public/js/app.js  -  ÚNICO archivo JavaScript del proyecto (sin librerías)
   =============================================================================
   Login:   verPassword()
   Barra:   descargarDatos(), abrirConfiguracion(), cerrarConfiguracion()
   Datos:   filtrarSubestacion(), limpiarFiltro()   (buscador de subestación)
   Usuarios (ventana flotante, solo admin):
            abrirUsuarios(), cerrarUsuarios(), cargarUsuarios(id),
            guardarUsuario(evento), borrarUsuario(boton)
   Las funciones que no aplican a la página actual simplemente no se usan.
   ============================================================================= */

/* ---------------------------------------------------------------- LOGIN */

/** Muestra u oculta la contraseña (botón "Ver" del login). */
function verPassword() {
    const campo = document.getElementById('password');
    const boton = document.querySelector('.ver-password');
    const oculto = campo.type === 'password';
    campo.type = oculto ? 'text' : 'password';
    boton.textContent = oculto ? 'Ocultar' : 'Ver';
}

/* ---------------------------------------------------------------- BARRA */

/** Escapa una celda para formar un archivo CSV válido. */
function csvCelda(texto) {
    return '"' + String(texto || '').replace(/"/g, '""').replace(/\r?\n/g, ' ') + '"';
}

/**
 * Descarga como CSV las filas visibles de la tabla actual.
 * Respeta el filtro de subestación y funciona para Status y Analógicas.
 */
function descargarDatos() {
    const tabla = document.querySelector('#tabla-datos')?.closest('table');
    if (!tabla) {
        window.alert('Abre Datos para descargar la tabla actual.');
        return;
    }

    const filas = Array.from(tabla.querySelectorAll('tr')).filter(function (fila) {
        return !fila.hidden && fila.id !== 'sin-resultados';
    });

    const contenido = filas.map(function (fila) {
        return Array.from(fila.cells).map(function (celda) {
            return csvCelda(celda.textContent.trim());
        }).join(',');
    }).join('\r\n');

    const blob = new Blob(['\uFEFF' + contenido], { type: 'text/csv;charset=utf-8;' });
    const enlace = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const fecha = new Date().toISOString().slice(0, 10);

    enlace.href = url;
    enlace.download = 'datos-pcam-' + fecha + '.csv';
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
    URL.revokeObjectURL(url);
}

/** Abre la configuración en una ventana flotante. */
function abrirConfiguracion() {
    const modal = document.getElementById('modal-configuracion');
    if (modal) modal.classList.add('abierto');
}

/** Cierra la ventana flotante de configuración. */
function cerrarConfiguracion() {
    const modal = document.getElementById('modal-configuracion');
    if (modal) modal.classList.remove('abierto');
}

/* ---------------------------------------------------------------- USUARIOS (ventana flotante) */

/** Abre la ventana flotante y carga la lista de usuarios (sin salir de la página). */
function abrirUsuarios() {
    document.getElementById('modal-usuarios').classList.add('abierto');
    mostrarEstadoUsuarios('', true);
    cargarUsuarios(0);
}

/** Cierra la ventana flotante. */
function cerrarUsuarios() {
    document.getElementById('modal-usuarios').classList.remove('abierto');
}

/** Mensaje dentro de la ventana (ok = verde, error = rojo). */
function mostrarEstadoUsuarios(texto, ok) {
    const estado = document.getElementById('usuarios-estado');
    estado.textContent = texto;
    estado.className = 'ventana-estado' + (texto ? (ok ? ' ok' : ' error') : '');
}

/**
 * Pide al servidor el formulario + lista de usuarios y los pone en la ventana.
 * id = 0 -> formulario para un usuario nuevo; id = N -> editar el usuario N.
 */
function cargarUsuarios(id) {
    const destino = document.getElementById('usuarios-contenido');
    const url = URL_USUARIOS + (id ? '&id=' + encodeURIComponent(id) : '');

    fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            // Si la sesión expiró el servidor manda el login completo: recarga la página
            if (html.indexOf('<html') !== -1) { window.location.reload(); return; }
            destino.innerHTML = html;
        })
        .catch(function () {
            mostrarEstadoUsuarios('No se pudo cargar la lista de usuarios.', false);
        });
}

/** Envía el formulario (crear o editar) sin recargar la página. */
function guardarUsuario(evento) {
    evento.preventDefault();
    enviarUsuarios(URL_GUARDAR_USUARIO, new FormData(evento.target));
}

/** Botón Borrar de una fila (pide confirmación). */
function borrarUsuario(boton) {
    if (!confirm('¿Borrar a ' + boton.dataset.nombre + '?')) return;

    const datos = new FormData();
    datos.append('csrf', document.querySelector('#usuarios-contenido [name=csrf]').value);
    datos.append('id', boton.dataset.id);
    enviarUsuarios(URL_BORRAR_USUARIO, datos);
}

/** POST común: muestra el mensaje y, si salió bien, recarga la lista. */
function enviarUsuarios(url, datos) {
    fetch(url, { method: 'POST', body: datos, headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
            mostrarEstadoUsuarios(resp.mensaje, resp.ok);
            if (resp.ok) cargarUsuarios(0);
        })
        .catch(function () {
            mostrarEstadoUsuarios('No se pudo guardar. Intenta de nuevo.', false);
        });
}

/* ---------------------------------------------------------------- DATOS */

/** Quita acentos y mayúsculas para comparar textos ("San Jerónimo" = "san jeronimo"). */
function normalizar(texto) {
    return (texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}

/**
 * Filtra las filas de la tabla (Status o Analógicas) por subestación.
 * Compara lo escrito en el buscador con el código y nombre de la subestación
 * que cada fila trae en data-estacion (ejemplo: "JRN San Jerónimo").
 * Buscador vacío = se muestran todas las filas.
 */
function filtrarSubestacion() {
    const campo = document.getElementById('filtro-subestacion');
    const cuerpo = document.getElementById('tabla-datos');
    if (!campo || !cuerpo) return;

    const texto = normalizar(campo.value);
    const filas = cuerpo.querySelectorAll('tr[data-estacion]');
    let visibles = 0;

    filas.forEach(function (fila) {
        const coincide = texto === '' || normalizar(fila.dataset.estacion).indexOf(texto) !== -1;
        fila.hidden = !coincide;
        if (coincide) visibles++;
    });

    // Mensaje "sin datos", contador y botón X del buscador
    document.getElementById('sin-resultados').hidden = (visibles > 0);
    document.getElementById('datos-contador').textContent = 'Mostrando ' + visibles + ' de ' + filas.length;
    campo.parentElement.classList.toggle('con-texto', campo.value !== '');
}

/** Botón X del buscador: borra el filtro y muestra todas las filas. */
function limpiarFiltro() {
    const campo = document.getElementById('filtro-subestacion');
    campo.value = '';
    filtrarSubestacion();
    campo.focus();
}

/** Al cambiar de pestaña (Status <-> Analógicas) se conserva lo escrito en el buscador. */
function conservarFiltroEnPestanas() {
    const campo = document.getElementById('filtro-subestacion');
    if (!campo) return;

    document.querySelectorAll('.tab-dato').forEach(function (enlace) {
        enlace.addEventListener('click', function () {
            const url = new URL(enlace.href);
            if (campo.value.trim() !== '') {
                url.searchParams.set('subestacion', campo.value.trim());
            }
            enlace.href = url.toString();
        });
    });
}

/* ---------------------------------------------------------------- AL CARGAR */

document.addEventListener('DOMContentLoaded', function () {
    // Cierra las ventanas flotantes con la tecla Esc
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (document.getElementById('modal-configuracion')) cerrarConfiguracion();
            if (document.getElementById('modal-usuarios')) cerrarUsuarios();
        }
    });

    // Datos: aplica el filtro inicial (si viene de otra pestaña) y lo conserva al cambiar de pestaña
    filtrarSubestacion();
    conservarFiltroEnPestanas();
});

// AQUÍ AGREGAS NUEVAS FUNCIONES
