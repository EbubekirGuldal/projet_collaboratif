<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\ResourceLike;
use App\Entity\Share;
use App\Entity\User;
use App\Entity\UserResourceState;
use App\Repository\ResourceLikeRepository;
use App\Repository\UserResourceStateRepository;
use App\Security\ResourceAccessResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ResourceInteractionController extends AbstractController
{
    public function __construct(private readonly ResourceAccessResolver $resourceAccessResolver)
    {
    }

    private function getState(User $user, Resource $resource, UserResourceStateRepository $repo, EntityManagerInterface $em): UserResourceState
    {
        $state = $repo->findOne($user, $resource);

        if (!$state) {
            $state = new UserResourceState();
            $state->setUser($user);
            $state->setResource($resource);
            $em->persist($state);
        }

        return $state;
    }

    private function getVerifiedUserOrError(Resource $resource): User|JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        if (!$user->getIsVerified()) {
            return new JsonResponse(['error' => 'Email verification required'], 403);
        }

        if (!$this->resourceAccessResolver->canInteract($user, $resource)) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        return $user;
    }

    #[Route('/resource/{id}/like', name: 'resource_like', methods: ['POST'])]
    public function like(
        Resource $resource,
        EntityManagerInterface $em,
        UserResourceStateRepository $stateRepository,
        ResourceLikeRepository $resourceLikeRepository
    ): JsonResponse {
        $user = $this->getVerifiedUserOrError($resource);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $state = $this->getState($user, $resource, $stateRepository, $em);
        $existingLike = $resourceLikeRepository->findOneByUserAndResource($user, $resource);

        if ($existingLike) {
            $em->remove($existingLike);
            $state->setIsLiked(false);
        } else {
            $like = new ResourceLike();
            $like->setUser($user);
            $like->setResource($resource);
            $em->persist($like);
            $state->setIsLiked(true);
        }

        $resource->setLikesCount(
            $existingLike
                ? max(0, ($resource->getLikesCount() ?? 0) - 1)
                : (($resource->getLikesCount() ?? 0) + 1)
        );

        $em->flush();

        return new JsonResponse([
            'liked' => $state->isLiked(),
            'likes' => $resource->getLikesCount(),
        ]);
    }

    #[Route('/resource/{id}/save', name: 'resource_save', methods: ['POST'])]
    #[Route('/resource/{id}/favorite', name: 'resource_favorite', methods: ['POST'])]
    public function save(Resource $resource, EntityManagerInterface $em, UserResourceStateRepository $repo): JsonResponse
    {
        $user = $this->getVerifiedUserOrError($resource);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $state = $this->getState($user, $resource, $repo, $em);
        $saved = !$state->isSaved();
        $state->setIsSaved($saved);

        if ($saved) {
            $user->addFavorite($resource);
        } else {
            $user->removeFavorite($resource);
        }

        $em->flush();

        return new JsonResponse([
            'saved' => $saved,
            'favorited' => $saved,
        ]);
    }

    #[Route('/resource/{id}/exploit', name: 'resource_exploit', methods: ['POST'])]
    public function exploit(Resource $resource, EntityManagerInterface $em, UserResourceStateRepository $repo): JsonResponse
    {
        $user = $this->getVerifiedUserOrError($resource);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $state = $this->getState($user, $resource, $repo, $em);
        $state->setIsExploited(!$state->isExploited());

        $em->flush();

        return new JsonResponse(['exploited' => $state->isExploited()]);
    }

    #[Route('/resource/{id}/share', name: 'resource_share', methods: ['POST'])]
    public function share(Resource $resource, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getVerifiedUserOrError($resource);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $share = new Share();
        $share->setChannel('web');
        $em->persist($share);

        $resource->setSharesCount(($resource->getSharesCount() ?? 0) + 1);
        $em->flush();

        return new JsonResponse(['shares' => $resource->getSharesCount()]);
    }
}
