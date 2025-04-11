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
            ->add('bio', TextType::class, [
                'label' => 'Bio',
                'required' => false,
            ])
            ->add('photoProfil', FileType::class, [
                'label' => 'Télécharger une nouvelle photo de profil',
                'mapped' => false, // Non mappé à l'entité
                'required' => false, // Facultatif
            ])
            ->add('decription', TextType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('cv', FileType::class, [
                'label' => 'Télécharger un nouveau CV',
                'mapped' => false, // Non mappé à l'entité
                'required' => false, // Facultatif
            ])
            ->add('formation', TextType::class, [
                'label' => 'Formation',
                'required' => false,
            ])
            ->add('experience', TextareaType::class, [
                'label' => 'Expériences',
                'required' => false,
            ])
            ->add('contact', TextType::class, [
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