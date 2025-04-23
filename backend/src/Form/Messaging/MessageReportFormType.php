<?php

namespace App\Form\Messaging;

use App\Entity\Messaging\MessageReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageReportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Raison du signalement',
                'choices' => MessageReport::getReasonChoices(),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une raison',
                    ]),
                ],
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Détails (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Fournissez des détails supplémentaires sur ce signalement...'
                ],
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Les détails ne peuvent pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessageReport::class,
        ]);
    }
} 