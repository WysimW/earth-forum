<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu du post',
                'attr' => ['class' => 'form-control', 'rows' => 6]
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'label' => 'Thread',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'group_by' => function($thread) {
                    return $thread->getForum() ? $thread->getForum()->getName() : 'Sans forum';
                }
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Auteur',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de message',
                'choices' => [
                    'Normal' => 'normal',
                    'Roleplay' => 'roleplay'
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'roleplay-toggle'],
                'data' => 'normal'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}