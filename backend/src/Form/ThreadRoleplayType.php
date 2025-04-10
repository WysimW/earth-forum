<?php

namespace App\Form;

use App\Entity\Character;
use App\Entity\Location;
use App\Entity\Thread;
use App\Entity\Univers;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ThreadRoleplayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un titre pour votre scène']),
                    new Length([
                        'min' => 5,
                        'max' => 150,
                        'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Premier message',
                'attr' => [
                    'rows' => 10,
                    'class' => 'wysiwyg-editor'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une description pour commencer votre scène']),
                    new Length([
                        'min' => 50,
                        'minMessage' => 'La description doit faire au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('location', EntityType::class, [
                'label' => 'Lieu',
                'class' => Location::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisissez un lieu',
                'required' => false,
                'help' => 'Lieu où se déroule cette scène'
            ])
            ->add('characterCreator', EntityType::class, [
                'label' => 'Personnage participant',
                'class' => Character::class,
                'choices' => $options['characters'],
                'choice_label' => 'name',
                'placeholder' => 'Choisissez un personnage',
                'required' => false,
                'help' => 'Quel personnage utilise-t-on pour débuter cette scène ?',
                'attr' => [
                    'class' => 'character-select'
                ]
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre maximum de participants',
                'required' => false,
                'data' => 0, // 0 = pas de limite
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 20,
                        'notInRangeMessage' => 'Le nombre de participants doit être compris entre {{ min }} et {{ max }}'
                    ])
                ],
                'help' => 'Nombre maximum de personnages pouvant participer (0 = illimité)'
            ])
            ->add('visibleToCharactersOnly', CheckboxType::class, [
                'label' => 'Visible uniquement par les personnages participants',
                'required' => false,
                'help' => 'Si activé, seuls les personnages participants pourront voir cette scène'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouvert' => 'open',
                    'Fermé' => 'closed',
                    'Archivé' => 'archived'
                ],
                'data' => 'open',
                'help' => 'Un thread fermé n\'accepte plus de nouveaux participants'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Thread::class,
            'characters' => [], // Liste des personnages de l'utilisateur
        ]);
    }
}