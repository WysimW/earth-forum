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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du thread',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un titre pour le thread']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit comporter au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une description pour votre discussion']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'La description doit faire au moins {{ limit }} caractères'
                    ])
                ],
                'help' => 'Une brève description de la discussion, visible dans le résumé du thread'
            ])
            ->add('firstPostContent', TextareaType::class, [
                'label' => 'Contenu du premier message',
                'attr' => [
                    'rows' => 8,
                    'class' => 'wysiwyg-editor'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un contenu pour commencer votre discussion']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le premier message doit faire au moins {{ limit }} caractères'
                    ])
                ]
            ]);

        // Ajouter des champs supplémentaires si c'est un thread de roleplay
        if ($options['is_roleplay']) {
            $builder
                ->add('type', ChoiceType::class, [
                    'label' => 'Type de thread',
                    'choices' => [
                        'Normal' => 'normal',
                        'Roleplay' => 'roleplay',
                        'Annonce' => 'announcement',
                        'Fiche de personnage' => 'character_sheet'
                    ],
                    'attr' => ['class' => 'form-select'],
                    'data' => 'roleplay'
                ])
                ->add('status', ChoiceType::class, [
                    'label' => 'Statut',
                    'choices' => [
                        'Ouvert' => 'open',
                        'Fermé' => 'closed',
                        'Archivé' => 'archived'
                    ],
                    'attr' => ['class' => 'form-select'],
                    'data' => 'open'
                ])
                ->add('sticky', CheckboxType::class, [
                    'label' => 'Épingler en haut du forum',
                    'required' => false,
                    'attr' => ['class' => 'form-check-input']
                ])
                ->add('visibleToCharactersOnly', CheckboxType::class, [
                    'label' => 'Visible uniquement pour les personnages participants',
                    'required' => false,
                    'attr' => ['class' => 'form-check-input']
                ])
                ->add('maxParticipants', null, [
                    'label' => 'Nombre maximum de participants',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'min' => 1
                    ]
                ]);
        } else {
            // Pour les threads non-RP, type est accessible seulement aux admins
            if ($options['is_admin']) {
                $builder->add('type', ChoiceType::class, [
                    'label' => 'Type de thread',
                    'choices' => [
                        'Normal' => 'normal',
                        'Important' => 'important',
                    ],
                    'attr' => ['class' => 'form-select'],
                    'data' => 'normal'
                ]);
            } else {
                // Pour les utilisateurs normaux, le type est toujours "normal"
                $builder->add('type', HiddenType::class, [
                    'data' => 'normal'
                ]);
            }
            
            $builder
                ->add('status', ChoiceType::class, [
                    'label' => 'Statut',
                    'choices' => [
                        'Ouvert' => 'open',
                        'Fermé' => 'closed',
                        'Archivé' => 'archived'
                    ],
                    'attr' => ['class' => 'form-select'],
                    'data' => 'open'
                ])
                ->add('sticky', CheckboxType::class, [
                    'label' => 'Épingler en haut du forum',
                    'required' => false,
                    'attr' => ['class' => 'form-check-input']
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Thread::class,
            'is_roleplay' => false,
            'is_admin' => false,
        ]);
    }
}