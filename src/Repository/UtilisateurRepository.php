<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use App\Enum\Type;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
    * Recherche des utilisateurs par terme de recherche et type
    */
    public function findBySearchTermAndType(string $searchTerm, Type $type): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.type = :type')
            ->andWhere('(u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search)')
            ->setParameter('type', $type)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Recherche des utilisateurs par nom, prénom ou email
     */
    public function findBySearch(string $search): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }




    //    /**
    //     * @return Utilisateur[] Returns an array of Utilisateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Utilisateur
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
