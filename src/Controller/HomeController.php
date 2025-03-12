<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    #quand on veut que ce soit l'url c  #[Route(path:'lucky/number')] par exemple et dcp quand on va dans lucky/number on tombera sur ça
    public function index(): Response
    {
        return new Response('OK');
    }
}
