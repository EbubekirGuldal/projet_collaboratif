<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserApiController extends AbstractController
{
    
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        private readonly EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ) {
        
        $this->passwordHasher = $passwordHasher;
    }

    #[Route(path: '/api/change-password', methods: ["POST"])]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            /**@var User $user */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse([
                    "status" => 401,
                    "message" => "Utilisateur non authentifié"
                ], 401);
            }

            if (!password_verify($data["current_password"], $user->getPassword())) {
                return new JsonResponse([
                    "status" => 400,
                    "message" => "Mot de passe actuel incorrect"
                ], 400);
            }

            $newHashedPassword = $this->passwordHasher->hashPassword($user, $data["new_password"]);
            $user->setPassword($newHashedPassword);

            $this->em->flush();

            return new JsonResponse([
                "status" => 200,
                "message" => "Mot de passe changé avec succès"
            ], 200);    

        } catch (\Throwable $th) {
            return new JsonResponse([
                "status" => 500,
                "message" => "Une erreur est survenue : " . $th->getMessage()
            ], 500);
        }
    }
}