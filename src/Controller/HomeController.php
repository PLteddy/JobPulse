<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Poste;
use App\Enum\Contrat;
use App\Enum\Duree;
use App\Enum\Type_presence;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Si l'utilisateur est connecté, on le redirige en fonction de son rôle
        if ($user) {
            return $this->redirectToDashboard($user);
        }
        
        // Récupération des paramètres de recherche et filtres
        $searchTerm = $request->query->get('q', '');
        
        // Utiliser all() pour récupérer un tableau ou getAlnum() pour les tableaux de paramètres
        $contratType = $request->query->all('contrat');
        $duree = $request->query->all('duree');
        
        $salaireMin = $request->query->get('salaire_min');
        $presence = $request->query->get('presence');
        
        // Construction de la requête
        $qb = $em->getRepository(Poste::class)->createQueryBuilder('p');
        
        if ($searchTerm) {
            $qb->andWhere('p.intitule LIKE :searchTerm OR p.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
        
        if (!empty($contratType)) {
            $qb->andWhere('p.contrat_type IN (:contratType)')
               ->setParameter('contratType', $contratType);
        }
        
        if (!empty($duree)) {
            $qb->andWhere('p.duree IN (:duree)')
               ->setParameter('duree', $duree);
        }
        
        if ($salaireMin) {
            $qb->andWhere('p.salaire >= :salaireMin')
               ->setParameter('salaireMin', $salaireMin);
        }
        
        if ($presence) {
            $qb->andWhere('p.presence = :presence')
               ->setParameter('presence', $presence);
        }
        
        $postes = $qb->getQuery()->getResult();
        
        // Sinon, on affiche la page d'accueil classique
        return $this->render('home/index.html.twig', [
            'postes' => $postes,
            'searchTerm' => $searchTerm,
            'selectedContrat' => $contratType,
            'selectedDuree' => $duree,
            'selectedSalaireMin' => $salaireMin,
            'selectedPresence' => $presence,
            'contratTypes' => Contrat::cases(),
            'durees' => Duree::cases(),
            'presences' => Type_presence::cases(),
        ]);
    }
    
    private function redirectToDashboard($user): Response
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
        
        // Par défaut, on le redirige vers l'accueil si aucun rôle ne correspond
        return $this->redirectToRoute('home');
    }
    #[Route('/offre/{id}', name: 'offre_details')]
    public function offreDetails(Poste $poste): Response
    {
        return $this->render('home/offre_details.html.twig', [
            'poste' => $poste
        ]);
    }    
}