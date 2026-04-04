<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InventoryItem;

class DashboardController extends Controller
{
    private InventoryItem $inventoryModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryItem();
    }

    /**
     * Tableau de bord — produits à consommer prochainement.
     */
    public function index(): void
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();

        $expiringSoon = $this->inventoryModel->findExpiringSoonForUser($userId, 14);
        $expired = $this->inventoryModel->findExpiredForUser($userId);
        $allWithExpiry = $this->inventoryModel->findAllWithExpiryForUser($userId);

        // Construire les événements pour FullCalendar
        $calendarEvents = [];
        $today = date('Y-m-d');
        foreach ($allWithExpiry as $item) {
            $isExpired = $item['expiry_date'] < $today;
            $isExpiringSoon = !$isExpired && $item['expiry_date'] <= date('Y-m-d', strtotime('+14 days'));

            if ($isExpired) {
                $color = '#ef476f';
            } elseif ($isExpiringSoon) {
                $color = '#ffd166';
            } else {
                $color = '#06d6a0';
            }

            $calendarEvents[] = [
                'title' => $item['product_name'] . ' (×' . $item['quantity'] . ')',
                'start' => $item['expiry_date'],
                'color' => $color,
                'extendedProps' => [
                    'location' => $item['location_name'],
                    'space' => $item['space_name'],
                    'quantity' => $item['quantity'],
                    'spaceId' => $item['sid'],
                ],
            ];
        }

        $this->render('dashboard/index', [
            'title' => 'Tableau de bord',
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
            'calendarEventsJson' => json_encode($calendarEvents, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
