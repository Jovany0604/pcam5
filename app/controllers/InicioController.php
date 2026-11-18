<?php
/* =============================================================================
   app/controllers/InicioController.php  -  CONTROLADOR de la página principal
   =============================================================================
   Acciones:
     controller=inicio&action=index      Redirige a Datos
     controller=inicio&action=diagrama   Redirige a Datos (compatibilidad)
   Pide sesión iniciada.
   ============================================================================= */

class InicioController
{
    public function __construct()
    {
        // Sin sesión no se puede ver nada: regresa al login
        if (empty($_SESSION['usuario'])) {
            redirigir('login', 'loginIP');
        }
    }

    /** La pantalla principal ahora inicia directamente en Datos. */
    public function index()
    {
        redirigir('datos', 'analogicas');
    }

    /** El enlace antiguo del diagrama también lleva a Datos. */
    public function diagrama()
    {
        redirigir('datos', 'analogicas');
    }

    // AQUÍ AGREGAS MÁS ACCIONES DE LA PÁGINA PRINCIPAL
}
