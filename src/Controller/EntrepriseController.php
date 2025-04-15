<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Enum\Etat;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('/entreprise/mes-offres', name: 'entreprise_mes_offres')]
    public function mesOffres(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPRISE');

        // Récupérer l'utilisateur connecté
        $entreprise = $this->getUser();

        // Récupérer les postes de l'entreprise avec leurs candidatures
        $postes = $em->getRepository(\App\Entity\Poste::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.candidatures', 'c')
            ->addSelect('c')
            ->where('p.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();

        return $this->render('poste/index.html.twig', [
            'postes' => $postes,
        ]);
    }
    #[Route('/candidature/accepter/{id}', name: 'candidature_accepter', methods: ['POST'])]
    public function accepterCandidature(Candidature $candidature, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPRISE');

        // Changer l'état de la candidature à "Accepte"
        $candidature->setEtat(Etat::ACCEPTE);
        $em->flush();

        $this->addFlash('success', 'La candidature a été acceptée.');
        return $this->redirectToRoute('entreprise_mes_offres');
    }

    #[Route('/candidature/refuser/{id}', name: 'candidature_refuser', methods: ['POST'])]
    public function refuserCandidature(Candidature $candidature, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENTREPRISE');

        // Changer l'état de la candidature à "Refuse"
        $candidature->setEtat(Etat::REFUSE);
        $em->flush();

        $this->addFlash('success', 'La candidature a été refusée.');
        return $this->redirectToRoute('entreprise_mes_offres');
    }
}