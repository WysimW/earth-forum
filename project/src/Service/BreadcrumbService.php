<?php
namespace App\Service;

use App\Entity\Forum;

class BreadcrumbService
{
    public function generateBreadcrumbs(Forum $forum): array
    {
        $breadcrumbs = [];

        // Traverse the parent forums recursively to build the breadcrumb trail
        while ($forum !== null) {
            $breadcrumbs[] = [
                'name' => $forum->getName(),
                'url' => "/forum/{$forum->getId()}"
            ];
            $forum = $forum->getForum(); // Move to the parent forum
        }

        // Add the root or home breadcrumb
        $breadcrumbs[] = [
            'name' => 'Home',
            'url' => '/'
        ];

        // The breadcrumbs need to be in the correct order, so we reverse the array
        return array_reverse($breadcrumbs);
    }
}
