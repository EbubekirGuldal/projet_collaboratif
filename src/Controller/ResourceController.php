<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Resource;
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
        $resource->setResourceStatus('Publié');
        $resource->setVisibilityStatus('public');
        $resource->setPublishedAt(new \DateTimeImmutable());
        $resource->setSharesCount(0);
        $resource->setLikesCount(0);

        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$resource->getPublishedAt()) {
                $resource->setPublishedAt(new \DateTimeImmutable());
            }

            if (!$resource->getResourceStatus()) {
                $resource->setResourceStatus('Publié');
            }

            if (!$resource->getVisibilityStatus()) {
                $resource->setVisibilityStatus('public');
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
}
