<?php

namespace App\Controller\Api;

use App\Entity\Resource;
use App\Entity\User;
use App\Entity\UserLiked;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ResourceApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route(path: '/api/resources/{id}/toggle-like', methods: ["GET"])]
    public function login(int $id): JsonResponse
    {
        try {
            /**@var User $user */
            $user = $this->getUser();

            /**@var Resource $resource */
            $resource = $this->em->getRepository(Resource::class)->find($id);
            $userLiked = $this->em->getRepository(UserLiked::class)->findOneBy(["resource" => $resource, "user" => $user]);

            if ($userLiked) {
                $isLiked = false;
                $this->em->remove($userLiked);
            } else {
                $isLiked = true;
                $userLiked = (new UserLiked())
                    ->setResource($resource)
                    ->setUser($user);

                $this->em->persist($userLiked);
            }

            $likesCount = $resource->getLikesCount() + ($isLiked ? 1 : -1);

            $resource->setLikesCount($likesCount);

            $this->em->flush();

            return new JsonResponse([
                "status" => 200,
                "likesCount" => $likesCount
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                "status" => 500,
                "message" => "Erreur : " . $th->getMessage()
            ], 500);
        }
    }
}
