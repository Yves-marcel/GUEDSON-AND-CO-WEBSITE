<?php
declare(strict_types=1);

namespace App\Validation;

final class ContactValidator
{
    private const MAX_LENGTH = ['name' => 100, 'email' => 120, 'subject' => 150, 'message' => 2000];
    private const EMAIL_RE = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';

    // Champs "une seule ligne" : un retour chariot/saut de ligne signalerait une tentative
    // d'injection d'en-tête e-mail (CRLF injection / header injection via Reply-To, Subject, ...).
    private const SINGLE_LINE_FIELDS = ['name', 'email', 'subject'];

    /**
     * @return array<string, string> Champ => message d'erreur
     */
    public function validate(array $data): array
    {
        $errors = [];

        foreach (self::MAX_LENGTH as $field => $maxLength) {
            $value = trim($this->stripNullBytes((string) ($data[$field] ?? '')));

            if ($value === '') {
                $errors[$field] = 'Ce champ est requis.';
                continue;
            }

            if (mb_strlen($value) > $maxLength) {
                $errors[$field] = 'Ce champ dépasse la longueur maximale autorisée.';
                continue;
            }

            if (in_array($field, self::SINGLE_LINE_FIELDS, true) && $this->containsLineBreak($value)) {
                $errors[$field] = 'Ce champ ne doit pas contenir de retour à la ligne.';
            }
        }

        $email = trim($this->stripNullBytes((string) ($data['email'] ?? '')));
        if ($email !== '' && !preg_match(self::EMAIL_RE, $email)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        }

        return $errors;
    }

    public function isSpam(array $data): bool
    {
        return trim((string) ($data['company'] ?? '')) !== '';
    }

    /**
     * Normalise les champs après validation : octets nuls retirés, espaces superflus coupés.
     * @return array{name: string, email: string, subject: string, message: string}
     */
    public function sanitize(array $data): array
    {
        $clean = [];
        foreach (array_keys(self::MAX_LENGTH) as $field) {
            $clean[$field] = trim($this->stripNullBytes((string) ($data[$field] ?? '')));
        }
        return $clean;
    }

    private function containsLineBreak(string $value): bool
    {
        return (bool) preg_match('/[\r\n]/', $value);
    }

    private function stripNullBytes(string $value): string
    {
        return str_replace("\0", '', $value);
    }
}
