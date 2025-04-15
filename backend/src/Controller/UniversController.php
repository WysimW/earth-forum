<?php

namespace App\Controller;

use App\Entity\Univers;
use App\Entity\Elseworld;
use App\Repository\UniversRepository;
use App\Repository\ElseworldRepository;
use App\Repository\ForumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\LastPostService;
use App\Service\ForumStatisticsService;

#[Route('/univers')]
class UniversController extends AbstractController
{
    #[Route('/', name: 'app_univers_index', methods: ['GET'])]
    public function index(UniversRepository $universRepository): Response
    {
        return $this->render('univers/index.html.twig', [
            'univers' => $universRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'app_univers_show', methods: ['GET'])]
    public function show(string $slug, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $slug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        return $this->render('univers/show.html.twig', [
            'univers' => $univers,
        ]);
    }

    #[Route('/{slug}/forums', name: 'app_univers_forums', methods: ['GET'])]
    public function forums(
        string $slug, 
        UniversRepository $universRepository, 
        ForumRepository $forumRepository,
        LastPostService $lastPostService,
        ForumStatisticsService $forumStatsService
    ): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $slug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        // Récupérer uniquement les forums parents (sans parent) de l'univers, triés par type et position
        $universeForums = $forumRepository->createQueryBuilder('f')
            ->where('f.parent IS NULL')
            ->andWhere('f.universe = :universe')
            ->setParameter('universe', $univers)
            ->orderBy('CASE f.type 
                WHEN \'important\' THEN 1 
                WHEN \'roleplay\' THEN 2 
                WHEN \'hrp\' THEN 3 
                ELSE 4 END', 'ASC')
            ->addOrderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Enrichir les forums parents avec les informations sur les derniers posts et les statistiques
        foreach ($universeForums as $forum) {
            // Ajouter les informations du dernier post
            $lastPostInfo = $lastPostService->getLastPostInfoForForum($forum->getId());
            $forum->lastPostInfo = $lastPostInfo;
            
            // Ajouter les statistiques cumulées
            $stats = $forumStatsService->getForumStats($forum->getId());
            $forum->stats = $stats;
            
            // Charger explicitement les sous-forums triés par type et position
            $subforums = $forumRepository->createQueryBuilder('sf')
                ->where('sf.parent = :parent')
                ->setParameter('parent', $forum)
                ->orderBy('CASE sf.type 
                    WHEN \'important\' THEN 1 
                    WHEN \'roleplay\' THEN 2 
                    WHEN \'hrp\' THEN 3 
                    ELSE 4 END', 'ASC')
                ->addOrderBy('sf.position', 'ASC')
                ->getQuery()
                ->getResult();
                
            // Assigner les sous-forums à une propriété publique
            $forum->subforums = $subforums;
        }
        
        // Récupérer tous les elseworlds de cet univers avec leurs forums
        $elseworlds = $univers->getElseworlds();
        
        return $this->render('univers/forums.html.twig', [
            'univers' => $univers,
            'universeForums' => $universeForums,
            'elseworlds' => $elseworlds
        ]);
    }

    #[Route('/{slug}/elseworlds', name: 'app_univers_elseworlds', methods: ['GET'])]
    public function elseworlds(string $slug, UniversRepository $universRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $slug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        return $this->render('univers/elseworlds.html.twig', [
            'univers' => $univers,
            'elseworlds' => $univers->getElseworlds(),
        ]);
    }

    #[Route('/{universeSlug}/elseworld/{elseworldSlug}', name: 'app_elseworld_show', methods: ['GET'])]
    public function showElseworld(string $universeSlug, string $elseworldSlug, UniversRepository $universRepository, ElseworldRepository $elseworldRepository): Response
    {
        $univers = $universRepository->findOneBy(['slug' => $universeSlug]);
        
        if (!$univers) {
            throw $this->createNotFoundException('L\'univers demandé n\'existe pas');
        }
        
        $elseworld = $elseworldRepository->findOneBy([
            'slug' => $elseworldSlug,
            'parentUniverse' => $univers
        ]);
        
        if (!$elseworld) {
            throw $this->createNotFoundException('L\'elseworld demandé n\'existe pas ou n\'appartient pas à cet univers');
        }
        
        return $this->render('elseworld/show.html.twig', [
            'univers' => $univers,
            'elseworld' => $elseworld,
        ]);
    }
} 