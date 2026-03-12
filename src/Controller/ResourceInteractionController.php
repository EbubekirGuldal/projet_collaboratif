<?php

namespace App\Controller;

use App\Entity\ModerationLog;
use App\Entity\Resource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        // Prototype simple de toggle (0 -> 1, 1 -> 0).
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
        // Anti-autoclicker simple : un incrément par session et par ressource.
        $session = $request->getSession();
        $key = 'shared_resource_' . $resource->getId();

        if ($session && $session->has($key)) {
            return new JsonResponse([
                'shares' => $resource->getSharesCount() ?? 0,
                'shared' => false,
                'message' => 'Déjà partagé dans cette session.',
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

        // Favoris persistés en base via la relation ManyToMany (User::favorites).
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

    #[Route('/resource/{id}/report', name: 'resource_report', methods: ['POST'])]
    public function report(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Connectez-vous pour signaler une ressource.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('report_resource_' . $resource->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'La demande de signalement est invalide.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        $category = trim((string) $request->request->get('category', ''));
        $details = trim((string) $request->request->get('details', ''));

        if ($category === '') {
            $this->addFlash('warning', 'Choisissez un motif de signalement.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        $reason = sprintf('Catégorie : %s', $category);
        if ($details !== '') {
            $reason .= sprintf("\nDétails : %s", $details);
        }

        $log = new ModerationLog();
        $log
            ->setTargetType('resource')
            ->setUser($user)
            ->setAction('report')
            ->setResource($resource)
            ->setReason($reason);

        $em->persist($log);
        $em->flush();

        $this->addFlash('success', 'Le signalement a bien été transmis à l’équipe de modération.');

        return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
    }
}
