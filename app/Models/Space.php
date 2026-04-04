<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Space extends Model
{
    protected string $table = 'spaces';

    /**
     * Crée un espace et ajoute le créateur comme administrateur.
     */
    public function createWithOwner(array $data, int $userId): int
    {
        $data['created_by'] = $userId;
        $spaceId = $this->create($data);

        $this->db->prepare(
            "INSERT INTO space_members (space_id, user_id, role) VALUES (:space_id, :user_id, 'administrateur')"
        )->execute(['space_id' => $spaceId, 'user_id' => $userId]);

        return $spaceId;
    }

    /**
     * Retourne les espaces auxquels un utilisateur appartient.
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, sm.role FROM spaces s
             INNER JOIN space_members sm ON sm.space_id = s.id
             WHERE sm.user_id = :user_id
             ORDER BY s.name ASC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne le rôle d'un utilisateur dans un espace.
     */
    public function getUserRole(int $spaceId, int $userId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT role FROM space_members WHERE space_id = :space_id AND user_id = :user_id"
        );
        $stmt->execute(['space_id' => $spaceId, 'user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['role'] : null;
    }

    /**
     * Retourne les membres d'un espace.
     */
    public function getMembers(int $spaceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.email, sm.role, sm.created_at as joined_at
             FROM users u
             INNER JOIN space_members sm ON sm.user_id = u.id
             WHERE sm.space_id = :space_id
             ORDER BY u.username ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un membre à un espace.
     */
    public function addMember(int $spaceId, int $userId, string $role = 'membre'): bool
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO space_members (space_id, user_id, role) VALUES (:space_id, :user_id, :role)"
        );
        return $stmt->execute(['space_id' => $spaceId, 'user_id' => $userId, 'role' => $role]);
    }

    /**
     * Met à jour le rôle d'un membre.
     */
    public function updateMemberRole(int $spaceId, int $userId, string $role): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE space_members SET role = :role WHERE space_id = :space_id AND user_id = :user_id"
        );
        return $stmt->execute(['space_id' => $spaceId, 'user_id' => $userId, 'role' => $role]);
    }

    /**
     * Retire un membre d'un espace.
     */
    public function removeMember(int $spaceId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM space_members WHERE space_id = :space_id AND user_id = :user_id"
        );
        return $stmt->execute(['space_id' => $spaceId, 'user_id' => $userId]);
    }
}
