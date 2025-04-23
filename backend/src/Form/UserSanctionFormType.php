<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserSanction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class UserSanctionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Utilisateur',
                'mapped' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de sanction',
                'choices' => UserSanction::getTypeChoices(),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un type de sanction',
                    ]),
                ],
            ])
            ->add('duration', ChoiceType::class, [
                'label' => 'Durée',
                'choices' => UserSanction::getDurationChoices(),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une durée',
                    ]),
                ],
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Motif de la sanction',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez expliquer le motif de la sanction',
                    ]),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Expliquez clairement la raison de cette sanction (visible par l\'utilisateur)'
                ]
            ])
            ->add('moderatorNotes', TextareaType::class, [
                'label' => 'Notes internes (optionnel)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Notes internes visibles uniquement par les modérateurs'
                ]
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