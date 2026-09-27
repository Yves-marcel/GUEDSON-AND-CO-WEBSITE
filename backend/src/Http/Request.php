<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    // Filet de sécurité si Content-Length est absent/mensonger : on ne lit jamais plus que ça,
    // quelle que soit la taille réelle envoyée par le client.
    private const MAX_BODY_BYTES = 20_000;

    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1);
        if ($raw === false || $raw === '') {
            return $_POST;
        }

        if (strlen($raw) > self::MAX_BODY_BYTES) {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    public static function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public static function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
