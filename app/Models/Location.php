<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Location extends Model
{
    protected string $table = 'locations';

    /**
     * Retourne tous les emplacements d'un espace.
     */
    public function findBySpace(int $spaceId): array
    {
        return $this->findBy(['space_id' => $spaceId], 'name', 'ASC');
    }

    /**
     * Crée un emplacement dans un espace.
     */
    public function createInSpace(int $spaceId, array $data): int
    {
        $data['space_id'] = $spaceId;
        return $this->create($data);
    }
}
