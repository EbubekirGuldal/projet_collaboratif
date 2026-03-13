<?php

namespace App\Controller;

use App\Repository\RelationKindRepository;
use App\Repository\ResourceRepository;
use App\Repository\RessourceTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        Request $request,
        ResourceRepository $resourceRepository,
        RessourceTypeRepository $ressourceTypeRepository,
        RelationKindRepository $relationKindRepository
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'new');

        $resourceTypeRaw = trim((string) $request->query->get('resourceType', ''));
        $relationKindRaw = trim((string) $request->query->get('relationKind', ''));

        $resourceTypeId = ctype_digit($resourceTypeRaw) ? (int) $resourceTypeRaw : null;
        $relationKindId = ctype_digit($relationKindRaw) ? (int) $relationKindRaw : null;

        if (!in_array($sort, ['new', 'top'], true)) {
            $sort = 'new';
        }

        $feedItems = $resourceRepository->findFeed(
            $q !== '' ? $q : null,
            $sort,
            30,
            $resourceTypeId,
            $relationKindId
        );

        return $this->render('home/index.html.twig', [
            'feedItems' => $feedItems,
            'q' => $q,
            'sort' => $sort,
            'resourceTypeId' => $resourceTypeId,
            'relationKindId' => $relationKindId,
            'resourceTypes' => $ressourceTypeRepository->findBy([], ['label' => 'ASC']),
            'relationKinds' => $relationKindRepository->findBy([], ['name' => 'ASC']),
        ]);
    }
}