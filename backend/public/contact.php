<?php
declare(strict_types=1);

use App\Controllers\ContactController;
use App\Database;
use App\Http\Response;
use App\Mail\ContactMailBuilder;
use App\Mail\MailerInterface;
use App\Mail\NativeMailer;
use App\Mail\SmtpMailer;
use App\Repositories\ContactRepository;
use App\Validation\ContactValidator;

$config = require __DIR__ . '/../bootstrap.php';

// Ne pas révéler la version de PHP côté serveur, éviter toute mise en cache de réponses dynamiques,
// et empêcher le navigateur de deviner un autre type MIME pour la réponse JSON.
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isAllowedOrigin = in_array($origin, $config['cors']['allowed_origins'], true)
    || ($config['cors']['allow_any_localhost'] && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin) === 1);

if ($origin !== '' && $isAllowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
    exit;
}

// Rejette les corps de requête anormalement volumineux avant même de les lire/parser.
const MAX_BODY_BYTES = 20_000;
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > MAX_BODY_BYTES) {
    Response::json(['success' => false, 'message' => 'Requête trop volumineuse.'], 413);
    exit;
}

try {
    $pdo = Database::connection($config['db']);
    $mailConfig = $config['mail'];

    $mailer = $mailConfig['smtp']['host'] !== ''
        ? new SmtpMailer(
            $mailConfig['smtp']['host'],
            $mailConfig['smtp']['port'],
            $mailConfig['smtp']['encryption'],
            $mailConfig['smtp']['username'],
            $mailConfig['smtp']['password'],
            $mailConfig['from_email'],
            $mailConfig['from_name'],
            $mailConfig['smtp']['timeout']
        )
        : new NativeMailer($mailConfig['from_email'], $mailConfig['from_name']);

    /** @var MailerInterface $mailer */
    $controller = new ContactController(
        new ContactRepository($pdo),
        new ContactValidator(),
        $mailer,
        new ContactMailBuilder(),
        $mailConfig
    );

    $controller->handle();
} catch (Throwable $e) {
    error_log('[contact.php] ' . $e->getMessage());
    Response::json(['success' => false, 'message' => 'Erreur serveur.'], 500);
}
