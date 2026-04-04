#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script de récapitulatif journalier.
 * Envoie un email de résumé aux utilisateurs ayant activé daily_digest.
 * À exécuter en cron, chaque matin à 8h.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Mailer;

// Connexion à la base de données
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'app_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo '[daily-digest] Erreur connexion BDD : ' . $e->getMessage() . "\n";
    exit(1);
}

// Récupérer les utilisateurs abonnés au digest
$users = $pdo->query(
    "SELECT id, username, email, COALESCE(firstname, '') as firstname
     FROM users WHERE daily_digest = 1 AND email IS NOT NULL AND email != ''"
)->fetchAll();

if (empty($users)) {
    echo '[daily-digest] Aucun utilisateur abonné. Fin.\n';
    exit(0);
}

$appUrl  = rtrim(getenv('APP_URL') ?: 'http://localhost:8085', '/');
$today   = date('d/m/Y');
$sent    = 0;
$errors  = 0;

foreach ($users as $u) {
    $userId = (int)$u['id'];

    // Produits périmés (tous les espaces de l'utilisateur)
    $expired = $pdo->prepare(
        "SELECT p.name as product_name, ii.expiry_date, l.name as location_name, s.name as space_name, ii.quantity
         FROM inventory_items ii
         INNER JOIN products p ON p.id = ii.product_id
         INNER JOIN locations l ON l.id = ii.location_id
         INNER JOIN spaces s ON s.id = ii.space_id
         INNER JOIN space_members sm ON sm.space_id = s.id AND sm.user_id = :uid
         WHERE ii.is_casse = 0 AND ii.expiry_date IS NOT NULL AND ii.expiry_date < CURDATE()
         ORDER BY ii.expiry_date ASC"
    );
    $expired->execute(['uid' => $userId]);
    $expiredItems = $expired->fetchAll();

    // Produits expirant dans les 7 prochains jours
    $soon = $pdo->prepare(
        "SELECT p.name as product_name, ii.expiry_date, l.name as location_name, s.name as space_name, ii.quantity
         FROM inventory_items ii
         INNER JOIN products p ON p.id = ii.product_id
         INNER JOIN locations l ON l.id = ii.location_id
         INNER JOIN spaces s ON s.id = ii.space_id
         INNER JOIN space_members sm ON sm.space_id = s.id AND sm.user_id = :uid
         WHERE ii.is_casse = 0
           AND ii.expiry_date IS NOT NULL
           AND ii.expiry_date >= CURDATE()
           AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
         ORDER BY ii.expiry_date ASC"
    );
    $soon->execute(['uid' => $userId]);
    $soonItems = $soon->fetchAll();

    // Ne pas envoyer si rien à signaler
    if (empty($expiredItems) && empty($soonItems)) {
        echo "[daily-digest] {$u['email']} — rien à signaler, email non envoyé.\n";
        continue;
    }

    $prenom = $u['firstname'] ?: $u['username'];

    // Construction du corps HTML
    $body = '<!DOCTYPE html><html lang="fr"><body style="font-family:sans-serif;color:#333;max-width:600px;margin:auto;">';
    $body .= '<h2 style="color:#3a86ff;">Récapitulatif du ' . $today . '</h2>';
    $body .= '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES) . ',</p>';
    $body .= '<p>Voici votre résumé quotidien de l\'état de vos stocks.</p>';

    if (!empty($expiredItems)) {
        $body .= '<h3 style="color:#ef476f;">⚠️ Produits périmés (' . count($expiredItems) . ')</h3>';
        $body .= '<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">';
        $body .= '<tr style="background:#ffeef2;text-align:left;"><th>Produit</th><th>Espace</th><th>Emplacement</th><th>Quantité</th><th>Périmé le</th></tr>';
        foreach ($expiredItems as $item) {
            $body .= '<tr style="border-bottom:1px solid #eee;">';
            $body .= '<td>' . htmlspecialchars($item['product_name'], ENT_QUOTES) . '</td>';
            $body .= '<td>' . htmlspecialchars($item['space_name'], ENT_QUOTES) . '</td>';
            $body .= '<td>' . htmlspecialchars($item['location_name'], ENT_QUOTES) . '</td>';
            $body .= '<td>' . (int)$item['quantity'] . '</td>';
            $body .= '<td>' . date('d/m/Y', strtotime($item['expiry_date'])) . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
    }

    if (!empty($soonItems)) {
        $body .= '<h3 style="color:#e09f1b;margin-top:1.5rem;">🕐 Expire dans les 7 jours (' . count($soonItems) . ')</h3>';
        $body .= '<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">';
        $body .= '<tr style="background:#fff8e6;text-align:left;"><th>Produit</th><th>Espace</th><th>Emplacement</th><th>Quantité</th><th>Expire le</th></tr>';
        foreach ($soonItems as $item) {
            $body .= '<tr style="border-bottom:1px solid #eee;">';
            $body .= '<td>' . htmlspecialchars($item['product_name'], ENT_QUOTES) . '</td>';
            $body .= '<td>' . htmlspecialchars($item['space_name'], ENT_QUOTES) . '</td>';
            $body .= '<td>' . htmlspecialchars($item['location_name'], ENT_QUOTES) . '</td>';
            $body .= '<td>' . (int)$item['quantity'] . '</td>';
            $body .= '<td>' . date('d/m/Y', strtotime($item['expiry_date'])) . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
    }

    $body .= '<p style="margin-top:1.5rem;">
        <a href="' . htmlspecialchars($appUrl . '/dashboard', ENT_QUOTES) . '"
           style="display:inline-block;padding:10px 20px;background:#3a86ff;color:#fff;text-decoration:none;border-radius:6px;">
            Voir le tableau de bord
        </a>
    </p>';
    $body .= '<hr style="margin-top:2rem;border:none;border-top:1px solid #eee;">';
    $body .= '<p style="font-size:0.8rem;color:#999;">
        Vous recevez cet email car vous avez activé le récapitulatif journalier.
        Vous pouvez le désactiver depuis <a href="' . htmlspecialchars($appUrl . '/profile', ENT_QUOTES) . '">votre profil</a>.
    </p>';
    $body .= '</body></html>';

    try {
        Mailer::send($u['email'], $prenom, 'Récapitulatif stocks du ' . $today, $body);
        echo "[daily-digest] Email envoyé à {$u['email']}.\n";
        $sent++;
    } catch (\Exception $e) {
        echo "[daily-digest] ERREUR envoi à {$u['email']} : " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "[daily-digest] Terminé — {$sent} envoyé(s), {$errors} erreur(s).\n";
