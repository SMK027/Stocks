<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Wrapper d'envoi d'email via PHPMailer.
 * Configuré par les variables d'environnement SMTP_*.
 */
class Mailer
{
    /**
     * Envoie un email HTML.
     *
     * @throws \Exception en cas d'échec d'envoi
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $smtpHost = getenv('SMTP_HOST') ?: '';

        if ($smtpHost) {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);

            $smtpUser = getenv('SMTP_USER') ?: '';
            if ($smtpUser) {
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = getenv('SMTP_PASS') ?: '';
            }

            $encryption = strtolower(getenv('SMTP_ENCRYPTION') ?: '');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        }

        $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@localhost';
        $fromName    = getenv('MAIL_FROM_NAME')    ?: 'Application';

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $mail->send();
    }
}
