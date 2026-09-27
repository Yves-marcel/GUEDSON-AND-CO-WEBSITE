<?php
declare(strict_types=1);

namespace App\Mail;

/**
 * Envoie les e-mails via la fonction native mail() de PHP.
 * Nécessite un agent d'envoi (sendmail) configuré sur le serveur.
 */
final class NativeMailer implements MailerInterface
{
    use GuardsHeaderInjection;

    public function __construct(
        private readonly string $fromEmail,
        private readonly string $fromName
    ) {
    }

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody,
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool {
        $boundary = md5(uniqid('', true));
        $toEmail = $this->stripHeaderInjection($toEmail);
        $fromEmail = $this->stripHeaderInjection($this->fromEmail);

        $headers = [];
        $headers[] = 'From: ' . $this->encodeHeader($this->fromName) . ' <' . $fromEmail . '>';
        if ($replyToEmail !== null) {
            $replyToEmail = $this->stripHeaderInjection($replyToEmail);
            $headers[] = 'Reply-To: ' . $this->encodeHeader($replyToName ?? $replyToEmail) . ' <' . $replyToEmail . '>';
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$textBody}\r\n\r\n";
        $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$htmlBody}\r\n\r\n";
        $body .= "--{$boundary}--";

        // -f fixe l'expéditeur enveloppe (Return-Path). La plupart des hébergeurs mutualisés
        // (dont OVH) l'exigent aligné avec le domaine From pour ne pas finir en spam/être rejeté.
        $envelopeSender = '-f' . $fromEmail;

        return @mail($toEmail, $this->encodeHeader($subject), $body, implode("\r\n", $headers), $envelopeSender);
    }
}
