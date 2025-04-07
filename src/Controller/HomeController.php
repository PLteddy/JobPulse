<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $user = $this->getUser();

        // Si l'utilisateur est connecté, on le redirige en fonction de son rôle
        if ($user) {
            return $this->redirectToDashboard($user);
        }

        // Sinon, on affiche la page d'accueil classique
        return $this->render('home/index.html.twig');
    }

    private function redirectToDashboard($user): Response
    {
        if (in_array('ROLE_ETUDIANT', $user->getRoles())) {
            return $this->redirectToRoute('etudiant_dashboard');
        }

        if (in_array('ROLE_ENTREPRISE', $user->getRoles())) {
            return $this->redirectToRoute('entreprise_dashboard');
        }

        if (in_array('ROLE_TUTEUR', $user->getRoles())) {
            return $this->redirectToRoute('tuteur_dashboard');
        }

        // Par défaut, on le redirige vers l'accueil si aucun rôle ne correspond
        return $this->redirectToRoute('home');
    }
}
