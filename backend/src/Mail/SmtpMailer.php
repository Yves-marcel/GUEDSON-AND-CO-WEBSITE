<?php
declare(strict_types=1);

namespace App\Mail;

use RuntimeException;

/**
 * Client SMTP minimaliste basé sur les sockets (sans dépendance externe).
 * Supporte STARTTLS/SSL et l'authentification AUTH LOGIN.
 */
final class SmtpMailer implements MailerInterface
{
    use GuardsHeaderInjection;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $encryption, // tls | ssl | none
        private readonly string $username,
        private readonly string $password,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly int $timeout = 15
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
        $transport = $this->encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if ($socket === false) {
            throw new RuntimeException("Connexion SMTP impossible : {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $this->timeout);
        $clientName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $toEmail = $this->stripHeaderInjection($toEmail);
        $fromEmail = $this->stripHeaderInjection($this->fromEmail);
        $replyToEmail = $replyToEmail !== null ? $this->stripHeaderInjection($replyToEmail) : null;

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . $clientName, [250]);

            if ($this->encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Échec de la négociation TLS.');
                }
                $this->command($socket, 'EHLO ' . $clientName, [250]);
            }

            if ($this->username !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($this->username), [334]);
                $this->command($socket, base64_encode($this->password), [235]);
            }

            $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $message = $this->buildMessage($toEmail, $toName, $subject, $htmlBody, $textBody, $replyToEmail, $replyToName);
            $this->command($socket, $this->stuffDots($message) . "\r\n.", [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }

        return true;
    }

    private function buildMessage(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody,
        ?string $replyToEmail,
        ?string $replyToName
    ): string {
        $boundary = md5(uniqid('', true));
        $clientName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $fromEmail = $this->stripHeaderInjection($this->fromEmail);

        $headers = [];
        $headers[] = 'From: ' . $this->encodeHeader($this->fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'To: ' . $this->encodeHeader($toName) . ' <' . $toEmail . '>';
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $clientName . '>';
        if ($replyToEmail !== null) {
            $headers[] = 'Reply-To: ' . $this->encodeHeader($replyToName ?? $replyToEmail) . ' <' . $replyToEmail . '>';
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$textBody}\r\n\r\n";
        $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$htmlBody}\r\n\r\n";
        $body .= "--{$boundary}--";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function stuffDots(string $body): string
    {
        return preg_replace('/^\./m', '..', $body);
    }

    /** @param resource $socket @param int[] $expectedCodes */
    private function command($socket, string $line, array $expectedCodes): string
    {
        fwrite($socket, $line . "\r\n");
        return $this->expect($socket, $expectedCodes);
    }

    /** @param resource $socket @param int[] $expectedCodes */
    private function expect($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException("Réponse SMTP inattendue : {$response}");
        }

        return $response;
    }
}
