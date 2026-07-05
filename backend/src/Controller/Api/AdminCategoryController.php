<?php

namespace App\Controller\Api;

use App\Entity\CategoriesType;
use App\Entity\ForumCategory;
use App\Repository\CategoriesTypeRepository;
use App\Repository\ForumCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/admin/categories')]
class AdminCategoryController extends AbstractController
{
    public function __construct(
        private ForumCategoryRepository $categoryRepository,
        private CategoriesTypeRepository $categoriesTypeRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'api_admin_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $categories = $this->categoryRepository->createQueryBuilder('c')
            ->leftJoin('c.type', 't')
            ->addSelect('t')
            ->orderBy('c.homeOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'categories' => array_map(fn (ForumCategory $category) => $this->serializeCategory($category), $categories),
            'total' => count($categories),
        ]);
    }

    #[Route('/meta', name: 'api_admin_categories_meta', methods: ['GET'])]
    public function meta(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $types = $this->categoriesTypeRepository->findBy([], ['name' => 'ASC']);

        return new JsonResponse([
            'types' => array_map(static fn (CategoriesType $type) => [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'slug' => $type->getSlug(),
            ], $types),
        ]);
    }

    #[Route('/update-home-orders', name: 'api_admin_categories_update_home_orders', methods: ['POST'])]
    public function updateHomeOrders(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        try {
            foreach ($data as $item) {
                if (!isset($item['id']) || !array_key_exists('home_order', $item)) {
                    continue;
                }

                $category = $this->categoryRepository->find($item['id']);
                if ($category) {
                    $category->setHomeOrder((int) $item['home_order']);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_admin_categories_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie introuvable'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializeCategory($category));
    }

    #[Route('', name: 'api_admin_categories_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return new JsonResponse(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        $typeId = $data['type_id'] ?? null;
        if (!$typeId) {
            return new JsonResponse(['error' => 'Le type de catégorie est requis'], Response::HTTP_BAD_REQUEST);
        }

        $type = $this->categoriesTypeRepository->find($typeId);
        if (!$type) {
            return new JsonResponse(['error' => 'Type de catégorie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $category = new ForumCategory();
        $category->setName($name);
        $category->setDescription($this->nullableString($data['description'] ?? null));
        $category->setType($type);
        $category->setSlug($this->generateUniqueSlug($name));
        $category->setHomeOrder($this->resolveHomeOrder($data['home_order'] ?? null));

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Catégorie créée avec succès',
            'category' => $this->serializeCategory($category),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_admin_categories_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                return new JsonResponse(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
            }
            $category->setName($name);
        }

        if (array_key_exists('description', $data)) {
            $category->setDescription($this->nullableString($data['description']));
        }

        if (array_key_exists('type_id', $data)) {
            if (!$data['type_id']) {
                return new JsonResponse(['error' => 'Le type de catégorie est requis'], Response::HTTP_BAD_REQUEST);
            }
            $type = $this->categoriesTypeRepository->find($data['type_id']);
            if (!$type) {
                return new JsonResponse(['error' => 'Type de catégorie introuvable'], Response::HTTP_NOT_FOUND);
            }
            $category->setType($type);
        }

        if (array_key_exists('home_order', $data)) {
            $category->setHomeOrder($data['home_order'] === null ? null : (int) $data['home_order']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Catégorie modifiée avec succès',
            'category' => $this->serializeCategory($category),
        ]);
    }

    #[Route('/{id}', name: 'api_admin_categories_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (count($category->getForums()) > 0) {
            return new JsonResponse([
                'error' => 'Impossible de supprimer cette catégorie car elle contient des forums',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Catégorie supprimée avec succès']);
    }

    private function serializeCategory(ForumCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'slug' => $category->getSlug(),
            'home_order' => $category->getHomeOrder(),
            'position' => $category->getPosition(),
            'type_id' => $category->getType()?->getId(),
            'type_name' => $category->getType()?->getName(),
            'forums_count' => count($category->getForums()),
        ];
    }

    private function resolveHomeOrder(mixed $homeOrder): int
    {
        if ($homeOrder !== null && $homeOrder !== '') {
            return (int) $homeOrder;
        }

        $maxHomeOrder = $this->categoryRepository->createQueryBuilder('c')
            ->select('MAX(c.homeOrder)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxHomeOrder) + 1;
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        if ($baseSlug === '') {
            $baseSlug = 'category';
        }

        $candidate = $baseSlug;
        $counter = 2;
        while ($this->categoryRepository->findOneBy(['slug' => $candidate]) !== null) {
            $candidate = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $candidate;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
