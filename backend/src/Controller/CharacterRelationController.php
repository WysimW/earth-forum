<?php

namespace App\Controller;

use App\Entity\Character;
use App\Entity\CharacterRelation;
use App\Repository\CharacterRelationRepository;
use App\Repository\CharacterRepository;
use App\Service\BreadcrumbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CharacterRelationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CharacterRepository $characterRepository,
        private CharacterRelationRepository $relationRepository,
        private BreadcrumbService $breadcrumbService
    ) {
    }

    #[Route('/characters/{id}/relationships', name: 'app_roleplay_character_relationships')]
    #[IsGranted('ROLE_USER')]
    public function relationships(Character $character): Response
    {
        // Vérifier si l'utilisateur est le propriétaire du personnage
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de gérer les relations de ce personnage.');
        }

        // Récupérer les relations existantes
        $relations = $this->relationRepository->findCharacterRelations($character);

        // Récupérer les personnages disponibles pour créer de nouvelles relations
        $availableCharacters = [];
        
        // Si le personnage a un univers, on récupère tous les personnages de cet univers
        if ($character->getUniverse()) {
            $availableCharacters = $this->characterRepository->findByUnivers($character->getUniverse());
        }
        
        // Enlever le personnage actuel de la liste des disponibles
        $availableCharacters = array_filter($availableCharacters, function($char) use ($character) {
            return $char->getId() !== $character->getId();
        });

        // Génération des breadcrumbs avec le service
        $breadcrumbsData = [
            'Accueil' => $this->generateUrl('app_roleplay'),
            'Mes Personnages' => $this->generateUrl('app_roleplay_characters'),
            $character->getName() => $this->generateUrl('app_roleplay_character_show', ['id' => $character->getId()]),
            'Relations' => $this->generateUrl('app_roleplay_character_relationships', ['id' => $character->getId()]),
        ];
        $breadcrumbs = $this->breadcrumbService->generate($breadcrumbsData);

        return $this->render('characters/relationships.html.twig', [
            'character' => $character,
            'relations' => $relations,
            'availableCharacters' => $availableCharacters,
            'relationTypes' => [
                'family' => 'Famille',
                'friend' => 'Ami(e)',
                'enemy' => 'Ennemi(e)',
                'ally' => 'Allié(e)',
                'rival' => 'Rival(e)',
                'mentor' => 'Mentor',
                'student' => 'Élève',
                'other' => 'Autre'
            ],
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/characters/{id}/relationships/add', name: 'app_roleplay_character_relationship_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addRelationship(Request $request, Character $character): Response
    {
        // Vérifier si l'utilisateur est le propriétaire du personnage
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de gérer les relations de ce personnage.');
        }

        $targetId = $request->request->get('target_character');
        $type = $request->request->get('type');
        $description = $request->request->get('description');

        $targetCharacter = $this->characterRepository->find($targetId);

        if (!$targetCharacter) {
            $this->addFlash('error', 'Le personnage cible n\'existe pas.');
            return $this->redirectToRoute('app_roleplay_character_relationships', ['id' => $character->getId()]);
        }

        // Vérifier que les personnages appartiennent au même univers
        if ($character->getUniverse() !== $targetCharacter->getUniverse()) {
            $this->addFlash('error', 'Les personnages doivent appartenir au même univers pour avoir une relation.');
            return $this->redirectToRoute('app_roleplay_character_relationships', ['id' => $character->getId()]);
        }

        $relation = new CharacterRelation();
        $relation->setSourceCharacter($character)
                ->setTargetCharacter($targetCharacter)
                ->setType($type)
                ->setDescription($description);

        $this->entityManager->persist($relation);
        $this->entityManager->flush();

        $this->addFlash('success', 'La relation a été ajoutée avec succès.');

        return $this->redirectToRoute('app_roleplay_character_relationships', ['id' => $character->getId()]);
    }

    #[Route('/character-relations/{id}/delete', name: 'app_roleplay_character_relationship_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteRelationship(Request $request, CharacterRelation $relation): Response
    {
        // Vérifier si l'utilisateur est le propriétaire d'un des personnages de la relation
        if ($relation->getSourceCharacter()->getUser() !== $this->getUser() && 
            $relation->getTargetCharacter()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer cette relation.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$relation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $characterId = $relation->getSourceCharacter()->getId();

        $this->entityManager->remove($relation);
        $this->entityManager->flush();

        $this->addFlash('success', 'La relation a été supprimée avec succès.');

        return $this->redirectToRoute('app_roleplay_character_relationships', ['id' => $characterId]);
    }
}