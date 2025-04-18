<?php

namespace App\Form;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Entity\Univers;
use App\Entity\Elseworld;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du forum',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Forum Important' => 'important',
                    'Forum Roleplay' => 'roleplay',
                    'Forum Hors-Roleplay' => 'hrp'
                ],
                'label' => 'Type de forum',
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'forum_type'
                ]
            ])
            ->add('banner', UrlType::class, [
                'label' => 'URL de la bannière',
                'attr' => ['class' => 'form-control'],
                'required' => false
            ])
            ->add('heroLogo', UrlType::class, [
                'label' => 'URL du logo héros',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'Petit logo qui apparaît sur le forum (généralement 100x100px)'
            ])
            ->add('category', EntityType::class, [
                'class' => ForumCategory::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'placeholder' => 'Sélectionner une catégorie (pour un forum principal)'
            ])
            ->add('universe', EntityType::class, [
                'class' => Univers::class,
                'choice_label' => 'name',
                'label' => 'Univers',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'placeholder' => 'Sélectionner un univers (optionnel)',
                'help' => 'Univers auquel ce forum est associé'
            ])
            ->add('elseworld', EntityType::class, [
                'class' => Elseworld::class,
                'choice_label' => 'name',
                'label' => 'Elseworld',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'placeholder' => 'Sélectionner un elseworld (optionnel)',
                'help' => 'Elseworld auquel ce forum est associé'
            ])
            ->add('parent', EntityType::class, [
                'class' => Forum::class,
                'choice_label' => 'name',
                'label' => 'Forum parent',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'placeholder' => 'Sélectionner un forum parent (pour un sous-forum)',
                'group_by' => function($forum) {
                    return $forum->getCategory() ? $forum->getCategory()->getName() : 'Sans catégorie';
                }
            ])
            ->add('isRoleplay', CheckboxType::class, [
                'label' => 'Forum de jeu de rôle',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'id' => 'forum_isRoleplay'
                ]
            ])
        ;
        
        // Ajouter un event listener pour synchroniser le type et isRoleplay
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            
            // Si le type est défini comme 'roleplay', définir isRoleplay à true
            if (isset($data['type']) && $data['type'] === 'roleplay') {
                $data['isRoleplay'] = true;
            }
            
            $event->setData($data);
        });
        
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $forum = $event->getData();
            
            // S'assurer que isRoleplay est cohérent avec le type après la soumission
            if ($forum->getType() === 'roleplay') {
                $forum->setIsRoleplay(true);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Forum::class,
        ]);
    }
}