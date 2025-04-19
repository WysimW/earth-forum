<?php

namespace App\Form;

use App\Entity\Faction;
use App\Entity\Forum;
use App\Entity\Location;
use App\Entity\Univers;
use App\Entity\User;
use App\Entity\Character;
use App\Entity\Npc;
use App\Entity\Thread;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class FactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5
                ],
            ])
            ->add('universe', EntityType::class, [
                'class' => Univers::class,
                'choice_label' => 'name',
                'label' => 'Univers',
                'placeholder' => 'Choisir un univers',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('alignment', ChoiceType::class, [
                'label' => 'Alignement',
                'required' => false,
                'choices' => [
                    'Super-héros' => 'hero',
                    'Super-vilains' => 'villain',
                    'Anti-héros' => 'antihero',
                    'Vigilante' => 'vigilante',
                    'Neutre' => 'neutral',
                    'Autre' => 'other',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('scope', ChoiceType::class, [
                'label' => 'Portée',
                'required' => false,
                'choices' => [
                    'Galaxie' => 'galaxy',
                    'International' => 'international',
                    'National' => 'national',
                    'Régional' => 'regional',
                    'Local' => 'local',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouvert aux inscriptions' => Faction::STATUS_OPEN,
                    'Fermé aux inscriptions' => Faction::STATUS_CLOSED,
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('objectives', TextareaType::class, [
                'label' => 'Objectifs',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ],
            ])
            ->add('logo', UrlType::class, [
                'label' => 'Logo (URL)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://exemple.com/logo.png'
                ],
                'help' => 'URL de l\'image du logo de la faction'
            ])
            ->add('icon', UrlType::class, [
                'label' => 'Icône (URL)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://exemple.com/icon.png'
                ],
                'help' => 'URL de l\'icône de la faction'
            ])
            ->add('headquarters', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'label' => 'Quartier général',
                'required' => false,
                'placeholder' => 'Choisir un lieu',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->orderBy('l.name', 'ASC');
                },
            ])
            ->add('headquartersDescription', TextareaType::class, [
                'label' => 'Description du quartier général',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ],
            ])
            ->add('founder', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Fondateur',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.pseudo', 'ASC');
                },
            ])
            ->add('subforum', EntityType::class, [
                'class' => Forum::class,
                'choice_label' => 'name',
                'label' => 'Forum associé',
                'required' => false,
                'placeholder' => 'Choisir un forum',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('f')
                        ->orderBy('f.name', 'ASC');
                },
            ])
            ->add('characters', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'name',
                'label' => 'Personnages membres',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Sélectionner des personnages'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.status = :status')
                        ->setParameter('status', Character::STATUS_VALIDATED)
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('npcs', EntityType::class, [
                'class' => Npc::class,
                'choice_label' => 'name',
                'label' => 'PNJ membres',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Sélectionner des PNJ'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('n')
                        ->orderBy('n.name', 'ASC');
                },
            ])
            ->add('scenes', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'label' => 'Scènes associées',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control select2',
                    'data-placeholder' => 'Sélectionner des scènes'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.type = :type')
                        ->setParameter('type', 'roleplay')
                        ->orderBy('t.title', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faction::class,
            'front_office' => false,
        ]);
        
        $resolver->setAllowedTypes('front_office', 'bool');
    }
} 