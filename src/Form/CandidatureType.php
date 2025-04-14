<?php
namespace App\Form;

use App\Entity\Candidature;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cvCandidature', FileType::class, [
                'label' => 'CV',
                'required' => true,
                'mapped' => false, // Non mappé à l'entité pour gérer l'upload manuellement
            ])
            ->add('motivation', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => false,
                'attr' => [
                    'maxlength' => 1000,
                    'placeholder' => 'Votre lettre de motivation...',
                ],
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
        ]);
    }
}