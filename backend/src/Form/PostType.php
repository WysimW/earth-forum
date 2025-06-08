<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Entity\Character;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'placeholder' => 'Écrivez votre message ici...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le contenu du message est requis'
                    ])
                ]
            ])
            ->add('quotedPost', EntityType::class, [
                'class' => Post::class,
                'choice_label' => 'content',
                'required' => false,
                'attr' => [
                    'class' => 'form-control d-none',
                    'id' => 'quoted_post_id'
                ],
                'label' => false
            ])
        ;

        // Ajouter le champ character si c'est un thread de roleplay
        if (isset($options['is_roleplay']) && $options['is_roleplay']) {
            $builder->add('character', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'name',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'label' => 'Personnage'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'is_roleplay' => false,
        ]);
    }
}