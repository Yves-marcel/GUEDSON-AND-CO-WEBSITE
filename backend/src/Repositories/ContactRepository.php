<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contacts (name, email, subject, message, ip_address, user_agent, created_at)
             VALUES (:name, :email, :subject, :message, :ip_address, :user_agent, datetime(\'now\'))'
        );

        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':subject' => $data['subject'],
            ':message' => $data['message'],
            ':ip_address' => $data['ip_address'] ?? null,
            ':user_agent' => $data['user_agent'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markEmailSent(int $id, bool $sent): void
    {
        $stmt = $this->pdo->prepare('UPDATE contacts SET email_sent = :sent WHERE id = :id');
        $stmt->execute([':sent' => $sent ? 1 : 0, ':id' => $id]);
    }

    public function countRecentByIp(string $ipAddress, int $withinMinutes): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM contacts
             WHERE ip_address = :ip_address
               AND created_at >= datetime('now', :window)"
        );
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':window' => sprintf('-%d minutes', $withinMinutes),
        ]);

        return (int) $stmt->fetchColumn();
    }
}
