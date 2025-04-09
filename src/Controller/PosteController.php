<?php

namespace App\Controller;

use App\Entity\Poste;
use App\Repository\PosteRepository;
use App\Form\PosteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/entreprise/poste')]
class PosteController extends AbstractController
{
    #[Route('/', name: 'app_poste_index')]
    public function index(PosteRepository $posteRepository): Response
    {
        // Récupérer uniquement les postes de l'entreprise connectée
        $user = $this->getUser();
        $postes = $posteRepository->findBy(['entreprise' => $user]);
    
        return $this->render('poste/index.html.twig', [
            'postes' => $postes,
        ]);
    }

    #[Route('/new', name: 'app_poste_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $poste = new Poste();
        
        // Récupère l'utilisateur connecté (l'entreprise)
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté en tant qu\'entreprise pour créer une offre d\'emploi');
        }
        
        // Définit l'entreprise pour le nouveau Poste
        $poste->setEntreprise($user);
        
        $form = $this->createForm(PosteType::class, $poste);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sécurisation du nom de fichier
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }
                
                // Met à jour la propriété 'image' pour stocker le nom du fichier
                $poste->setImage($newFilename);
            }
            
            $entityManager->persist($poste);
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre a été créée avec succès');
            return $this->redirectToRoute('app_poste_index');
        }

        return $this->render('poste/new.html.twig', [
            'form' => $form->createView(),
            'poste' => $poste,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_poste_edit')]
    public function edit(Request $request, Poste $poste, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Vérifier si l'utilisateur est le propriétaire du poste
        if ($poste->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette offre');
        }
        
        $form = $this->createForm(PosteType::class, $poste);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sécurisation du nom de fichier
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    // Si une ancienne image existe, on pourrait la supprimer ici
                    if ($poste->getImage()) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$poste->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }
                
                // Met à jour la propriété 'image' pour stocker le nom du fichier
                $poste->setImage($newFilename);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre offre a été mise à jour avec succès');
            return $this->redirectToRoute('app_poste_index');
        }
        
        return $this->render('poste/edit.html.twig', [
            'form' => $form->createView(),
            'poste' => $poste,
        ]);
    }
    
    #[Route('/{id}', name: 'app_poste_delete', methods: ['POST'])]
    public function delete(Request $request, Poste $poste, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est le propriétaire du poste
        if ($poste->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette offre');
        }
        
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$poste->getId(), $token)) {
            // Suppression de l'image si elle existe
            if ($poste->getImage()) {
                $imagePath = $this->getParameter('images_directory').'/'.$poste->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($poste);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été supprimée avec succès');
        }
        
        return $this->redirectToRoute('app_poste_index');
    }
}