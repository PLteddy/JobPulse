<?php

// src/Form/PosteType.php
namespace App\Form;

use App\Entity\Poste;
use App\Enum\Contrat;
use App\Enum\Duree;
use App\Enum\Type_presence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PosteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('intitule', TextType::class)
            ->add('description', TextareaType::class)
            ->add('contrat_type', ChoiceType::class, [
                'choices' => Contrat::cases(),
                'choice_label' => 'value',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('domaine', TextType::class)
            ->add('localisation', TextType::class)
            ->add('profil_recherche', TextType::class)
            ->add('info_supp', TextType::class, ['required' => false])
            ->add('presentation_entreprise', TextareaType::class)
            ->add('contact', TextType::class)
            ->add('salaire', IntegerType::class)
            ->add('presence', ChoiceType::class, [
                'choices' => Type_presence::cases(),
                'choice_label' => 'value',
            ])
            ->add('duree', ChoiceType::class, [
                'choices' => Duree::cases(),
                'choice_label' => 'value',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('missions', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Poste::class,
        ]);
    }
}