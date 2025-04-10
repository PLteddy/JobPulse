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
    if (!$options['conversation_mode']) {
        $builder->add('toUser', EntityType::class, [
            'class' => Utilisateur::class,
            'choice_label' => function (Utilisateur $user) {
                return $user->getPrenom() . ' ' . $user->getNom();
            },
            'label' => 'Destinataire',
            'required' => true,
            'query_builder' => function ($er) use ($options) {
                $qb = $er->createQueryBuilder('u');
                if (!empty($options['filter_by_type'])) {
                    $qb->andWhere('u.type = :type')
                        ->setParameter('type', $options['filter_by_type']);
                }
                return $qb;
            }
        ]);
    }
    
    $builder->add('contenu', TextareaType::class, [ 
        'label' => 'Message',
        'attr' => [
            'maxlength' => 300,
            'rows' => $options['conversation_mode'] ? 2 : 5
        ]
    ]);
}

public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => \App\Entity\Message::class,
        'conversation_mode' => false,
        'filter_by_type' => null,
    ]);
}
}
