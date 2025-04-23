<?php

namespace App\Form\Messaging;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ModerationActionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'Action',
                'choices' => [
                    'Approuver le signalement et supprimer le message' => 'approve',
                    'Rejeter le signalement' => 'reject',
                ],
                'expanded' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une action',
                    ]),
                ],
            ])
            ->add('moderationNotes', TextareaType::class, [
                'label' => 'Notes de modération (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Notes internes concernant cette décision...'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
} 