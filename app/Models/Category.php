<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table = 'categories';

    /**
     * Retourne toutes les catégories d'un espace.
     */
    public function findBySpace(int $spaceId): array
    {
        return $this->findBy(['space_id' => $spaceId], 'name', 'ASC');
    }

    /**
     * Crée une catégorie dans un espace.
     */
    public function createInSpace(int $spaceId, array $data): int
    {
        $data['space_id'] = $spaceId;
        return $this->create($data);
    }
}
