<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PremiercontrollertestController extends AbstractController
{
    #[Route('/premiercontrollertest', name: 'app_premiercontrollertest')]
    public function index(): Response
    {
        return $this->render('premiercontrollertest/index.html.twig', [
            'controller_name' => 'PremiercontrollertestController',
        ]);
    }
}
