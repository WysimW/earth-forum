<?php

namespace App\Form\Messaging;

use App\Entity\Character;
use App\Entity\Messaging\Message;
use App\Entity\User;
use App\Repository\CharacterRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MessageFormType extends AbstractType
{
    private TokenStorageInterface $tokenStorage;
    private CharacterRepository $characterRepository;

    public function __construct(TokenStorageInterface $tokenStorage, CharacterRepository $characterRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->characterRepository = $characterRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Votre message...',
                    'rows' => 3,
                    'class' => 'message-input'
                ]
            ]);
            
        // Si l'utilisateur a des personnages, ajouter la possibilité de parler en tant que personnage
        /** @var User $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();
        $characters = $this->characterRepository->findBy(['user' => $currentUser, 'status' => Character::STATUS_VALIDATED]);
        
        if (!empty($characters) && !$isEdit) {
            $characterChoices = $this->getCharacterChoices($characters);
            
            if (!empty($characterChoices)) {
                $builder->add('character', ChoiceType::class, [
                    'label' => 'Parler en tant que',
                    'choices' => $characterChoices,
                    'required' => false,
                    'placeholder' => 'Parler en tant que vous-même',
                    'mapped' => false
                ]);
            }
        }
        
        // Si ce n'est pas une édition, permettre d'ajouter des fichiers
        if (!$isEdit) {
            $builder->add('files', FileType::class, [
                'label' => 'Pièces jointes',
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png,.gif,.pdf,.zip,.rar,.doc,.docx,.xls,.xlsx',
                    'class' => 'file-upload'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'is_edit' => false
        ]);
    }
    
    /**
     * Récupère la liste des personnages d'un utilisateur pour le formulaire
     */
    private function getCharacterChoices(array $characters): array
    {
        $choices = [];
        
        /** @var Character $character */
        foreach ($characters as $character) {
            $choices[$character->getName()] = $character->getId();
        }
        
        return $choices;
    }
} 