<?php

namespace App\EventListener;

use App\Entity\Post;
use App\Service\ForumStatisticsService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: Post::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Post::class)]
#[AsEntityListener(event: Events::postRemove, entity: Post::class)]
class PostEventListener
{
    private ForumStatisticsService $statsService;

    public function __construct(ForumStatisticsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function postPersist(Post $post, LifecycleEventArgs $event): void
    {
        $this->invalidateCache($post);
    }

    public function postUpdate(Post $post, LifecycleEventArgs $event): void
    {
        $this->invalidateCache($post);
    }

    public function postRemove(Post $post, LifecycleEventArgs $event): void
    {
        $this->invalidateCache($post);
    }

    private function invalidateCache(Post $post): void
    {
        try {
            if ($post->getThread() && $post->getThread()->getForum()) {
                $forum = $post->getThread()->getForum();
                $forumId = $forum->getId();
                
                // Vérifier si le forum existe toujours dans la base de données
                if ($forumId !== null) {
                    $this->statsService->invalidateForumStats($forumId);
                    
                    // Si le forum a un parent, invalider aussi le cache du parent
                    $parent = $forum->getParent();
                    if ($parent && $parent->getId() !== null) {
                        $this->statsService->invalidateForumStats($parent->getId());
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs lors de la suppression en cascade
            // car le forum et ses relations sont en cours de suppression
        }
    }
} 