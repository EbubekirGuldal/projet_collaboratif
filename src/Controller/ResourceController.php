<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResourceController extends AbstractController
{
    #[Route('/resource/{id}', name: 'resource_show', methods: ['GET', 'POST'])]
    public function show(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        // Ajout commentaire (simple et efficace pour le prototype)
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

        // Affichage page détail
        $comments = $resource->getComments();

        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
            'comments' => $comments,
        ]);
    }
}