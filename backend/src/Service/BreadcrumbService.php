<?php
namespace App\Service;

use App\Entity\Forum;
use App\Entity\Thread;

class BreadcrumbService
{   
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

        // Add the root or home breadcrumb
        $breadcrumbs[] = [
            'name' => 'Home',
            'url' => '/'
        ];

        // The breadcrumbs need to be in the correct order, so we reverse the array
        return array_reverse($breadcrumbs);
    }

    public function generateBreadcrumbsForThread(Thread $thread): array
    {
        $breadcrumbs = [];

        // Add the home link
        $breadcrumbs[] = ['name' => 'Home', 'url' => '/'];

        // Get the forum that this thread belongs to
        $forum = $thread->getForum();

        // Build the breadcrumb trail for the forum
        $this->generateBreadcrumbs($forum, $breadcrumbs);

        // Finally, add the current thread
        $breadcrumbs[] = ['name' => $thread->getTitle(), 'url' => "/thread/{$thread->getId()}"];

        return $breadcrumbs;
    }
}
