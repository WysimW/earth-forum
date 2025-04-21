<?php

namespace App\Twig;

use Twig\TwigFunction;
use App\Repository\UniversRepository;
use Twig\Extension\AbstractExtension;
use App\Repository\CharacterRepository;
use Symfony\Bundle\SecurityBundle\Security;

class AppExtension extends AbstractExtension
{
    private $security;
    private $characterRepository;
    private $universRepository;

    public function __construct(
        Security $security, 
        CharacterRepository $characterRepository,
        UniversRepository $universRepository
    ) {
        $this->security = $security;
        $this->characterRepository = $characterRepository;
        $this->universRepository = $universRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_favorite_universes', [$this, 'getUserFavoriteUniverses']),
            // Garder les autres fonctions existantes ici
        ];
    }

    /**
     * Récupère les univers favoris de l'utilisateur (ceux où il a au moins un personnage)
     */
    public function getUserFavoriteUniverses()
    {
        $user = $this->security->getUser();
        
        // Si l'utilisateur n'est pas connecté, retourner une liste vide
        if (!$user) {
            return [];
        }
        
        // Récupérer tous les personnages de l'utilisateur
        $characters = $this->characterRepository->findBy(['user' => $user]);
        
        // Extraire les IDs d'univers uniques
        $universeIds = [];
        foreach ($characters as $character) {
            $universeId = $character->getUniverse()->getId();
            if (!in_array($universeId, $universeIds)) {
                $universeIds[] = $universeId;
            }
        }
        
        // Si l'utilisateur n'a pas de personnages, retourner une liste vide
        if (empty($universeIds)) {
            return [];
        }
        
        // Récupérer les univers correspondants
        $universes = $this->universRepository->findBy(['id' => $universeIds]);
        
        return $universes;
    }
} 