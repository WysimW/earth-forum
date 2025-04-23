<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RevokeSanctionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', TextareaType::class, [
                'label' => 'Motif de la révocation',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez expliquer le motif de la révocation',
                    ]),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Expliquez pourquoi vous révoquez cette sanction'
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