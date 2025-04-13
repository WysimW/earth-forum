<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un pseudo']),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le pseudo doit comporter au moins {{ limit }} caractères',
                        'maxMessage' => 'Le pseudo ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une adresse email']),
                    new Email(['message' => 'L\'adresse email n\'est pas valide']),
                ]
            ])
            ->add('avatar', UrlType::class, [
                'label' => 'URL de l\'avatar',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'Laissez vide pour utiliser l\'avatar par défaut'
            ])
            ->add('playerRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'label' => 'Rôles',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'role-checkbox-group'],
                'help' => 'Sélectionnez un ou plusieurs rôles pour cet utilisateur'
            ]);
            
        // Ajout du champ password uniquement lors de la création d'un nouvel utilisateur
        if ($options['is_creation']) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un mot de passe']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit comporter au moins {{ limit }} caractères',
                    ]),
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_creation' => false,
        ]);
    }
}