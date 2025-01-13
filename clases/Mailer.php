<?php

//Clase para envio de correo electrónico

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public function enviarEmail($email, $asunto, $cuerpo, $email_copia = null, $asunto_copia = null, $cuerpo_copia = null)
    {
        require_once __DIR__ . '/../config/config.php';
        require  __DIR__ . '/../phpmailer/src/PHPMailer.php';
        require  __DIR__ . '/../phpmailer/src/SMTP.php';
        require  __DIR__ . '/../phpmailer/src/Exception.php';

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                //Enable verbose debug output
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;                     //Configure el servidor SMTP para enviar
            $mail->SMTPAuth   = true;                          // Habilita la autenticación SMTP
            $mail->Username   = MAIL_USER;                     //Usuario SMTP
            $mail->Password   = MAIL_PASS;                     //Contraseña SMTP
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Habilitar el cifrado TLS
            $mail->Port       = MAIL_PORT;                     //Puerto TCP al que conectarse, si usa 587 agregar `SMTPSecure = PHPMailer :: ENCRYPTION_STARTTLS`

            //Correo emisor y nombre
            $mail->setFrom(MAIL_USER, 'Representaciones Gueos');
            //Correo receptor y nombre
            $mail->addAddress($email);
            //$mail->addBCC(MAIL_USER);

            //Contenido
            $mail->isHTML(true);   //Establecer el formato de correo electrónico en HTML
            $mail->Subject = $asunto; //Titulo del correo

            //Cuerpo del correo
            $mail->Body = mb_convert_encoding($cuerpo, 'ISO-8859-1', 'UTF-8');

            //Enviar correo
            $mail->send();

            if ($email_copia) {
                $mail->clearAddresses(); // Limpiar las direcciones previas

                // Agregar la dirección de la copia
                $mail->addAddress($email_copia); // Agregar dirección de la copia

                // Cambiar el asunto y el cuerpo del correo para la copia
                $mail->Subject = $asunto_copia ? $asunto_copia : $asunto;  // Si no se proporciona un asunto de copia, usa el original
                $mail->Body = mb_convert_encoding($cuerpo_copia ? $cuerpo_copia : $cuerpo, 'ISO-8859-1', 'UTF-8');

                // Enviar correo a la dirección de copia
                $mail->send();
            }

            return true;
        } catch (Exception $e) {
            echo "No se pudo enviar el mensaje. Error de envío: {$mail->ErrorInfo}";
            return false;
        }
    }
}
