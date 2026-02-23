<?php

namespace App\Controller\Admin;

use App\Repository\DashboardStatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatsController extends AbstractController
{
    #[Route('/admin/stats', name: 'admin_stats', methods: ['GET'])]
    public function index(Request $request, DashboardStatsRepository $dashboardStatsRepository): Response
    {
        $query = $request->query->all();
        $period = $query['routeParams']['period'] ?? $query['routeParams[period]'] ?? $query['period'] ?? null;

        if (!is_string($period) || $period === '') {
            $rawQuery = [];
            parse_str((string) $request->server->get('QUERY_STRING', ''), $rawQuery);
            $period = $rawQuery['routeParams']['period'] ?? $rawQuery['period'] ?? null;
        }

        if (!is_string($period) || $period === '') {
            $requestUri = (string) $request->server->get('REQUEST_URI', '');
            if (preg_match('/routeParams%5Bperiod%5D=([^&]+)/i', $requestUri, $matches) === 1) {
                $period = urldecode($matches[1]);
            } elseif (preg_match('/routeParams\\[period\\]=([^&]+)/i', $requestUri, $matches) === 1) {
                $period = urldecode($matches[1]);
            }
        }

        $period = is_string($period) && $period !== '' ? $period : '30d';
        $stats = $dashboardStatsRepository->getStats($period);

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'resourceCount' => $stats['totalResources'],
            'UserCount' => $stats['activeUsers'],
            'CommentCount' => $stats['commentsCount'],
            'resourceIsExploited' => $stats['exploitedResources'],
            'isFavorited' => $stats['favoritesCount'],
            'isShared' => $stats['sharesCount'],
        ]);
    }
}
