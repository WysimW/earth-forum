<?php

namespace App\Controller;

use App\Repository\ForumRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ForumRepository $forumRepository): Response
    {
        // Récupérer les forums parents triés par type (important > roleplay > hrp) puis par position
        $parentForums = $forumRepository->createQueryBuilder('f')
            ->where('f.parent IS NULL')
            ->orderBy('CASE f.type 
                WHEN \'important\' THEN 1 
                WHEN \'roleplay\' THEN 2 
                WHEN \'hrp\' THEN 3 
                ELSE 4 END', 'ASC')
            ->addOrderBy('f.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('forum/index.html.twig', [
            'parentForums' => $parentForums,
        ]);
    }

}