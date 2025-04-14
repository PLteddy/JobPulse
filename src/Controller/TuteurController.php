<?php

namespace App\Controller;

use App\Entity\Poste;
use App\Entity\Utilisateur;
use App\Enum\Type;
use App\Form\EtudiantType;
use App\Repository\PosteRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
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
    
    #[Route('/partager-offre-multiple/{posteId}', name: 'tuteur_partager_multiple')]
    public function partagerOffreMultiple(
        string $posteId,
        Request $request,
        EntityManagerInterface $entityManager,
        PosteRepository $posteRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');
        
        $poste = $posteRepository->find($posteId);
        if (!$poste) {
            $this->addFlash('danger', 'Offre introuvable');
            return $this->redirectToRoute('home');
        }
        
        $etudiantIds = $request->request->all('etudiants');
        if (empty($etudiantIds)) {
            $this->addFlash('danger', 'Aucun étudiant sélectionné');
            return $this->redirectToRoute('offre_details', ['id' => $posteId]);
        }
        
        $user = $this->getUser();
        $partagesTotaux = 0;
        
        foreach ($etudiantIds as $etudiantId) {
            $etudiant = $utilisateurRepository->find($etudiantId);
            
            if (!$etudiant || !$user->hasEtudiant($etudiant)) {
                continue; // Ignorer les étudiants invalides ou non liés
            }
            
            // Créer un message pour partager l'offre
            $message = new \App\Entity\Message();
            $message->setFromUser($user);
            $message->setToUser($etudiant);
            $message->setContenu(
                "Je vous recommande cette offre : {$poste->getIntitule()} chez {$poste->getEntreprise()->getNom()}. " .
                "Vous pouvez la consulter à ce lien : /offre/{$poste->getId()}"
            );
            
            $entityManager->persist($message);
            $partagesTotaux++;
        }
        
        $entityManager->flush();
        
        if ($partagesTotaux > 0) {
            $this->addFlash('success', "L'offre a été partagée avec {$partagesTotaux} étudiant(s)");
        } else {
            $this->addFlash('warning', "Aucun partage n'a pu être effectué");
        }
        
        // Rediriger vers la page de détails de l'offre
        return $this->redirectToRoute('offre_details', ['id' => $posteId]);
    }


    #[Route('/tuteur/profil', name: 'tuteur_profil')]
    public function profil(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');

        // Logique pour afficher les informations sur le tuteur
        return $this->render('tuteur/profil.html.twig');
    }
    #[Route('/tuteur/profil/edit', name: 'tuteur_profil_edit')]
    public function editProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TUTEUR');

        $user = $this->getUser();


        $form = $this->createForm(EtudiantType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du téléchargement de la photo de profil
            $photoFile = $form->get('photoProfil')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $photoFileName = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                        $photoFileName
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo de profil.');
                }

                // Met à jour la propriété photoProfil de l'utilisateur
                $user->setPhotoProfil($photoFileName);
            }

            // Sauvegarde des modifications dans la base de données
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('etudiant_profil');
        }

        return $this->render('tuteur/edit_profil.html.twig', [
            'form' => $form->createView(),
        ]);
        }
}