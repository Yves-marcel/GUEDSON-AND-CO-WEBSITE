<?php
declare(strict_types=1);

// Lecture d'une variable d'environnement booléenne : absente => $default,
// sinon "0" / "false" / "no" / "off" (insensible à la casse) => false, tout le reste => true.
// (Évite le piège "0" ?: "1" qui vaut "1" en PHP car "0" est falsy.)
$boolEnv = static function (string $key, bool $default): bool {
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return !in_array(strtolower(trim($value)), ['0', 'false', 'no', 'off'], true);
};

return [
    'db' => [
        'path' => __DIR__ . '/../data/contacts.sqlite',
    ],
    'mail' => [
        // 'smtp' pour un envoi via un serveur SMTP (recommandé), 'native' pour la fonction mail() de PHP.
        'driver' => getenv('MAIL_DRIVER') ?: 'native',
        'smtp' => [
            'host' => getenv('SMTP_HOST') ?: '', // ex OVH : smtp.mail.ovh.net
            'port' => (int) (getenv('SMTP_PORT') ?: 465),
            'encryption' => getenv('SMTP_ENCRYPTION') ?: 'ssl', // tls (587) | ssl (465) | none
            'username' => getenv('SMTP_USERNAME') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
            'timeout' => 15,
        ],
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'sales@guedsonandcointertrade.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Site Guedson & Co',
        'to_email' => getenv('MAIL_TO_EMAIL') ?: 'sales@guedsonandcointertrade.com',
        'to_name' => getenv('MAIL_TO_NAME') ?: 'Guedson & Co',
    ],
    'cors' => [
        // Domaines de production autorisés (ex: https://guedsonandcointertrade.com). Vide par défaut.
        'allowed_origins' => array_filter(array_map('trim', explode(
            ',',
            getenv('CORS_ALLOWED_ORIGINS') ?: ''
        ))),
        // En dev, Vite change de port si le port par défaut est occupé (5173, 5174, ...).
        // On autorise donc automatiquement tout http(s)://localhost:*  et 127.0.0.1:*.
        'allow_any_localhost' => $boolEnv('CORS_ALLOW_ANY_LOCALHOST', true),
    ],
];
