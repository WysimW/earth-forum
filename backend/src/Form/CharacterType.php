<?php

namespace App\Form;

use App\Entity\Character;
use App\Entity\Univers;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un nom pour votre personnage']),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le nom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['placeholder' => 'Prénom du personnage']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'attr' => ['placeholder' => 'Nom de famille du personnage']
            ])
            ->add('pseudonyms', TextareaType::class, [
                'label' => 'Pseudonymes',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Alias, noms de code, surnoms...'
                ]
            ])
            ->add('age', TextType::class, [
                'label' => 'Âge',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Âge du personnage (ex: 32, inconnu, millénaire...)'
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'âge ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'required' => false,
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Masculin' => 'Masculin',
                    'Féminin' => 'Féminin',
                    'Autre' => 'Autre'
                ]
            ])
            ->add('sexualOrientation', ChoiceType::class, [
                'label' => 'Orientation',
                'required' => false,
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Hétérosexuel(le)' => 'Hétérosexuel(le)',
                    'Homosexuel(le)' => 'Homosexuel(le)',
                    'Bisexuel(le)' => 'Bisexuel(le)',
                    'Pansexuel(le)' => 'Pansexuel(le)',
                    'Asexuel(le)' => 'Asexuel(le)',
                    'Autre' => 'Autre',
                    'Non spécifié' => 'Non spécifié'
                ]
            ])
            ->add('moralAffiliation', ChoiceType::class, [
                'label' => 'Affiliation morale',
                'required' => false,
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Super-héros' => 'Super-héros',
                    'Super-vilain' => 'Super-vilain',
                    'Anti-héros' => 'Anti-héros',
                    'Neutre' => 'Neutre',
                    'Vigilante' => 'Vigilante',
                    'Civil' => 'Civil',
                    'Autre' => 'Autre'
                ]
            ])
            ->add('factions', TextareaType::class, [
                'label' => 'Factions',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Justice League, Titans, Legion of Doom, Suicide Squad...'
                ]
            ])
            ->add('civilStatus', ChoiceType::class, [
                'label' => 'Statut civil',
                'required' => false,
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Célibataire' => 'Célibataire',
                    'En couple' => 'En couple',
                    'Marié(e)' => 'Marié(e)',
                    'Divorcé(e)' => 'Divorcé(e)',
                    'Veuf/Veuve' => 'Veuf/Veuve',
                    'Compliqué' => 'Compliqué',
                    'Autre' => 'Autre'
                ]
            ])
            ->add('occupation', TextType::class, [
                'label' => 'Travail/Occupation',
                'required' => false,
                'attr' => ['placeholder' => 'Profession, occupation principale...']
            ])
            ->add('avatar', UrlType::class, [
                'label' => 'URL de l\'avatar',
                'required' => false,
                'help' => 'URL d\'une image représentant votre personnage'
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'class' => 'wysiwyg-editor'
                ],
                'help' => 'Histoire, passé et description de votre personnage',
                'purify_html' => true,
                'purify_html_profile' => 'roleplay'
            ])
            ->add('universe', EntityType::class, [
                'label' => 'Univers',
                'class' => Univers::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Choisissez un univers (optionnel)',
                'help' => 'Univers auquel appartient votre personnage'
            ])
            ->add('personality', TextareaType::class, [
                'label' => 'Personnalité',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'wysiwyg-editor'
                ],
                'help' => 'Traits de caractère, comportement, attitude...',
                'purify_html' => true,
                'purify_html_profile' => 'roleplay'
            ])
            ->add('appearance', TextareaType::class, [
                'label' => 'Apparence',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'wysiwyg-editor'
                ],
                'help' => 'Description physique de votre personnage',
                'purify_html' => true,
                'purify_html_profile' => 'roleplay'
            ])
            ->add('abilities', TextareaType::class, [
                'label' => 'Capacités',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'wysiwyg-editor'
                ],
                'help' => 'Pouvoirs, compétences et talents spéciaux',
                'purify_html' => true,
                'purify_html_profile' => 'roleplay'
            ])
            ->add('equipment', TextareaType::class, [
                'label' => 'Équipements',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'wysiwyg-editor'
                ],
                'help' => 'Armes, gadgets, accessoires, véhicules...',
                'purify_html' => true,
                'purify_html_profile' => 'roleplay'
            ])
            ->add('weaknesses', TextareaType::class, [
                'label' => 'Faiblesses',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'wysiwyg-editor'
                ],
                'help' => 'Points faibles, vulnérabilités, limitations...',
                'purify_html' => true,
                'purify_html_profile' => 'roleplay'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}