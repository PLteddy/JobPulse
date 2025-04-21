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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File; 

class PosteType extends AbstractType
{
    //ça permet de définir les différents champs du formulaire
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //ajout d'un champ texte pour le titre de l'offre <- c'est comme ça qu'on rajoute un champ et après pour les autres on modifie juste le type genre Textearea ou ChoiceType
            ->add('intitule', TextType::class)
            ->add('description', TextareaType::class)
            ->add('domaine', TextType::class)
            ->add('localisation', TextType::class)
            ->add('profil_recherche', TextareaType::class)
            ->add('info_supp', TextareaType::class, ['required' => false])
            ->add('presentation_entreprise', TextareaType::class)
            ->add('contact', TextareaType::class)
            ->add('salaire', TextType::class)
            ->add('presence', ChoiceType::class, [
                'choices' => Type_presence::cases(),
                'choice_label' => 'value',
            ])
            ->add('duree', ChoiceType::class, [
                'choices' => Duree::cases(),
                'choice_label' => 'value',
                'expanded' => false,
            ])
            ->add('contrat_type', ChoiceType::class, [
                'choices' => Contrat::cases(),
                'choice_label' => 'value',
                'expanded' => false,
                'multiple' => false,
            ])
            
            ->add('missions', TextareaType::class)
            ->add('imageFile', FileType::class, [
                'label' => 'Image de l\'offre',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG ou PNG)',
                    ])
                ],
            ]);
    }
    /**
     * Configure les options du formulaire.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Poste::class,
        ]);
    }
}