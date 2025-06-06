<?php

namespace App\Twig;

use Twig\TwigFunction;
use Twig\TwigFilter;
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

    public function getFilters(): array
    {
        return [
            new TwigFilter('decode_html', [$this, 'decodeHtml'], ['is_safe' => ['html']]),
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

    /**
     * Décode les entités HTML échappées tout en gardant la sécurité
     */
    public function decodeHtml(string $content): string
    {
        // Décoder seulement les entités HTML de base pour permettre l'affichage du HTML
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Liste des balises autorisées pour le roleplay
        $allowedTags = '<p><br><strong><em><span><div><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img>';
        
        // Nettoyer le contenu en ne gardant que les balises autorisées
        $content = strip_tags($content, $allowedTags);
        
        // Nettoyer les attributs dangereux (garder seulement style, class, href, src, alt)
        $content = preg_replace('/(<[^>]+)(?:on\w+|javascript:|vbscript:|expression\(|data:(?!image))[^>]*/', '$1', $content);
        
        return $content;
    }
} 