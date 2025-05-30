<?php
// src/Controller/InscriptionController.php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class InscriptionController extends AbstractController
{
    #[Route('/inscription/{type}', name: 'inscription', requirements: ['type' => 'entreprise|tuteur|etudiant'])]
public function inscription(
    string $type,
    Request $demande,
    EntityManagerInterface $gestionnaireEntites,
    UserPasswordHasherInterface $passwordHasher,
    UserAuthenticatorInterface $userAuthenticator,
    LoginFormAuthenticator $authenticator
): Response {
    if ($demande->isMethod('POST')) {
        $utilisateur = new Utilisateur();
        $utilisateur->setType(Type::fromString($type));
        $utilisateur->setEmail($demande->request->get('email'));
        $utilisateur->setPassword(
            $passwordHasher->hashPassword(
                $utilisateur,
                $demande->request->get('password')
            )
        );
        $utilisateur->setNom($demande->request->get('nom'));
        $utilisateur->setPrenom($demande->request->get('prenom', ''));

        if ($type === 'etudiant') {
            $cvFile = $demande->files->get('cv');
            if ($cvFile) {
                $cvFileName = uniqid() . '.' . $cvFile->guessExtension();
                $cvFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/cv', $cvFileName);
                $utilisateur->setCv($cvFileName);
            } else {
                $utilisateur->setCv(null);
            }
            $utilisateur->setAdresse($demande->request->get('ville'));
        }

        if ($type === 'entreprise') {
            $utilisateur->setSiret($demande->request->get('siret'));
            $utilisateur->setAdresse($demande->request->get('adresse'));
        }

        if ($type === 'tuteur') {
            $utilisateur->setEtablissement($demande->request->get('etablissement'));
            $utilisateur->setAdresse('');
        }

        $gestionnaireEntites->persist($utilisateur);
        $gestionnaireEntites->flush();

        return $userAuthenticator->authenticateUser(
            $utilisateur,
            $authenticator,
            $demande
        );
    }

    return $this->render('inscription.html.twig', [
        'type' => $type,
    ]);
}
}