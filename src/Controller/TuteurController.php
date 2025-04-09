<?php

namespace App\Controller;

use App\Repository\PosteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TuteurController extends AbstractController
{
    #[Route('/tuteur', name: 'tuteur_dashboard')]
    public function index(PosteRepository $posteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        // Récupérer toutes les offres (postes) de toutes les entreprises
        $postes = $posteRepository->findAll();
        
        return $this->render('tuteur/index.html.twig', [
            'postes' => $postes,
        ]);
    }
}