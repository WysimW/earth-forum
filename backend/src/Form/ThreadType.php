<?php

namespace App\Form;

use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du thread',
                'attr' => ['class' => 'form-control']
            ])
            ->add('forum', EntityType::class, [
                'class' => Forum::class,
                'choice_label' => 'name',
                'label' => 'Forum',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'group_by' => function($forum) {
                    return $forum->getCategory() ? $forum->getCategory()->getName() : ($forum->getParent() ? 'Sous-forum de ' . $forum->getParent()->getName() : 'Autres');
                }
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Auteur',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Thread::class,
        ]);
    }
}