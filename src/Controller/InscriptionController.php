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

class InscriptionController extends AbstractController
{
    // Route pour afficher et traiter le formulaire d'inscription
    #[Route('/inscription/{type}', name: 'inscription', requirements: ['type' => 'entreprise|tuteur|etudiant'])]
    public function inscription(string $type, Request $demande, EntityManagerInterface $gestionnaireEntites): Response
    {
        // Si la méthode HTTP est POST, cela veut dire que le formulaire a été soumis
        if ($demande->isMethod('POST')) {
            // Création de l'utilisateur
            $utilisateur = new Utilisateur();

            // Hydratation de l'utilisateur avec les données soumises dans le formulaire
            $utilisateur->setType(Type::fromString($type));  // On associe le type (etudiant, entreprise, tuteur)
            $utilisateur->setEmail($demande->request->get('email'));
            $utilisateur->setPassword($demande->request->get('password'));  // Il faudra hasher ce mot de passe avec Symfony

            $utilisateur->setNom($demande->request->get('nom'));
            $utilisateur->setPrenom($demande->request->get('prenom', ''));  // 'prenom' peut être vide, donc une valeur par défaut

            // Ajoute les champs spécifiques en fonction du type
            if ($type === 'etudiant') {
                $cvFile = $demande->files->get('cv'); // Récupère le fichier CV
                    if ($cvFile) {
                        // Génère un nom unique pour le fichier et le déplace dans un dossier
                        $cvFileName = uniqid() . '.' . $cvFile->guessExtension();
                        $cvFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/cv', $cvFileName);
                        $utilisateur->setCv($cvFileName); // Définit le chemin du fichier dans l'entité
                    } else {
                        $utilisateur->setCv(null); // Définit une valeur par défaut si aucun fichier n'est téléchargé
                    }
                $utilisateur->setAdresse($demande->request->get('ville'));  // Ville de l'étudiant
            }

            if ($type === 'entreprise') {
                $utilisateur->setSiret($demande->request->get('siret'));  // SIRET pour entreprise
                $utilisateur->setAdresse($demande->request->get('adresse'));  // Adresse de l'entreprise
            }

            if ($type === 'tuteur') {
                $utilisateur->setEtablissement($demande->request->get('etablissement'));  // Etablissement pour le tuteur
            }

            // Persiste l'objet utilisateur dans la base de données
            $gestionnaireEntites->persist($utilisateur);
            $gestionnaireEntites->flush();  // Envoie l'objet dans la base de données

            // Redirection vers une page spécifique en fonction du type d'utilisateur
            if ($type === 'etudiant') {
                return $this->render('etudiant.html.twig', [
                    'utilisateur' => $utilisateur, // Passez les données nécessaires au fichier Twig
                ]);
            } elseif ($type === 'entreprise') {
                return $this->render('entreprise.html.twig', [
                    'utilisateur' => $utilisateur, // Passez les données nécessaires au fichier Twig
                ]);
            } elseif ($type === 'tuteur') {
                return $this->render('tuteur.html.twig', [
                    'utilisateur' => $utilisateur, // Passez les données nécessaires au fichier Twig
                ]);
            }
        }

        // Si la requête est un GET, on affiche juste le formulaire
        return $this->render('inscription.html.twig', [
            'type' => $type,  // On passe le type pour l'affichage dynamique du formulaire
        ]);
    }
}
?>