<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginController extends AbstractController
{
    use TargetPathTrait;

    #[Route('/login', name: 'app_login')]
// LoginController.php
#[Route('/login', name: 'app_login')]
public function index(AuthenticationUtils $authenticationUtils): Response
{
    // Redirection si déjà connecté
    if ($this->getUser()) {
        return $this->redirectToRouteForUser($this->getUser());
    }

    // Récupérer l'URL précédente pour rediriger vers la page demandée après login
    $request = $this->container->get('request_stack')->getCurrentRequest();
    if ($request && $targetPath = $request->headers->get('referer')) {
        $this->saveTargetPath($request->getSession(), 'main', $targetPath);
    }

    return $this->render('login/index.html.twig', [
        'last_username' => $authenticationUtils->getLastUsername(),
        'error' => $authenticationUtils->getLastAuthenticationError(),
    ]);
}

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Le code ici ne sera jamais exécuté
        // La déconnexion est gérée par le système de sécurité
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function redirectToRouteForUser($user): Response
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
    
        return $this->redirectToRoute('home');
    }
}