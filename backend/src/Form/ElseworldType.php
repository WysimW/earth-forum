<?php

namespace App\Form;

use App\Entity\Elseworld;
use App\Entity\Univers;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElseworldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom de l\'Elseworld'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Description de l\'Elseworld'
                ]
            ])
            ->add('parentUniverse', EntityType::class, [
                'class' => Univers::class,
                'choice_label' => 'name',
                'label' => 'Univers parent',
                'required' => true,
                'placeholder' => 'Sélectionnez un univers parent',
            ])
            ->add('banner', TextType::class, [
                'label' => 'Bannière (URL)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'URL de l\'image de bannière'
                ]
            ])
            ->add('logo', TextType::class, [
                'label' => 'Logo (URL)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'URL de l\'image du logo'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Elseworld::class,
        ]);
    }
} 