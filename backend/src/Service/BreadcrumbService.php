<?php
namespace App\Service;

use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\Faction;
use App\Entity\Univers;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BreadcrumbService
{   
    private UrlGeneratorInterface $urlGenerator;
    
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }
    
    /**
     * Generate breadcrumbs from an associative array
     * 
     * @param array $items Array of name => url pairs
     * @return array
     */
    public function generate(array $items): array
    {
        $breadcrumbs = [];
        foreach ($items as $name => $url) {
            $breadcrumbs[] = [
                'name' => $name,
                'url' => $url
            ];
        }
        return $breadcrumbs;
    }
    
    /**
     * Génère les fils d'Ariane pour une faction
     */
    public function generateForFaction(Faction $faction, array $additional = []): array
    {
        $univers = $faction->getUniverse();
        
        $breadcrumbs = [
            $univers->getName() => $this->urlGenerator->generate('app_univers_show', ['slug' => $univers->getSlug()]),
            'Factions' => $this->urlGenerator->generate('app_factions_by_universe', ['universeSlug' => $univers->getSlug()]),
            $faction->getName() => $this->urlGenerator->generate('app_faction_show', [
                'universeSlug' => $univers->getSlug(), 
                'factionSlug' => $faction->getSlug()
            ])
        ];
        
        // Ajouter les éléments supplémentaires
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->generate($breadcrumbs);
    }
    
    /**
     * Génère les fils d'Ariane pour un univers
     */
    public function generateForUniverse(Univers $univers, array $additional = []): array
    {
        $breadcrumbs = [
            $univers->getName() => $this->urlGenerator->generate('app_univers_show', ['slug' => $univers->getSlug()])
        ];
        
        // Ajouter les éléments supplémentaires
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->generate($breadcrumbs);
    }
    
    /**
     * Génère les fils d'Ariane pour un forum dans un univers
     */
    public function generateForForum(Univers $univers, Forum $forum, array $additional = []): array
    {
        $currentForum = $forum;
        $parentForums = [];
        
        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }
        
        $parentForums = array_reverse($parentForums);
        
        // Si le forum appartient à un elseworld, l'ajouter dans le breadcrumb
        if ($forum->getElseworld()) {
            $elseworld = $forum->getElseworld();
            $breadcrumbs['Elseworlds'] = $this->urlGenerator->generate('app_univers_elseworlds', ['slug' => $univers->getSlug()]);
            $breadcrumbs[$elseworld->getName()] = $this->urlGenerator->generate('app_elseworld_show', [
                'universeSlug' => $univers->getSlug(),
                'elseworldSlug' => $elseworld->getSlug()
            ]);
        } else {
            $breadcrumbs[$univers->getName()] = $this->urlGenerator->generate('app_univers_forums', ['slug' => $univers->getSlug()]);
        }
        
        foreach ($parentForums as $parentForum) {
            $breadcrumbs[$parentForum->getName()] = $this->urlGenerator->generate('app_forum_show', [
                'universeSlug' => $univers->getSlug(),
                'id' => $parentForum->getId()
            ]);
        }
        
        $breadcrumbs[$forum->getName()] = $this->urlGenerator->generate('app_forum_show', [
            'universeSlug' => $univers->getSlug(),
            'id' => $forum->getId()
        ]);
        
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->generate($breadcrumbs);
    }

    public function generateBreadcrumbs(Forum $forum): array
    {
        $breadcrumbs = [];

        // Traverse the parent forums recursively to build the breadcrumb trail
        while ($forum !== null) {
            $breadcrumbs[] = [
                'name' => $forum->getName(),
                'url' => "/forum/{$forum->getId()}"
            ];
            $forum = $forum->getParent(); // Move to the parent forum
        }

    
        // The breadcrumbs need to be in the correct order, so we reverse the array
        return array_reverse($breadcrumbs);
    }

    /**
     * Génère les fils d'Ariane pour un thread dans un univers
     */
    public function generateForThread(Univers $univers, Thread $thread, array $additional = []): array
    {
        $forum = $thread->getForum();
        
        // Construire d'abord les breadcrumbs du forum
        $currentForum = $forum;
        $parentForums = [];
        
        while ($parent = $currentForum->getParent()) {
            $parentForums[] = $parent;
            $currentForum = $parent;
        }
        
        $parentForums = array_reverse($parentForums);
        
        $breadcrumbs = [];
        
        // Si le forum appartient à un elseworld, l'ajouter dans le breadcrumb
        if ($forum->getElseworld()) {
            $elseworld = $forum->getElseworld();
            $breadcrumbs['Elseworlds'] = $this->urlGenerator->generate('app_univers_elseworlds', ['slug' => $univers->getSlug()]);
            $breadcrumbs[$elseworld->getName()] = $this->urlGenerator->generate('app_elseworld_show', [
                'universeSlug' => $univers->getSlug(),
                'elseworldSlug' => $elseworld->getSlug()
            ]);
        } else {
            $breadcrumbs[$univers->getName()] = $this->urlGenerator->generate('app_univers_forums', ['slug' => $univers->getSlug()]);
        }
        
        // Ajouter les forums parents
        foreach ($parentForums as $parentForum) {
            $breadcrumbs[$parentForum->getName()] = $this->urlGenerator->generate('app_forum_show', [
                'universeSlug' => $univers->getSlug(),
                'id' => $parentForum->getId()
            ]);
        }
        
        // Ajouter le forum courant
        $breadcrumbs[$forum->getName()] = $this->urlGenerator->generate('app_forum_show', [
            'universeSlug' => $univers->getSlug(),
            'id' => $forum->getId()
        ]);
        
        // Ajouter le thread courant
        $breadcrumbs[$thread->getTitle()] = $this->urlGenerator->generate('app_thread_show', [
            'universeSlug' => $univers->getSlug(),
            'id' => $thread->getId()
        ]);
        
        // Ajouter les éléments supplémentaires
        foreach ($additional as $name => $url) {
            $breadcrumbs[$name] = $url;
        }
        
        return $this->generate($breadcrumbs);
    }

    /**
     * @deprecated Utiliser generateForThread() à la place
     */
    public function generateBreadcrumbsForThread(Thread $thread): array
    {
        $breadcrumbs = [];

        // Get the forum that this thread belongs to
        $forum = $thread->getForum();

        // Build the breadcrumb trail for the forum
        $this->generateBreadcrumbs($forum, $breadcrumbs);

        // Finally, add the current thread
        $breadcrumbs[] = ['name' => $thread->getTitle(), 'url' => "/thread/{$thread->getId()}"];

        return $breadcrumbs;
    }
}
