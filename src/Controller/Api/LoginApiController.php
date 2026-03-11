<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class LoginApiController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        private readonly EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->jwtManager = $jwtManager;
    }

    #[Route(path: '/auth/login', methods: ["POST"])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            /**@var User $user */
            $user = $this->em->getRepository(User::class)->findOneBy(["email" => $data["email"]]);

            if (!$user) {
                return new JsonResponse([
                    "status" => 400,
                    "message" => "Mot de passe ou email incorrect"
                ], 400);
            }

            if (password_verify($data["password"], $user->getPassword())) {

                if (!$user->isActive()) {
                    return new JsonResponse([
                        "status" => 400,
                        "message" => "Votre compte est désactivé, veuillez contacter Sevilog"
                    ], 400);
                }

                $token = $this->jwtManager->create($user);

                $user->setLastConnexion(new \DateTimeImmutable());

                $this->em->flush();

                return new JsonResponse([
                    'status' => 200,
                    'token' => $token
                ]);
            } else {
                return new JsonResponse([
                    "status" => 400,
                    "message" => "Mot de passe ou email incorrect"
                ], 400);
            }
        } catch (\Throwable $th) {
            return new JsonResponse([
                "status" => 500,
                "message" => "Erreur : " . $th->getMessage()
            ], 500);
        }
    }

    #[Route('/api/verify-token', methods: ["POST"])]
    public function verifyToken(): JsonResponse
    {
        try {
            /**@var User $user */
            $user = $this->getUser();
            $user->setLastConnexion(new \DateTimeImmutable());

            $this->em->flush();

            return new JsonResponse([
                "status" => 200,
                "message" => "Token valide"
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                "status" => 500,
                "message" => "Erreur : " . $th->getMessage()
            ], 500);
        }
    }
}
