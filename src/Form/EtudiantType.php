<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
            ])
            ->add('photoProfil', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false, // Non mappé à l'entité
                'required' => false, // Facultatif
            ])
            ->add('decription', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                'maxlength' => 500, // Limite la saisie à 500 caractères
                ],
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV',
                'mapped' => false, // Non mappé à l'entité
                'required' => false, // Facultatif
            ])
            ->add('formation', TextareaType::class, [
                'label' => 'Formation',
                'required' => false,
            ])
            ->add('experience', TextareaType::class, [
                'label' => 'Expériences',
                'required' => false,
            ])
            ->add('contact', TextareaType::class, [
                'label' => 'Contact',
                'required' => false,
            ])
            ->add('plusSurMoi', TextareaType::class, [
                'label' => 'Plus sur moi',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class, // L'entité associée au formulaire
        ]);
    }
}