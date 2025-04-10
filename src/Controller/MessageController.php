<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
public function index(MessageRepository $messageRepo): Response
{
    $user = $this->getUser();
    
    // Récupérer toutes les personnes avec qui l'utilisateur a échangé des messages
    $conversations = $messageRepo->findConversationPartners($user);
    
    return $this->render('message/index.html.twig', [
        'conversations' => $conversations,
    ]);
}

#[Route('/conversation/{id}', name: '_conversation')]
public function conversation(
    ?Utilisateur $partner, 
    MessageRepository $messageRepo, 
    Request $request, 
    EntityManagerInterface $em
): Response {
    $user = $this->getUser();

    if (!$partner) {
        throw $this->createNotFoundException('Aucun utilisateur cible trouvé.');
    }

    $messages = $messageRepo->findConversation($user, $partner);

    // Marquer les messages non lus comme lus
    foreach ($messages as $message) {
        if ($message->getToUser() === $user && !$message->isRead()) {
            $message->setIsRead(true);
            $em->persist($message);
        }
    }
    $em->flush();

    // Créer un formulaire pour répondre
    $newMessage = new Message();
    $newMessage->setToUser($partner);
    $form = $this->createForm(MessageType::class, $newMessage, [
        'conversation_mode' => true
    ]);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $newMessage->setFromUser($user);
        $em->persist($newMessage);
        $em->flush();

        // Seulement rediriger si on est en route HTTP normale
        if ($request->isMethod('POST') && !$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('app_message_conversation', ['id' => $partner->getId()]);
        }
    }

    return $this->render('message/conversation.html.twig', [
        'messages' => $messages,
        'partner' => $partner,
        'form' => $form->createView()
    ]);
}

#[Route('/search', name: '_search', methods: ['GET'])]
public function search(Request $request, UtilisateurRepository $userRepo): Response
{
    $search = $request->query->get('q', '');
    $results = [];
    
    if (strlen($search) >= 2) {
        $results = $userRepo->findBySearch($search);
    }
    
    return $this->render('message/search.html.twig', [
        'search' => $search,
        'results' => $results
    ]);
}
#[Route('/new', name: '_new')]
public function new(Request $request, EntityManagerInterface $em, UtilisateurRepository $userRepo): Response
{
    $search = $request->query->get('q', '');
    $results = [];
    
    if (strlen($search) >= 2) {
        $results = $userRepo->findBySearch($search);
    }
    
    // Si un utilisateur est sélectionné via le paramètre to_user
    $toUserId = $request->query->get('to_user');
    $message = new Message();
    
    if ($toUserId) {
        $toUser = $userRepo->find($toUserId);
        if ($toUser) {
            $message->setToUser($toUser);
        }
    }
    
    $form = $this->createForm(MessageType::class, $message);
    
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $message->setFromUser($this->getUser());
        $message->setMoment(new \DateTime());
        $message->setIsRead(false);
        
        $em->persist($message);
        $em->flush();
        
        return $this->redirectToRoute('app_message_index');
    }
    
    return $this->render('message/new.html.twig', [
        'form' => $form->createView(),
        'search' => $search,
        'results' => $results
    ]);
}


}

?>