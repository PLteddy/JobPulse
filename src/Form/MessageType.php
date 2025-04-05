<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('toUser', EntityType::class, [
            'class' => Utilisateur::class,
            'choice_label' => function (Utilisateur $user) {
                return $user->getPrenom() . ' ' . $user->getNom();
            },
            'label' => 'Destinataire',
            //'required' => true // Faut rendre le champ obligatoire mais j'ai pas encore fait
        ])
        ->add('contenu', TextareaType::class, [ 
            'label' => 'Message',
            'attr' => ['maxlength' => 300]
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           
            'data_class' => \App\Entity\Message::class,
        ]);
    }
}
