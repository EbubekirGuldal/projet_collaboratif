<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\ModerationLog;
use App\Entity\Resource;
use App\Entity\User;
use App\Enum\CommentStatus;
use App\Enum\ResourceStatus;
use App\Form\ResourceFormType;
use App\Form\ResourceType;
use App\Security\ResourceAccessResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResourceController extends AbstractController
{
    public function __construct(private readonly ResourceAccessResolver $resourceAccessResolver)
    {
    }

    private function requireVerifiedUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getIsVerified()) {
            throw $this->createAccessDeniedException('Veuillez verifier votre compte avant d utiliser cette fonctionnalite.');
        }

        return $user;
    }

    #[Route('/resource/new', name: 'resource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->requireVerifiedUser();

        $resource = new Resource();
        $resource->setUser($user);
        $resource->setPublishedAt(null);
        $resource->setSharesCount(0);
        $resource->setLikesCount(0);
        $resource->setResourceStatus(ResourceStatus::PRIVATE);

        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($resource->getResourceStatus(), [ResourceStatus::PUBLIC, ResourceStatus::SHARED], true)) {
                $resource->setPublishedAt($resource->getPublishedAt() ?? new \DateTimeImmutable());
            } else {
                $resource->setPublishedAt(null);
            }

            $resource->setUser($user);

            $em->persist($resource);
            $em->flush();

            $this->addFlash('success', 'Votre ressource a bien ete enregistree.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        return $this->render('resource/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/resource/{id}', name: 'resource_show', methods: ['GET', 'POST'])]
    public function show(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser() instanceof User ? $this->getUser() : null;

        if (!$this->resourceAccessResolver->canView($user, $resource)) {
            throw $this->createAccessDeniedException('Cette ressource n est pas accessible.');
        }

        $canModerate = $this->resourceAccessResolver->canModerate($user);

        if ($request->isMethod('POST')) {
            $user = $this->requireVerifiedUser();

            if (!$this->resourceAccessResolver->canInteract($user, $resource)) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas commenter cette ressource.');
            }

            $content = trim((string) $request->request->get('content', ''));

            if ($content === '') {
                $this->addFlash('danger', 'Le commentaire ne peut pas etre vide.');

                return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
            }

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setResource($resource);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setStatus($canModerate ? CommentStatus::APPROVED : CommentStatus::PENDING);

            $em->persist($comment);
            $em->flush();

            $this->addFlash(
                'success',
                $canModerate
                    ? 'Commentaire ajoute.'
                    : 'Commentaire enregistre et en attente de moderation.'
            );

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        $comments = $resource->getComments()->filter(
            static function (Comment $comment) use ($canModerate, $user): bool {
                if ($comment->isApproved()) {
                    return true;
                }

                if ($canModerate) {
                    return true;
                }

                return $user !== null && $comment->getUser() === $user;
            }
        );

        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
            'comments' => $comments,
        ]);
    }

    #[Route('/resource/{id}/edit', name: 'resource_edit', methods: ['GET', 'POST'])]
    public function edit(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        if ($resource->getUser() !== $user && !$this->resourceAccessResolver->canManageCatalog($user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres ressources.');
        }

        $form = $this->createForm(ResourceFormType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($resource->getResourceStatus(), [ResourceStatus::PUBLIC, ResourceStatus::SHARED], true)) {
                $resource->setPublishedAt($resource->getPublishedAt() ?? new \DateTimeImmutable());
            } else {
                $resource->setPublishedAt(null);
            }

            $em->flush();

            $this->addFlash('success', 'La ressource a ete mise a jour avec succes.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        return $this->render('resource/edit.html.twig', [
            'resource' => $resource,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/resource/{id}/report', name: 'resource_report', methods: ['POST'])]
    public function report(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->requireVerifiedUser();

        if (!$this->resourceAccessResolver->canView($user, $resource)) {
            throw $this->createAccessDeniedException('Cette ressource n est pas accessible.');
        }

        if (!$this->isCsrfTokenValid('report_resource_'.$resource->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Requete invalide.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        $category = trim((string) $request->request->get('category', ''));
        $details = trim((string) $request->request->get('details', ''));

        if ($category === '') {
            $this->addFlash('warning', 'Veuillez choisir un motif de signalement.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        $log = new ModerationLog();
        $log->setTargetType('resource');
        $log->setAction('report');
        $log->setReason(trim($category.' '.$details));
        $log->setResource($resource);
        $log->setUser($user);

        $em->persist($log);
        $em->flush();

        $this->addFlash('success', 'Le signalement a bien ete transmis a la moderation.');

        return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
    }
}
