<?php
declare(strict_types=1);

namespace App\Mail;

/**
 * Défense en profondeur contre l'injection d'en-têtes SMTP (CRLF injection) : toute valeur
 * insérée brute dans un en-tête (adresses e-mail notamment) passe par ce filtre, même si elle
 * a déjà été validée en amont par ContactValidator.
 */
trait GuardsHeaderInjection
{
    private function stripHeaderInjection(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($this->stripHeaderInjection($value)) . '?=';
    }
}
