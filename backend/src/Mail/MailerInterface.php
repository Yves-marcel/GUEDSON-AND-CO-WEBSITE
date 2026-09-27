<?php
declare(strict_types=1);

namespace App\Mail;

interface MailerInterface
{
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody,
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool;
}
