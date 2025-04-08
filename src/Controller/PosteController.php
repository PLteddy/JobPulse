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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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
            $entityManager->persist($poste);
            $entityManager->flush();

            return $this->redirectToRoute('app_poste_index');
        }

        return $this->render('poste/new.html.twig', [
            'form' => $form->createView(),
            'poste' => $poste,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_poste_edit')]
    public function edit(Request $request, Poste $poste, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est le propriétaire du poste
        if ($poste->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette offre');
        }
        
        $form = $this->createForm(PosteType::class, $poste);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
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
            $entityManager->remove($poste);
            $entityManager->flush();
        }
        
        return $this->redirectToRoute('app_poste_index');
    }
}