<?php

namespace App\Form\Admin;

use App\Entity\User;
use App\Entity\Character;
use App\Entity\Location;
use App\Form\CharacterType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AdminCharacterType extends CharacterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        
        // Ajouter le champ user avant le premier champ (name)
        $builder->add('user', EntityType::class, [
            'class' => User::class,
            'choice_label' => 'pseudo',
            'label' => 'Utilisateur',
            'required' => true,
            'placeholder' => 'Sélectionnez un utilisateur',
            'attr' => [
                'class' => 'form-select'
            ],
        ], [
            // Position du champ au début du formulaire
            'prepend' => true,
        ]);
        
        // Ajouter le champ status
        $builder->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Brouillon' => Character::STATUS_DRAFT,
                'En attente' => Character::STATUS_PENDING,
                'Validé' => Character::STATUS_VALIDATED,
                'Refusé' => Character::STATUS_REJECTED,
                'Abandonné' => Character::STATUS_ABANDONED,
                'En cours d\'édition' => Character::STATUS_EDITING,
            ],
            'required' => true,
        ]);
        
        // Ajouter le champ location s'il existe dans l'entité
        try {
            $builder->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'label' => 'Lieu',
                'required' => false,
                'placeholder' => 'Sélectionnez un lieu (optionnel)',
            ]);
        } catch (\Throwable $e) {
            // Le champ location n'est peut-être pas présent dans l'entité Character
            // Nous ignorons donc l'erreur
        }
        
        // Gérer les autres champs spécifiques si nécessaire
        if (!$builder->has('description')) {
            // Si le champ description n'existe pas déjà, essayons avec la biographie
            if ($builder->has('biography')) {
                // Rendre le champ biography accessible via "description" dans le formulaire
                $builder->add('description', TextareaType::class, [
                    'label' => 'Description',
                    'required' => false,
                    'mapped' => false,
                    'data' => $options['data']->getBiography(),
                    'attr' => [
                        'rows' => 10,
                        'class' => 'html-editor',
                    ],
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}