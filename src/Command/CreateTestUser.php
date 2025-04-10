<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Enum\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateTestUser extends Command
{
    protected static $defaultName = 'app:create-test-user';
    protected static $defaultDescription = 'Crée un utilisateur de test';

    private $entityManager;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new Utilisateur();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setType(Type::ENTREPRISE);
        $user->setSiret('123456789');
        $user->setRoles(['ROLE_ENTREPRISE']);
        
        // Ajoute ces lignes pour les champs obligatoires
        $user->setAdresse('Adresse test');
        $user->setFormation('Formation test');
        $user->setEtablissement('Établissement test');
        // Ajoute d'autres champs obligatoires si nécessaire
    
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        $output->writeln('Utilisateur test créé avec succès !');
        $output->writeln('Email: test@example.com');
        $output->writeln('Mot de passe: password');
    
        return Command::SUCCESS;
    }
}