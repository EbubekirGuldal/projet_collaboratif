<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class ResourceInteractionController extends AbstractController
{
    #[Route('/resource/{id}/like', name: 'resource_like', methods: ['POST'])]
    public function toggleLike(Resource $resource, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $current = $resource->getLikesCount() ?? 0;

        if ($current > 0) {
            $resource->setLikesCount($current - 1);
            $liked = false;
        } else {
            $resource->setLikesCount($current + 1);
            $liked = true;
        }

        $em->flush();

        return new JsonResponse([
            'likes' => $resource->getLikesCount(),
            'liked' => $liked,
        ]);
    }

    #[Route('/resource/{id}/share', name: 'resource_share', methods: ['POST'])]
    public function share(Resource $resource, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $key = 'shared_resource_' . $resource->getId();

        if ($session && $session->has($key)) {
            return new JsonResponse([
                'shares' => $resource->getSharesCount() ?? 0,
                'shared' => false,
                'message' => 'Déjà partagé dans cette session.'
            ]);
        }

        $resource->setSharesCount(($resource->getSharesCount() ?? 0) + 1);

        if ($session) {
            $session->set($key, true);
        }

        $em->flush();

        return new JsonResponse([
            'shares' => $resource->getSharesCount(),
            'shared' => true,
        ]);
    }

    #[Route('/resource/{id}/favorite', name: 'resource_favorite', methods: ['POST'])]
    public function toggleFavorite(Resource $resource, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        if ($user->isFavorite($resource)) {
            $user->removeFavorite($resource);
            $favorited = false;
        } else {
            $user->addFavorite($resource);
            $favorited = true;
        }

        $em->flush();

        return new JsonResponse([
            'favorited' => $favorited,
        ]);
    }
}