<?php

namespace App\Form;

use App\Entity\ForumCategory;
use App\Entity\CategoriesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ForumCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false
            ])
            ->add('homeOrder', NumberType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => ['class' => 'form-control'],
                'required' => true
            ])
            ->add('type', EntityType::class, [
                'class' => CategoriesType::class,
                'choice_label' => 'name',
                'label' => 'Type de catégorie',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumCategory::class,
        ]);
    }
}