<?php

namespace App\Controller;

use App\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Request $request, ResourceRepository $resourceRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'new'); // new | top

        if (!in_array($sort, ['new', 'top'], true)) {
            $sort = 'new';
        }

        $feedItems = $resourceRepository->findFeed($q ?: null, $sort, 30);

        return $this->render('home/index.html.twig', [
            'feedItems' => $feedItems,
            'q' => $q,
            'sort' => $sort,
        ]);
    }
}