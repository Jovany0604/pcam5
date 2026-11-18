<?php
/* =============================================================================
   app/controllers/ReporteController.php  -  Envía REPORTES por correo (Gmail)
   =============================================================================
   Acción: controller=reporte&action=enviar  (POST desde public/js/app.js)
   Recibe: asunto, mensaje
   Responde JSON: {"ok": true|false, "mensaje": "..."}

   Protocolo SMTP de Gmail (datos en config.xml > <smtp>):
     Servidor  smtp.gmail.com
     Puerto    587 + STARTTLS ("tls")   ó   465 + SSL ("ssl")
     Usuario   cuenta completa de Gmail
     Password  "Contraseña de aplicación" (requiere verificación en 2 pasos)
   Se usa PHPMailer (app/libs/PHPMailer), sin Composer.
   ============================================================================= */

require_once LIBS_PATH . '/PHPMailer/Exception.php';
require_once LIBS_PATH . '/PHPMailer/PHPMailer.php';
require_once LIBS_PATH . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ReporteController
{
    public function enviar()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Solo usuarios con sesión (incluye invitado) y solo por POST
        if (empty($_SESSION['usuario'])) {
            $this->responder(false, 'Tu sesión terminó, vuelve a entrar.');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(false, 'Método no permitido.');
        }

        $asunto  = trim($_POST['asunto'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        if ($asunto === '' || $mensaje === '') {
            $this->responder(false, 'Escribe el asunto y el mensaje.');
        }

        $smtp    = Config::load()->smtp;
        $usuario = $_SESSION['usuario'];

        $mail = new PHPMailer(true); // true = lanza excepciones si falla
        try {
            // ---- Conexión SMTP ----
            $mail->isSMTP();
            $mail->Host       = (string) $smtp->host;      // smtp.gmail.com
            $mail->Port       = (int) $smtp->puerto;       // 587
            $mail->SMTPAuth   = true;                      // Gmail siempre pide usuario/contraseña
            $mail->Username   = (string) $smtp->usuario;
            $mail->Password   = (string) $smtp->password;  // contraseña de aplicación
            $mail->SMTPSecure = ((string) $smtp->seguridad === 'ssl')
                ? PHPMailer::ENCRYPTION_SMTPS              // 465
                : PHPMailer::ENCRYPTION_STARTTLS;          // 587
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            // ---- Remitente y destinatario ----
            // Gmail exige que el remitente sea la misma cuenta que inicia sesión
            $mail->setFrom((string) $smtp->usuario, (string) $smtp->remitente_nombre);
            $mail->addAddress((string) $smtp->destinatario);

            // ---- Contenido ----
            $mail->isHTML(true);
            $mail->Subject = '[PCAM] ' . $asunto;
            $mail->Body    =
                '<h3 style="color:#00664f">Reporte desde PCAM</h3>'
                . '<p><b>Usuario:</b> ' . e($usuario['nombre']) . ' (' . e($usuario['username']) . ')</p>'
                . '<p><b>IP:</b> ' . e($_SERVER['REMOTE_ADDR'] ?? '') . '</p>'
                . '<p><b>Fecha:</b> ' . date('Y-m-d H:i') . '</p><hr>'
                . '<p>' . nl2br(e($mensaje)) . '</p>';
            $mail->AltBody = $mensaje; // versión texto plano

            $mail->send();
            $this->responder(true, 'Reporte enviado correctamente.');

        } catch (Exception $ex) {
            // ErrorInfo trae el motivo (contraseña incorrecta, puerto bloqueado, etc.)
            $this->responder(false, 'No se pudo enviar: ' . $mail->ErrorInfo);
        }
    }

    /** Imprime la respuesta JSON y termina. */
    private function responder($ok, $mensaje)
    {
        echo json_encode(['ok' => $ok, 'mensaje' => $mensaje]);
        exit;
    }
}
