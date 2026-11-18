<?php
/* =============================================================================
   app/controllers/DatosController.php  -  CONTROLADOR de la sección Datos
   =============================================================================
   Acciones (controller=datos&action=...):
     analogicas   Tabla de puntos analógicos (acción por defecto)
     status       Tabla de puntos de estado

   Los ven todos los roles (administrador, usuario e invitado); solo pide sesión.
   Los registros se solicitan al servicio externo configurado por GET/JSON.
   El filtro por subestación se hace en el navegador (app.js, filtrarSubestacion).
   Si llega &subestacion=XXX en la dirección, el buscador abre ya con ese texto
   (así al cambiar de pestaña Status/Analógicas no se pierde el filtro).
   ============================================================================= */

require_once MODEL_PATH . '/DatosModel.php';

class DatosController
{
    public function __construct()
    {
        // Sin sesión no se puede ver nada: regresa al login
        if (empty($_SESSION['usuario'])) {
            redirigir('login', 'loginIP');
        }
    }

    /** Datos > Analógicas. */
    public function analogicas()
    {
        $this->mostrar('datos_analogicas', 'analogicas');
    }

    /** Datos > Status. */
    public function status()
    {
        $this->mostrar('datos_status', 'status');
    }

    /** Prueba la conexión externa sin dibujar la tabla; solo para el administrador. */
    public function probar()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ((int) ($_SESSION['usuario']['rol'] ?? 0) !== 1) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'mensaje' => 'Solo el administrador puede probar la fuente de datos.']);
            exit;
        }

        try {
            $analogicas = DatosModel::analogicas();
            $status = DatosModel::estados();
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Conexión y JSON correctos.',
                'analogicas' => count($analogicas),
                'status' => count($status),
                'subestaciones' => count(DatosModel::subestaciones()),
            ]);
        } catch (Throwable $ex) {
            http_response_code(502);
            echo json_encode(['ok' => false, 'mensaje' => $ex->getMessage()]);
        }
        exit;
    }

    /** Dibuja inicio.php con la sección pedida y muestra un error si falla la fuente. */
    private function mostrar($seccion, $tipo)
    {
        $usuario = $_SESSION['usuario'];
        $config = Config::load();
        $filas = [];
        $subestaciones = [];
        $datosError = null;

        try {
            $filas = $tipo === 'status' ? DatosModel::estados() : DatosModel::analogicas();
            $subestaciones = DatosModel::subestaciones();
        } catch (Throwable $ex) {
            // No se muestran datos falsos si el OSI no responde o aún no está configurado.
            $datosError = $ex->getMessage();
        }

        $filtro = trim((string) ($_GET['subestacion'] ?? '')); // texto inicial del buscador

        require VIEW_PATH . '/inicio.php';
    }
}
