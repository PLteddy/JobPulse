<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Poste;
use App\Entity\Utilisateur; // Correction de l'entité
use App\Enum\Contrat;
use App\Enum\Duree;
use App\Enum\Type_presence;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Candidature;
use App\Form\CandidatureType;
use App\Enum\Etat;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
                
        // Récupération des paramètres de recherche et filtres
        $searchTerm = $request->query->get('q', '');
        
        // Utiliser all() pour récupérer un tableau ou getAlnum() pour les tableaux de paramètres
        $contratType = $request->query->all('contrat');
        $duree = $request->query->all('duree');
        
        $salaireMin = $request->query->get('salaire_min');
        $presence = $request->query->get('presence');
        
        // Construction de la requête pour les postes
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
        
        // Récupération des profils utilisateurs (en supposant que les étudiants ont le rôle ROLE_ETUDIANT)
        $qbProfils = $em->getRepository(Utilisateur::class)->createQueryBuilder('u')
        ->where('u.type = :type')
        ->setParameter('type', 'ETUDIANT');
            
        // Vous pouvez ajouter des filtres supplémentaires pour les profils si nécessaire
        if ($searchTerm) {
            $qbProfils->andWhere('u.nom LIKE :searchTerm OR u.prenom LIKE :searchTerm OR u.decription LIKE :searchTerm')
                     ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
        
        $profils = $qbProfils->getQuery()->getResult();
        
        // Sinon, on affiche la page d'accueil classique
        return $this->render('home/index.html.twig', [
            'postes' => $postes,
            'profils' => $profils, // Ajout des profils à la vue
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
        
    
    #[Route('/offre/{id}', name: 'offre_details')]
    public function offreDetails(Poste $poste, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $candidature = new Candidature();
        $form = $this->createForm(CandidatureType::class, $candidature);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du CV
            $cvFile = $form->get('cvCandidature')->getData();
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $cvFileName = $safeFilename . '-' . uniqid() . '.' . $cvFile->guessExtension();
    
                try {
                    $cvFile->move(
                        $this->getParameter('uploads_directory'),
                        $cvFileName
                    );
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV.');
                    return $this->redirectToRoute('offre_details', ['id' => $poste->getId()]);
                }
    
                $candidature->setCvCandidature($cvFileName);
            }
    
            // Récupération manuelle de la motivation (champ non mappé)
            $motivation = $form->get('motivation')->getData();
            $candidature->setMotivation($motivation);
    
            // Lier l'utilisateur connecté et le poste à la candidature
            $candidature->setUtilisateur($this->getUser());
            $candidature->setPoste($poste);
    
            // Tu peux ajouter un état par défaut si nécessaire (optionnel)
            $candidature->setEtat(Etat::EN_ATTENTE);
    
            $em->persist($candidature);
            $em->flush();
    
            $this->addFlash('success', 'Votre candidature a été envoyée avec succès.');
            return $this->redirectToRoute('offre_details', ['id' => $poste->getId()]);
        }
    
        return $this->render('home/offre_details.html.twig', [
            'poste' => $poste,
            'form' => $form->createView(),
        ]);
    }
    
    
    #[Route('/profil/{id}', name: 'profil_details')]
    public function profilDetails(Utilisateur $utilisateur): Response
    {
        return $this->render('home/profil_details.html.twig', [
            'profil' => $utilisateur
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('home/mentions_legales.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('home/politique_confidentialite.html.twig');
    }


}