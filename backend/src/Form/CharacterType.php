<?php

namespace App\Form;

use App\Entity\Character;
use App\Entity\Univers;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}