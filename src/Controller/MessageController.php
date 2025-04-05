<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
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
        
        // Corrige 'moment' en 'createdAt' si nécessaire (selon ton entité)
        $messages = $messageRepo->findBy(
            ['toUser' => $user],
            ['moment' => 'DESC'] // Garde 'moment' si c'est le nom dans ton entité
        );
    
        return $this->render('message/index.html.twig', [
            'messages' => $messages,
        ]);
    }
    
    #[Route('/new', name: '_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setFromUser($this->getUser());
            $message->setMoment(new \DateTime()); // Ajoute cette ligne
            $message->setIsRead(false); // Ajoute cette ligne
            
            $em->persist($message);
            $em->flush();
            
            return $this->redirectToRoute('app_message_index');
        }
        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

?>