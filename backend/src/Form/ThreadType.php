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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add('forum', EntityType::class, [
                'class' => Forum::class,
                'choice_label' => 'name',
                'label' => 'Forum',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'group_by' => function($forum) {
                    return $forum->getCategory() ? $forum->getCategory()->getName() : ($forum->getParent() ? 'Sous-forum de ' . $forum->getParent()->getName() : 'Autres');
                },
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un forum']),
                ]
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Auteur',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un auteur']),
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
            $builder
                ->add('type', ChoiceType::class, [
                    'label' => 'Type de thread',
                    'choices' => [
                        'Normal' => 'normal',
                        'Announcement' => 'announcement',
                    ],
                    'attr' => ['class' => 'form-select'],
                    'data' => 'normal'
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
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Thread::class,
            'is_roleplay' => false,
        ]);
    }
}