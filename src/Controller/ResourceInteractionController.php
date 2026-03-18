<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\User;
use App\Entity\UserResourceState;
use App\Repository\UserResourceStateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ResourceInteractionController extends AbstractController
{
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

    #[Route('/resource/{id}/like', name: 'resource_like', methods: ['POST'])]
    public function like(Resource $resource, EntityManagerInterface $em, UserResourceStateRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 403);

        $state = $this->getState($user, $resource, $repo, $em);

        $state->setIsLiked(!$state->isLiked());

        $resource->setLikesCount(
            $state->isLiked()
                ? $resource->getLikesCount() + 1
                : max(0, $resource->getLikesCount() - 1)
        );

        $em->flush();

        return new JsonResponse([
            'liked' => $state->isLiked(),
            'likes' => $resource->getLikesCount()
        ]);
    }

    #[Route('/resource/{id}/save', name: 'resource_save', methods: ['POST'])]
    public function save(Resource $resource, EntityManagerInterface $em, UserResourceStateRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 403);

        $state = $this->getState($user, $resource, $repo, $em);

        $state->setIsSaved(!$state->isSaved());

        $em->flush();

        return new JsonResponse(['saved' => $state->isSaved()]);
    }

    #[Route('/resource/{id}/exploit', name: 'resource_exploit', methods: ['POST'])]
    public function exploit(Resource $resource, EntityManagerInterface $em, UserResourceStateRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 403);

        $state = $this->getState($user, $resource, $repo, $em);

        $state->setIsExploited(!$state->isExploited());

        $em->flush();

        return new JsonResponse(['exploited' => $state->isExploited()]);
    }
}