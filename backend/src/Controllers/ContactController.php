<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Mail\ContactMailBuilder;
use App\Mail\MailerInterface;
use App\Repositories\ContactRepository;
use App\Validation\ContactValidator;
use Throwable;

final class ContactController
{
    private const RATE_LIMIT_MAX_SUBMISSIONS = 5;
    private const RATE_LIMIT_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly ContactRepository $repository,
        private readonly ContactValidator $validator,
        private readonly MailerInterface $mailer,
        private readonly ContactMailBuilder $mailBuilder,
        private readonly array $mailConfig
    ) {
    }

    public function handle(): void
    {
        $data = Request::jsonBody();
        $ip = Request::ip();

        // Champ piège anti-spam : rempli uniquement par les robots, on répond "succès" sans rien faire.
        if ($this->validator->isSpam($data)) {
            Response::json(['success' => true]);
            return;
        }

        if ($ip !== null && $this->repository->countRecentByIp($ip, self::RATE_LIMIT_WINDOW_MINUTES) >= self::RATE_LIMIT_MAX_SUBMISSIONS) {
            Response::json([
                'success' => false,
                'message' => 'Trop de tentatives. Merci de réessayer dans quelques minutes.',
            ], 429);
            return;
        }

        $errors = $this->validator->validate($data);
        if ($errors !== []) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $clean = $this->validator->sanitize($data) + [
            'ip_address' => $ip,
            'user_agent' => Request::userAgent(),
        ];

        try {
            $id = $this->repository->save($clean);
        } catch (Throwable $e) {
            error_log('[ContactController] Échec enregistrement: ' . $e->getMessage());
            Response::json(['success' => false, 'message' => "Une erreur est survenue lors de l'enregistrement de votre message."], 500);
            return;
        }

        $emailSent = false;
        try {
            $emailSent = $this->mailer->send(
                $this->mailConfig['to_email'],
                $this->mailConfig['to_name'],
                $this->mailBuilder->subject($clean['subject']),
                $this->mailBuilder->htmlBody($clean),
                $this->mailBuilder->textBody($clean),
                $clean['email'],
                $clean['name']
            );
        } catch (Throwable $e) {
            error_log('[ContactController] Échec envoi e-mail: ' . $e->getMessage());
        }

        $this->repository->markEmailSent($id, $emailSent);

        Response::json([
            'success' => true,
            'emailSent' => $emailSent,
            'message' => $emailSent
                ? 'Votre message a bien été envoyé. Nous vous répondrons rapidement.'
                : "Votre message a été enregistré mais l'envoi de l'e-mail a échoué. Nous vous recontacterons.",
        ]);
    }
}
