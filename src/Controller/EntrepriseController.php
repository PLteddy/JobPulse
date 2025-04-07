<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprise', name: 'entreprise_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPRISE');
        return $this->render('entreprise/index.html.twig');
    }
}