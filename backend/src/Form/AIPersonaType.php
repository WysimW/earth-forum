<?php

namespace App\Form;

use App\Entity\AIPersona;
use App\Entity\Character;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AIPersonaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du persona',
                'attr' => [
                    'placeholder' => 'Ex: Assistant IA de Gandalf'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Description générale du persona'
                ]
            ])
            ->add('character', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'name',
                'label' => 'Personnage associé',
                'placeholder' => 'Sélectionnez un personnage'
            ])
            ->add('personalityTraits', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'label' => 'Traits de personnalité',
                'attr' => [
                    'class' => 'collection-field'
                ]
            ])
            ->add('knowledge', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'label' => 'Connaissances',
                'attr' => [
                    'class' => 'collection-field'
                ]
            ])
            ->add('relationships', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'label' => 'Relations',
                'attr' => [
                    'class' => 'collection-field'
                ]
            ])
            ->add('goals', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'label' => 'Objectifs',
                'attr' => [
                    'class' => 'collection-field'
                ]
            ])
            ->add('speechPattern', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'label' => 'Style de langage',
                'attr' => [
                    'class' => 'collection-field'
                ]
            ])
            ->add('narrativeContext', TextareaType::class, [
                'label' => 'Contexte narratif',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Contexte narratif spécifique pour ce persona'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AIPersona::class,
        ]);
    }
} 