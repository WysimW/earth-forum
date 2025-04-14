<?php

namespace App\Form;

use App\Entity\Character;
use App\Entity\Post;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostRoleplayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'rows' => 8,
                    'class' => 'wysiwyg-editor'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un message']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Votre message doit faire au moins {{ limit }} caractères'
                    ])
                ]
            ]);
            
        // Si des personnages sont disponibles, ajouter le champ de sélection
        if (!empty($options['characters'])) {
            $builder->add('character', EntityType::class, [
                'label' => 'Personnage',
                'class' => Character::class,
                'choices' => $options['characters'],
                'choice_label' => 'name',
                'placeholder' => 'Choisissez un personnage',
                'required' => true,
                'help' => 'Quel personnage utiliser pour ce message ?',
                'attr' => [
                    'class' => 'character-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un personnage'])
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'characters' => [], // Liste des personnages disponibles
        ]);
    }
}