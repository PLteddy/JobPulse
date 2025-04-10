<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }


    /**
 * Trouve tous les utilisateurs avec qui l'utilisateur courant a échangé des messages
 */
public function findConversationPartners($user): array
{
    $qb = $this->createQueryBuilder('m');
    
    $sent = $qb->select('DISTINCT IDENTITY(m.toUser) as userId')
        ->where('m.fromUser = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getArrayResult();
    
    $received = $this->createQueryBuilder('m')
        ->select('DISTINCT IDENTITY(m.fromUser) as userId')
        ->where('m.toUser = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getArrayResult();
    
    // Fusionner les deux listes d'IDs
    $partnerIds = array_unique(array_merge(
        array_column($sent, 'userId'),
        array_column($received, 'userId')
    ));
    
    // Si aucun partenaire trouvé
    if (empty($partnerIds)) {
        return [];
    }
    
    // Obtenir la dernière date d'échange pour chaque partenaire
    $partners = [];
    foreach ($partnerIds as $partnerId) {
        $lastMessage = $this->createQueryBuilder('m')
            ->where('(m.fromUser = :user AND m.toUser = :partner) OR (m.fromUser = :partner AND m.toUser = :user)')
            ->setParameter('user', $user)
            ->setParameter('partner', $partnerId)
            ->orderBy('m.moment', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($lastMessage) {
            $partners[] = [
                'user' => ($lastMessage->getFromUser()->getId() == $partnerId) 
                    ? $lastMessage->getFromUser() : $lastMessage->getToUser(),
                'lastMessage' => $lastMessage
            ];
        }
    }
    
    // Trier par date du dernier message
    usort($partners, function($a, $b) {
        return $b['lastMessage']->getMoment() <=> $a['lastMessage']->getMoment();
    });
    
    return $partners;
}

/**
 * Trouve tous les messages entre deux utilisateurs
 */
public function findConversation($user1, $user2): array
{
    return $this->createQueryBuilder('m')
        ->where('(m.fromUser = :user1 AND m.toUser = :user2) OR (m.fromUser = :user2 AND m.toUser = :user1)')
        ->setParameter('user1', $user1)
        ->setParameter('user2', $user2)
        ->orderBy('m.moment', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Compte le nombre de messages non lus
 */
public function countUnreadMessages($user): int
{
    return $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.toUser = :user')
        ->andWhere('m.isRead = :isRead')
        ->setParameter('user', $user)
        ->setParameter('isRead', false)
        ->getQuery()
        ->getSingleScalarResult();
}
/**
 * Pour rechercher un utilisateur par son nom, prénom ou email
 */
public function findBySearch(string $search): array
{
    return $this->createQueryBuilder('u')
        ->where('LOWER(u.prenom) LIKE LOWER(:search) OR LOWER(u.nom) LIKE LOWER(:search) OR LOWER(u.email) LIKE LOWER(:search)')
        ->setParameter('search', '%' . $search . '%')
        ->orderBy('u.prenom', 'ASC')
        ->addOrderBy('u.nom', 'ASC')
        ->getQuery()
        ->getResult();
}


public function markAsRead($user, $partner): int
{
    return $this->createQueryBuilder('m')
        ->update()
        ->set('m.isRead', 'true')
        ->where('m.toUser = :user')
        ->andWhere('m.fromUser = :partner')
        ->andWhere('m.isRead = :isRead')
        ->setParameter('user', $user)
        ->setParameter('partner', $partner)
        ->setParameter('isRead', false)
        ->getQuery()
        ->execute();
}
//    /**
//     * @return Message[] Returns an array of Message objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Message
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
