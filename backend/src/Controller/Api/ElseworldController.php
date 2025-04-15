<?php

namespace App\Controller\Api;

use App\Entity\Elseworld;
use App\Entity\Character;
use App\Entity\Npc;
use App\Entity\Univers;
use App\Repository\ElseworldRepository;
use App\Repository\CharacterRepository;
use App\Repository\NpcRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajax/elseworlds')]
class ElseworldController extends AbstractController
{
    #[Route('/', name: 'api_elseworlds_list', methods: ['GET'])]
    public function getElseworlds(ElseworldRepository $elseworldRepository): JsonResponse
    {
        $elseworlds = $elseworldRepository->findAll();
        
        $data = array_map(function(Elseworld $elseworld) {
            return [
                'id' => $elseworld->getId(),
                'name' => $elseworld->getName(),
                'description' => $elseworld->getDescription(),
                'slug' => $elseworld->getSlug(),
                'parentUniverse' => [
                    'id' => $elseworld->getParentUniverse()->getId(),
                    'name' => $elseworld->getParentUniverse()->getName(),
                    'slug' => $elseworld->getParentUniverse()->getSlug()
                ],
                'banner' => $elseworld->getBanner(),
                'logo' => $elseworld->getLogo()
            ];
        }, $elseworlds);
        
        return new JsonResponse($data);
    }

    #[Route('/universe/{id}', name: 'api_elseworlds_by_universe', methods: ['GET'])]
    public function getElseworldsByUniverse(Univers $universe, ElseworldRepository $elseworldRepository): JsonResponse
    {
        $elseworlds = $elseworldRepository->findByParentUniverse($universe->getId());
        
        $data = array_map(function(Elseworld $elseworld) {
            return [
                'id' => $elseworld->getId(),
                'name' => $elseworld->getName(),
                'description' => $elseworld->getDescription(),
                'slug' => $elseworld->getSlug(),
                'banner' => $elseworld->getBanner(),
                'logo' => $elseworld->getLogo()
            ];
        }, $elseworlds);
        
        return new JsonResponse($data);
    }
    
    #[Route('/{id}/characters', name: 'api_elseworld_characters', methods: ['GET'])]
    public function getElseworldCharacters(Elseworld $elseworld, CharacterRepository $characterRepository): JsonResponse
    {
        $characters = $characterRepository->findBy([
            'elseworld' => $elseworld,
            'status' => 'validated'
        ]);
        
        $data = array_map(function(Character $character) {
            return [
                'id' => $character->getId(),
                'name' => $character->getName(),
                'slug' => $character->getSlug(),
                'avatar' => $character->getAvatar()
            ];
        }, $characters);
        
        return new JsonResponse($data);
    }
    
    #[Route('/{id}/npcs', name: 'api_elseworld_npcs', methods: ['GET'])]
    public function getElseworldNpcs(Elseworld $elseworld, NpcRepository $npcRepository): JsonResponse
    {
        $npcs = $npcRepository->findBy([
            'elseworld' => $elseworld,
            'status' => 'validated'
        ]);
        
        $data = array_map(function(Npc $npc) {
            return [
                'id' => $npc->getId(),
                'name' => $npc->getName(),
                'slug' => $npc->getSlug(),
                'avatar' => $npc->getAvatar()
            ];
        }, $npcs);
        
        return new JsonResponse($data);
    }
    
    #[Route('/{id}', name: 'api_elseworld_detail', methods: ['GET'])]
    public function getElseworldDetail(Elseworld $elseworld): JsonResponse
    {
        $data = [
            'id' => $elseworld->getId(),
            'name' => $elseworld->getName(),
            'description' => $elseworld->getDescription(),
            'slug' => $elseworld->getSlug(),
            'parentUniverse' => [
                'id' => $elseworld->getParentUniverse()->getId(),
                'name' => $elseworld->getParentUniverse()->getName(),
                'slug' => $elseworld->getParentUniverse()->getSlug()
            ],
            'banner' => $elseworld->getBanner(),
            'logo' => $elseworld->getLogo(),
            'createdAt' => $elseworld->getCreatedAt() ? $elseworld->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updatedAt' => $elseworld->getUpdatedAt() ? $elseworld->getUpdatedAt()->format('Y-m-d H:i:s') : null
        ];
        
        return new JsonResponse($data);
    }

    #[Route('/universe/{id}/list', name: 'admin_api_universe_elseworlds', methods: ['GET'])]
    public function getUniverseElseworlds(Univers $universe): JsonResponse
    {
        $elseworlds = $universe->getElseworlds();
        
        $data = [];
        foreach ($elseworlds as $elseworld) {
            $data[] = [
                'id' => $elseworld->getId(),
                'name' => $elseworld->getName()
            ];
        }
        
        return new JsonResponse($data);
    }
} 