<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TuteurController extends AbstractController
{
    #[Route('/tuteur', name: 'tuteur_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        return $this->render('tuteur/index.html.twig');
    }
}