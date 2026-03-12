<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthApiController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        private readonly EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
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
                    'token' => $token,
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'username' => $user->getUsername(),
                        'lastName' => $user->getLastName(),
                        'firstName' => $user->getFirstName(),
                        'isVerified' => $user->getIsVerified(),
                        'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
                        'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
                    ]
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

    #[Route(path: '/auth/register', methods: ["POST"])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $user = (new User())
                ->setEmail($data["email"])
                ->setUsername($data["username"])
                ->setLastConnexion(new \DateTimeImmutable());

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $data["password"]
            );

            if ($data["firstName"] != null) {
                $user->setFirstName($data["firstname"]);
            }

            if ($data["lastName"] != null) {
                $user->setLastName($data["lastName"]);
            }

            $user->setPassword($hashedPassword);
            $this->em->persist($user);
            $this->em->flush();

            $token = $this->jwtManager->create($user);

            return new JsonResponse([
                "status" => 200,
                "message" => "Compte crée avec succès",
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'lastName' => $user->getLastName(),
                    'firstName' => $user->getFirstName(),
                    'isVerified' => $user->getIsVerified(),
                    'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
                    'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
                ],
            ], 200);
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
