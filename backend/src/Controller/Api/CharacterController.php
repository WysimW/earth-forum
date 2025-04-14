<?php

namespace App\Controller\Api;

use App\Entity\Character;
use App\Entity\Npc;
use App\Repository\CharacterRepository;
use App\Repository\NpcRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajax/characters')]
class CharacterController extends AbstractController
{
    #[Route('/available', name: 'api_characters_available', methods: ['GET'])]
    public function getAvailableCharacters(CharacterRepository $characterRepository, NpcRepository $npcRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // Récupérer les personnages validés de l'utilisateur
        $characters = $characterRepository->findBy([
            'user' => $user,
            'status' => 'validated'
        ]);

        // Récupérer les PNJ validés de l'utilisateur
        $npcs = $npcRepository->findBy([
            'user' => $user,
            'status' => 'validated'
        ]);

        // Formater les données
        $charactersData = array_map(function(Character $character) {
            return [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'type' => 'character',
                'style' => $character->getDialogueStyle() ? json_decode($character->getDialogueStyle(), true) : null
            ];
        }, $characters);

        $npcsData = array_map(function(Npc $npc) {
            return [
                'id' => $npc->getId(),
                'name' => $npc->getName(),
                'type' => 'npc',
                'style' => $npc->getDialogueStyle() ? json_decode($npc->getDialogueStyle(), true) : null
            ];
        }, $npcs);

        return new JsonResponse([
            'characters' => $charactersData,
            'npcs' => $npcsData
        ]);
    }
} 