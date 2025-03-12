<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ControleurTestController extends AbstractController
{
    #[Route('/controleur/test', name: 'app_controleur_test')]
    public function index(): Response
    {
        return $this->render('controleur_test/index.html.twig', [
            'controller_name' => 'ControleurTestController',
        ]);
    }
}
