<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

class HomeController extends Controller
{
    /**
     * Page d'accueil — redirige vers le tableau de bord si connecté.
     */
    public function index(): void
    {
        if (Session::get('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('home/index', [
            'title' => 'Gestion de Stocks',
        ]);
    }
}
