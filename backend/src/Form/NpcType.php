<?php

namespace App\Form;

use App\Entity\Npc;
use App\Entity\User;
use App\Entity\Univers;
use App\Entity\Elseworld;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NpcType extends AbstractType
{   
    private $UserRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->UserRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
            ])
            ->add('pseudonyms', TextareaType::class, [
                'label' => 'Pseudonymes',
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Masculin' => 'male',
                    'Féminin' => 'female',
                    'Autre' => 'other',
                ],
                'required' => false,
            ])
            ->add('moralAffiliation', TextType::class, [
                'label' => 'Alignement moral',
                'required' => false,
            ])
            ->add('factions', TextareaType::class, [
                'label' => 'Factions',
                'required' => false,
            ])
            ->add('occupation', TextType::class, [
                'label' => 'Occupation',
                'required' => false,
            ])
            ->add('equipment', TextareaType::class, [
                'label' => 'Équipement',
                'required' => false,
            ])
            ->add('weaknesses', TextareaType::class, [
                'label' => 'Faiblesses',
                'required' => false,
            ])
            ->add('age', TextType::class, [
                'label' => 'Âge',
                'required' => false,
            ])
            ->add('universe', EntityType::class, [
                'class' => Univers::class,
                'choice_label' => 'name',
                'label' => 'Univers',
                'required' => true,
            ])
            ->add('elseworld', EntityType::class, [
                'class' => Elseworld::class,
                'choice_label' => 'name',
                'label' => 'Elseworld',
                'required' => false,
                'placeholder' => 'Sélectionnez un Elseworld (optionnel)',
                'help' => 'Elseworld (univers alternatif) auquel appartient ce PNJ'
            ])
            ->add('avatar', TextType::class, [
                'label' => 'Avatar (URL)',
                'required' => false,
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
            ])
            ->add('personality', TextareaType::class, [
                'label' => 'Personnalité',
                'required' => false,
            ])
            ->add('appearance', TextareaType::class, [
                'label' => 'Apparence',
                'required' => false,
            ])
            ->add('abilities', TextareaType::class, [
                'label' => 'Capacités',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Npc::class,
        ]);
    }

    private function getUserChoices(): array
    {
        $users = $this->UserRepository->findAll();
        return array_map(fn (User $user) => $user->getUsername(), $users);
    }
} 