<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UtilisateurRepository; 
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MessageRepository;
use App\Entity\Message;
use App\Form\MessageType;

#[Route('/message', name: 'app_message')]
class MessageController extends AbstractController
{
    #[Route('/', name: '_index')]
    public function index(MessageRepository $messageRepo, Request $request): Response
    {
        $user = $this->getUser();
        
        $conversations = $messageRepo->findConversationPartners($user);
        
        if ($request->isXmlHttpRequest() && $request->query->get('list_only')) {
            return $this->render('message/_conversation_list.html.twig', [
                'conversations' => $conversations,
            ]);
        }
        
        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/conversation/{id<\d+>}', name: '_conversation')]
    public function conversation(
        int $id, 
        UtilisateurRepository $userRepo,
        MessageRepository $messageRepo, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $partner = $userRepo->find($id);
    
        if (!$partner) {
            return $this->handleError($request, 'Utilisateur non trouvé');
        }
    
        // Marquer les messages comme lus en une requête
        $messageRepo->markAsRead($user, $partner);
    
        // Gestion du formulaire
        $newMessage = (new Message())
            ->setToUser($partner)
            ->setFromUser($user);
            
        $form = $this->createForm(MessageType::class, $newMessage, [
            'action' => $this->generateUrl('app_message_conversation', ['id' => $id]),
            'conversation_mode' => true,
        ]);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($newMessage);
            $em->flush();
        
            if ($request->isXmlHttpRequest()) {
                // Retourne uniquement le nouveau message au format JSON
                return $this->json([
                    'success' => true,
                    'message' => [
                        'content' => $newMessage->getContenu(),
                        'time' => $newMessage->getMoment()->format('H:i'),
                        'isFromMe' => true
                    ],
                    'formHtml' => $this->renderView('message/_message_form.html.twig', [
                        'form' => $form->createView()
                    ])
                ]);
            }
    
            return $this->redirectToRoute('app_message_conversation', ['id' => $id]);
        }
    
        $messages = $messageRepo->findConversation($user, $partner);
    
        if ($request->isXmlHttpRequest()) {
            return $this->render('message/conversation.html.twig', [
                'messages' => $messages,
                'partner' => $partner,
                'form' => $form->createView()
            ]);
        }
    
        return $this->render('message/index.html.twig', [
            'conversations' => $messageRepo->findConversationPartners($user),
            'activeConversation' => [
                'messages' => $messages,
                'partner' => $partner,
                'form' => $form->createView()
            ]
        ]);
    }

    #[Route('/search', name: '_search', methods: ['GET'])]
    public function search(Request $request, UtilisateurRepository $userRepo): Response
    {
        $search = trim($request->query->get('q', ''));
        
        if (strlen($search) < 2) {
            return $this->render('message/_search_results.html.twig', [
                'search' => $search,
                'results' => []
            ]);
        }
        
        $results = $userRepo->createQueryBuilder('u')
            ->where('CONCAT(u.prenom, \' \', u.nom) LIKE :search')
            ->orWhere('u.prenom LIKE :search')
            ->orWhere('u.nom LIKE :search')
            ->setParameter('search', '%'.$search.'%')
            ->setMaxResults(10) // Limitez les résultats
            ->getQuery()
            ->getResult();
        
        return $this->render('message/_search_results.html.twig', [
            'search' => $search,
            'results' => $results
        ]);
    }

    #[Route('/new', name: '_new')]
    public function new(Request $request, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
    {
        $message = new Message();
        $toUserId = $request->query->getInt('to_user');
        
        if ($toUserId > 0) {
            $toUser = $userRepo->find($toUserId);
            if ($toUser) {
                $message->setToUser($toUser);
            }
        }
        
        $form = $this->createForm(MessageType::class, $message, [
            'action' => $this->generateUrl('app_message_new'),
            'conversation_mode' => false, 
        ]);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setFromUser($this->getUser());
            $em->persist($message);
            $em->flush();
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true, 
                    'toUserId' => $message->getToUser()->getId()
                ]);
            }
            
            return $this->redirectToRoute('app_message_conversation', ['id' => $message->getToUser()->getId()]);
        }
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('message/_message_form.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        
        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
            'conversation_mode' => false, 
        ]);
    }


    #[Route('/delete/{id<\d+>}', name: '_delete', methods: ['POST'])]
    public function deleteConversation(
        int $id, 
        UtilisateurRepository $userRepo,
        MessageRepository $messageRepo
    ): JsonResponse
    {
        $currentUser = $this->getUser();
        $partner = $userRepo->find($id);
        
        if (!$partner) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }
        
        $deletedCount = $messageRepo->deleteConversationMessages($currentUser, $partner);
        
        return $this->json([
            'success' => true, 
            'deletedCount' => $deletedCount
        ]);
    }
    #[Route('/count-unread', name: '_count_unread', methods: ['GET'])]
    public function countUnread(MessageRepository $messageRepo): JsonResponse
    {
        $user = $this->getUser();
        $count = $messageRepo->countUnreadMessages($user);
        
        return new JsonResponse(['count' => $count]);
    }
    #[Route('/messagerie/{id<\d+>}', name: 'messagerie')]
    public function messagerie(
        int $id,
        UtilisateurRepository $userRepo,
        MessageRepository $messageRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Réutilise la logique de la méthode conversation
        return $this->conversation($id, $userRepo, $messageRepo, $request, $em);
    }
}