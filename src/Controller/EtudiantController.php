<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\EtudiantType;


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
    #[Route('/etudiant/profil/edit', name: 'etudiant_profil_edit')]
    public function editProfil(Request $request, EntityManagerInterface $em): Response
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
            $photoFileName = uniqid() . '.' . $photoFile->guessExtension();
            $photoFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/images', $photoFileName);
            $user->setPhotoProfil($photoFileName);
        }
            // Gestion du téléchargement du CV
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $cvFileName = uniqid() . '.' . $cvFile->guessExtension();
                $cvFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/cv', $cvFileName);
                $user->setCv($cvFileName);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('etudiant_profil');
        }

        return $this->render('etudiant/edit_profil.html.twig', [
            'form' => $form->createView(),
        ]);
        }
}
