<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Resource;
use App\Enum\ResourceStatus;
use App\Form\ResourceFormType;
use App\Form\ResourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResourceController extends AbstractController
{
    #[Route('/resource/new', name: 'resource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $resource = new Resource();
        $resource->setUser($this->getUser());
        $resource->setPublishedAt(new \DateTimeImmutable());
        $resource->setSharesCount(0);
        $resource->setLikesCount(0);
        $resource->setResourceStatus(ResourceStatus::PUBLIC);

        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$resource->getPublishedAt()) {
                $resource->setPublishedAt(new \DateTimeImmutable());
            }

            if ($resource->getResourceStatus() === ResourceStatus::PUBLIC && $resource->getPublishedAt() === null) {
                $resource->setPublishedAt(new \DateTimeImmutable());
            }

            $resource->setUser($this->getUser());

            $em->persist($resource);
            $em->flush();

            $this->addFlash('success', 'Ta ressource a bien été publiée.');
            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        return $this->render('resource/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/resource/{id}', name: 'resource_show', methods: ['GET', 'POST'])]
    public function show(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('ROLE_USER');

            $content = trim((string) $request->request->get('content', ''));

            if ($content === '') {
                $this->addFlash('danger', 'Le commentaire ne peut pas être vide.');
                return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
            }

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setResource($resource);
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté ✅');
            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        $comments = $resource->getComments();

        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
            'comments' => $comments,
        ]);
    }

    #[Route('/resource/{id}/edit', name: 'resource_edit', methods: ['GET', 'POST'])]
    public function edit(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if ($resource->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres ressources.');
        }

        $form = $this->createForm(ResourceFormType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($resource->getResourceStatus() === ResourceStatus::PUBLIC && $resource->getPublishedAt() === null) {
                $resource->setPublishedAt(new \DateTimeImmutable());
            }

            if ($resource->getResourceStatus() !== ResourceStatus::PUBLIC) {
                $resource->setPublishedAt(null);
            }

            $em->flush();

            $this->addFlash('success', 'La ressource a été mise à jour avec succès.');

            return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
        }

        return $this->render('resource/edit.html.twig', [
            'resource' => $resource,
            'form' => $form->createView(),
        ]);
    }
}