<?php

namespace App\Form\Messaging;

use App\Entity\Messaging\Conversation;
use App\Entity\Univers;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConversationFormType extends AbstractType
{
    private TokenStorageInterface $tokenStorage;
    private UserRepository $userRepository;

    public function __construct(TokenStorageInterface $tokenStorage, UserRepository $userRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la conversation',
                'attr' => [
                    'placeholder' => 'Donnez un nom à cette conversation'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de conversation',
                'choices' => [
                    'Privée (entre deux personnes)' => Conversation::TYPE_PRIVATE,
                    'Groupe (plusieurs participants)' => Conversation::TYPE_GROUP,
                    'Publique (canal)' => Conversation::TYPE_PUBLIC,
                ],
                'expanded' => true,
                'multiple' => false,
                'disabled' => $isEdit // On ne peut pas changer le type après création
            ])
            ->add('universe', EntityType::class, [
                'class' => Univers::class,
                'choice_label' => 'name',
                'label' => 'Univers',
                'placeholder' => 'Choisissez un univers',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Description optionnelle de la conversation'
                ]
            ])
            ->add('icon', UrlType::class, [
                'label' => 'Icône (URL)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'URL d\'une image pour l\'icône (optionnel)'
                ]
            ]);
            
        // Si ce n'est pas une édition, ajouter le champ des participants
        if (!$isEdit) {
            /** @var User $currentUser */
            $currentUser = $this->tokenStorage->getToken()->getUser();
            
            $builder->add('participants', ChoiceType::class, [
                'label' => 'Participants',
                'choices' => $this->getUserChoices($currentUser),
                'choice_label' => function($choice, $key, $value) {
                    return $value;
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                    'data-placeholder' => 'Sélectionnez des participants'
                ],
                'mapped' => false
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conversation::class,
            'is_edit' => false
        ]);
    }
    
    /**
     * Récupère la liste des utilisateurs disponibles pour être ajoutés à une conversation
     */
    private function getUserChoices(User $currentUser): array
    {
        $users = $this->userRepository->findAll();
        $choices = [];
        
        foreach ($users as $user) {
            if ($user->getId() !== $currentUser->getId()) {
                $choices[$user->getPseudo()] = $user->getId();
            }
        }
        
        return $choices;
    }
} 