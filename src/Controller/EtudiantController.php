<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\EtudiantType;
use App\Entity\Poste;
use Symfony\Component\String\Slugger\SluggerInterface;



class EtudiantController extends AbstractController
{
    #[Route('/etudiant', name: 'etudiant_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher le tableau de bord étudiant
        return $this->render('etudiant/index.html.twig');
    }

    #[Route('/etudiant/candidatures/{filter}', name: 'etudiant_candidatures', defaults: ['filter' => 'tout'])]
public function candidatures(string $filter, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

    // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    // Construire la requête en fonction du filtre
    $queryBuilder = $em->getRepository(\App\Entity\Candidature::class)->createQueryBuilder('c')
        ->where('c.utilisateur = :user')
        ->setParameter('user', $user);

        if ($filter === 'En attente') {
            $queryBuilder->andWhere('c.etat = :etat')->setParameter('etat', 'En attente');
        } elseif ($filter === 'Accepte') {
            $queryBuilder->andWhere('c.etat = :etat')->setParameter('etat', 'Accepte');
        } elseif ($filter === 'Refuse') {
            $queryBuilder->andWhere('c.etat = :etat')->setParameter('etat', 'Refuse');
        }

    $candidatures = $queryBuilder->getQuery()->getResult();

    // Passer les candidatures et le filtre au template
    return $this->render('etudiant/candidatures.html.twig', [
        'candidatures' => $candidatures,
        'filter' => $filter,
    ]);
}

    #[Route('/etudiant/enregistrements', name: 'etudiant_enregistrements')]
    public function enregistrements(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');
    
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupérer les postes sauvegardés de l'utilisateur
        $postesSauvegardes = $user->getPostesSauvegardes();
    
        // Passer les postes sauvegardés au template
        return $this->render('etudiant/enregistrements.html.twig', [
            'postesSauvegardes' => $postesSauvegardes
        ]);
    }

    #[Route('/etudiant/tuteur', name: 'etudiant_tuteur')]
    public function tuteur(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Récupérer l'utilisateur (étudiant) connecté
        $etudiant = $this->getUser();

        // Récupérer les tuteurs associés à l'étudiant
        $tuteurs = $etudiant->getTuteurs();

        // Passer les tuteurs au template
        return $this->render('etudiant/tuteur.html.twig', [
            'tuteurs' => $tuteurs
        ]);
    }

    #[Route('/etudiant/retirer-tuteur/{id}', name: 'etudiant_retirer_tuteur')]
    public function retirerTuteur(
        Utilisateur $tuteur, 
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        $etudiant = $this->getUser();

        // Retirer le tuteur de la liste de l'étudiant
        $etudiant->removeTuteur($tuteur);

        $entityManager->flush();
        $this->addFlash('success', 'Le tuteur a été retiré de votre liste');

        return $this->redirectToRoute('etudiant_tuteur');
    }

    #[Route('/etudiant/profil-tuteur/{id}', name: 'etudiant_profil_tuteur')]
    public function profilTuteur(Utilisateur $tuteur): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Vérifier que l'utilisateur est bien le tuteur de l'étudiant connecté
        $etudiant = $this->getUser();
        if (!$etudiant->getTuteurs()->contains($tuteur)) {
            throw $this->createAccessDeniedException('Ce tuteur nest pas dans votre liste');
        }
    } 

    #[Route('/sauvegarder-poste/{id}', name: 'etudiant_sauvegarder_poste')]
    public function sauvegarderPoste(
        Poste $poste, 
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');
        
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
    

    #[Route('/etudiant/profil', name: 'etudiant_profil')]
    public function profil(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        // Logique pour afficher les informations sur le tuteur
        return $this->render('etudiant/profil.html.twig');
    }
    #[Route('/etudiant/profil/edit', name: 'etudiant_profil_edit')]
    public function editProfil(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');

        $user = $this->getUser();

         // Créez un formulaire pour modifier les informations du profil
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

            // Gestion du téléchargement du CV
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $cvFileName = $safeFilename . '-' . uniqid() . '.' . $cvFile->guessExtension();

                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cv',
                        $cvFileName
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV.');
                }

                // Met à jour la propriété cv de l'utilisateur
                $user->setCv($cvFileName);
            }

            // Sauvegarde des modifications dans la base de données
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('etudiant_profil');
        }

        return $this->render('etudiant/edit_profil.html.twig', [
            'form' => $form->createView(),
            'cvFileName' => $user->getCv(), 
        ]);
        }
    #[Route('/etudiant/profil/utilisateur/{id}', name: 'etudiant_profil_utilisateur')]
    public function afficherProfilUtilisateur(Utilisateur $utilisateur): Response
    {
        
        // Vérifie si l'utilisateur existe
        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }
        
        return $this->render('etudiant/profil.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
        }    
}
