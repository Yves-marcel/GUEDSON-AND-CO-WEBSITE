<?php
declare(strict_types=1);

namespace App\Mail;

final class ContactMailBuilder
{
    public function subject(string $formSubject): string
    {
        return '[Site Web] ' . $formSubject;
    }

    public function textBody(array $data): string
    {
        return "Nouveau message depuis le formulaire de contact\n\n"
            . "Nom : {$data['name']}\n"
            . "E-mail : {$data['email']}\n"
            . "Objet : {$data['subject']}\n\n"
            . "Message :\n{$data['message']}\n";
    }

    public function htmlBody(array $data): string
    {
        $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8'));

        return <<<HTML
            <div style="font-family:Arial,sans-serif;line-height:1.6;color:#111">
                <h2>Nouveau message depuis le formulaire de contact</h2>
                <p><strong>Nom :</strong> {$name}</p>
                <p><strong>E-mail :</strong> {$email}</p>
                <p><strong>Objet :</strong> {$subject}</p>
                <p><strong>Message :</strong><br>{$message}</p>
            </div>
        HTML;
    }
}
