<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Utilisateur;

class EtudiantController extends AbstractController
{
    #[Route('/etudiant', name: 'etudiant_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher le tableau de bord étudiant
        return $this->render('etudiant/index.html.twig');
    }

    #[Route('/etudiant/candidatures', name: 'etudiant_candidatures')]
    public function candidatures(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher les candidatures de l'étudiant
        return $this->render('etudiant/candidatures.html.twig');
    }

    #[Route('/etudiant/enregistrements', name: 'etudiant_enregistrements')]
    public function enregistrements(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher les enregistrements de l'étudiant
        return $this->render('etudiant/enregistrements.html.twig');
    }

    #[Route('/etudiant/tuteur', name: 'etudiant_tuteur')]
    public function tuteur(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher les informations sur le tuteur
        return $this->render('etudiant/tuteur.html.twig');
    }

    #[Route('/etudiant/profil', name: 'etudiant_profil')]
    public function profil(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher les informations sur le tuteur
        return $this->render('etudiant/profil.html.twig');
    }
}
