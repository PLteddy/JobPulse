<?php

namespace App\Controller;

use App\Repository\PosteRepository; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtudiantController extends AbstractController
{
    #[Route('/etudiant', name: 'etudiant_dashboard')]
    public function index(PosteRepository $posteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');
        
        // Récupérer toutes les offres (postes) de toutes les entreprises
        $postes = $posteRepository->findAll();
        
        return $this->render('etudiant/index.html.twig', [
            'postes' => $postes,
        ]);
    }
}