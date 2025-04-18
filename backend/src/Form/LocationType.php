<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\Univers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du lieu',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un nom pour ce lieu']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control html-editor', 'rows' => 10],
                'required' => false,
            ])
            ->add('image', UrlType::class, [
                'label' => 'URL de l\'image',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'URL d\'une image représentant le lieu',
            ])
            ->add('parent', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'label' => 'Lieu parent',
                'required' => false,
                'placeholder' => 'Aucun (lieu principal)',
                'attr' => ['class' => 'form-select'],
                'help' => 'Si ce lieu est contenu dans un autre lieu (ex: une taverne dans une ville)',
            ])
            ->add('universe', EntityType::class, [
                'class' => Univers::class,
                'choice_label' => 'name',
                'label' => 'Univers',
                'required' => false,
                'placeholder' => 'Aucun (lieu global)',
                'attr' => ['class' => 'form-select'],
                'help' => 'À quel univers ce lieu appartient-il',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}