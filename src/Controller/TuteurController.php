<?php

namespace App\Controller;

use App\Entity\Poste;
use App\Entity\Utilisateur;
use App\Enum\Type;
use App\Repository\PosteRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tuteur')]
class TuteurController extends AbstractController
{
    #[Route('/', name: 'tuteur_dashboard')]
    public function index(PosteRepository $posteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        // Récupérer toutes les offres (postes) de toutes les entreprises
        $postes = $posteRepository->findAll();
        
        return $this->render('tuteur/index.html.twig', [
            'postes' => $postes,
        ]);
    }
    
    #[Route('/mes-enregistrements', name: 'tuteur_mes_enregistrements')]
    public function mesEnregistrements(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        // Récupérer l'utilisateur connecté et ses postes sauvegardés
        $user = $this->getUser();
        $postesSauvegardes = $user->getPostesSauvegardes();
        
        return $this->render('tuteur/mes_enregistrements.html.twig', [
            'postesSauvegardes' => $postesSauvegardes,
        ]);
    }
    
    #[Route('/sauvegarder-poste/{id}', name: 'tuteur_sauvegarder_poste')]
    public function sauvegarderPoste(
        Poste $poste, 
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        $user = $this->getUser();
        
        // Vérifier si déjà sauvegardé
        if ($user->hasPosteSauvegarde($poste)) {
            // Si oui, on le retire
            $user->removePosteSauvegarde($poste);
            $message = 'L\'offre a été retirée de vos enregistrements';
        } else {
            // Si non, on l'ajoute
            $user->addPosteSauvegarde($poste);
            $message = 'L\'offre a été enregistrée';
        }
        
        $entityManager->flush();
        $this->addFlash('success', $message);
        
        // Rediriger vers la page précédente ou la page d'accueil
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('tuteur_dashboard');
    }
    
    #[Route('/mes-etudiants', name: 'tuteur_mes_etudiants')]
    public function mesEtudiants(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        $user = $this->getUser();
        $etudiants = $user->getEtudiants();
        
        return $this->render('tuteur/mes_etudiants.html.twig', [
            'etudiants' => $etudiants
        ]);
    }
    
    #[Route('/rechercher-etudiants', name: 'tuteur_rechercher_etudiants')]
    public function rechercherEtudiants(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        $searchTerm = $request->query->get('q', '');
        $resultats = [];
        
        if ($searchTerm) {

            $resultats = $utilisateurRepository->findBySearchTermAndType(
                $searchTerm,
                Type::ETUDIANT
            );
        }
        
        return $this->render('tuteur/rechercher_etudiants.html.twig', [
            'searchTerm' => $searchTerm,
            'resultats' => $resultats
        ]);
    }
    
    #[Route('/ajouter-etudiant/{id}', name: 'tuteur_ajouter_etudiant')]
    public function ajouterEtudiant(
        Utilisateur $etudiant, 
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        // Vérifier que l'utilisateur est bien un étudiant
        if ($etudiant->getType() !== Type::ETUDIANT) {
            $this->addFlash('danger', 'Cet utilisateur n\'est pas un étudiant');
            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('tuteur_mes_etudiants');
        }
        
        $user = $this->getUser();
        
        // Vérifier si l'étudiant est déjà lié au tuteur
        if ($user->hasEtudiant($etudiant)) {
            $this->addFlash('info', 'Cet étudiant est déjà dans votre liste');
        } else {
            $user->addEtudiant($etudiant);
            $entityManager->flush();
            $this->addFlash('success', 'L\'étudiant a été ajouté à votre liste');
        }
        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('tuteur_mes_etudiants');
    }
    
    #[Route('/retirer-etudiant/{id}', name: 'tuteur_retirer_etudiant')]
    public function retirerEtudiant(
        Utilisateur $etudiant, 
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        $user = $this->getUser();
        
        if ($user->hasEtudiant($etudiant)) {
            $user->removeEtudiant($etudiant);
            $entityManager->flush();
            $this->addFlash('success', 'L\'étudiant a été retiré de votre liste');
        }
        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('tuteur_mes_etudiants');
    }
    
    #[Route('/profil-etudiant/{id}', name: 'tuteur_profil_etudiant')]
    public function profilEtudiant(Utilisateur $etudiant): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        // Vérifier que l'utilisateur est bien un étudiant
        if ($etudiant->getType() !== Type::ETUDIANT) {
            $this->addFlash('danger', 'Cet utilisateur n\'est pas un étudiant');
            return $this->redirectToRoute('tuteur_mes_etudiants');
        }
        
        // Vérifier que l'étudiant est bien lié au tuteur connecté
        $user = $this->getUser();
        if (!$user->hasEtudiant($etudiant)) {
            $this->addFlash('danger', 'Cet étudiant ne fait pas partie de votre liste');
            return $this->redirectToRoute('tuteur_mes_etudiants');
        }
        
        return $this->render('tuteur/profil_etudiant.html.twig', [
            'etudiant' => $etudiant
        ]);
    }
    
    #[Route('/partager-offre/{posteId}/{etudiantId}', name: 'tuteur_partager_offre')]
    public function partagerOffre(
        string $posteId,
        string $etudiantId,
        EntityManagerInterface $entityManager,
        PosteRepository $posteRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        $poste = $posteRepository->find($posteId);
        $etudiant = $utilisateurRepository->find($etudiantId);
        
        if (!$poste || !$etudiant) {
            $this->addFlash('danger', 'Offre ou étudiant introuvable');
            return $this->redirectToRoute('tuteur_mes_etudiants');
        }
        
        // Vérifier que l'étudiant est bien lié au tuteur connecté
        $user = $this->getUser();
        if (!$user->hasEtudiant($etudiant)) {
            $this->addFlash('danger', 'Cet étudiant ne fait pas partie de votre liste');
            return $this->redirectToRoute('tuteur_mes_etudiants');
        }
        
        // Créer un message pour partager l'offre
        $message = new \App\Entity\Message();
        $message->setFromUser($user);
        $message->setToUser($etudiant);
        //C LA QUIL FAUDRA CHANGER LES LIENS QUAND CE SERA EN LIGNE 
        $message->setContenu(
            "Je vous recommande cette offre : {$poste->getIntitule()} chez {$poste->getEntreprise()->getNom()}. " .
            "Vous pouvez la consulter à ce lien : /offre/{$poste->getId()}"
        );
        
        $entityManager->persist($message);
        $entityManager->flush();
        
        $this->addFlash('success', 'L\'offre a été partagée avec l\'étudiant');
        
        return $this->redirectToRoute('tuteur_profil_etudiant', ['id' => $etudiant->getId()]);
    }
}