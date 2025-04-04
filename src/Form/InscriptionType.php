<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Enum\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On récupère le type passé en option
        $type = $options['type'];

        // Champs communs à tous les types
        $builder
            ->add('email', TextType::class, [
                'label' => 'E-mail',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
            ]);

        // Champs spécifiques à l'entreprise
        if ($type === Type::ENTREPRISE) {
            $builder
                ->add('nom', TextType::class, [
                    'label' => 'Nom de l\'entreprise',
                ])
                ->add('siret', TextType::class, [
                    'label' => 'Numéro de SIRET',
                ])
                ->add('adresse', TextType::class, [
                    'label' => 'Adresse de l\'entreprise',
                ]);
        }

        // Champs spécifiques à l'étudiant
        if ($type === Type::ETUDIANT) {
            $builder
                ->add('nom', TextType::class, [
                    'label' => 'Nom',
                ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom',
                ])
                ->add('ville', TextType::class, [
                    'label' => 'Ville',
                    'mapped' => false, // Non mappé à l'entité
                ])
                ->add('cv', FileType::class, [
                    'label' => 'Télécharger votre CV',
                    'mapped' => false, // Non mappé à l'entité
                    'required' => false,
                ]);
        }

        // Champs spécifiques au tuteur
        if ($type === Type::TUTEUR) {
            $builder
                ->add('nom', TextType::class, [
                    'label' => 'Nom',
                ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom',
                ])
                ->add('etablissement', TextType::class, [
                    'label' => 'Établissement',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class, // L'entité associée au formulaire
            'type' => null, // Type dynamique passé en option
        ]);
    }
}