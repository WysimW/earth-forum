<?php

namespace App\Form;

use App\Entity\Character;
use App\Entity\CharacterRelation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;

class CharacterRelationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Type de relation',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir le type de relation']),
                ],
                'help' => 'Par exemple: ami, rival, parent, enfant, amoureux, etc.'
            ])
            ->add('sourceCharacter', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'name',
                'label' => 'Personnage source',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un personnage source']),
                ],
                'help' => 'Le personnage qui possède cette relation'
            ])
            ->add('targetCharacter', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'name',
                'label' => 'Personnage cible',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un personnage cible']),
                ],
                'help' => 'Le personnage avec lequel la relation est établie'
            ])
            ->add('intensity', ChoiceType::class, [
                'label' => 'Intensité',
                'choices' => [
                    'Faible' => 1,
                    'Modérée' => 2,
                    'Forte' => 3,
                    'Très forte' => 4,
                    'Extrême' => 5
                ],
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'help' => 'L\'intensité de cette relation (optionnel)'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control html-editor', 'rows' => 5],
                'required' => false,
                'help' => 'Description détaillée de cette relation'
            ])
            ->add('isPublic', ChoiceType::class, [
                'label' => 'Visibilité',
                'choices' => [
                    'Publique' => true,
                    'Privée' => false
                ],
                'expanded' => true,
                'label_attr' => ['class' => 'radio-inline'],
                'help' => 'Les relations privées ne sont visibles que par les joueurs concernés et les administrateurs'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CharacterRelation::class,
        ]);
    }
}