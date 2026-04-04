<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PasswordReset extends Model
{
    protected string $table = 'password_resets';

    /**
     * Génère un token, supprime les anciens tokens de cet email et sauvegarde le nouveau.
     * Retourne le token brut (à inclure dans le lien email).
     */
    public function createToken(string $email): string
    {
        // Supprimer les tokens existants pour cet email
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $token     = bin2hex(random_bytes(32)); // 64 caractères hex
        $tokenHash = hash('sha256', $token);    // Stockage du hash uniquement
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 heure

        $this->create([
            'email'      => $email,
            'token'      => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Retrouve un reset valide (non expiré) à partir du token brut.
     */
    public function findValidByToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $now       = $this->isSQLite() ? "datetime('now')" : 'NOW()';

        $stmt = $this->db->prepare(
            "SELECT * FROM password_resets WHERE token = :token AND expires_at > {$now} LIMIT 1"
        );
        $stmt->execute(['token' => $tokenHash]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Supprime un token après utilisation.
     */
    public function deleteByToken(string $token): void
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $tokenHash]);
    }

    /**
     * Supprime les tokens expirés (nettoyage).
     */
    public function deleteExpired(): void
    {
        $now = $this->isSQLite() ? "datetime('now')" : 'NOW()';
        $this->db->exec("DELETE FROM password_resets WHERE expires_at <= {$now}");
    }
}
