<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\ModerationLog;
use App\Entity\RelationKind;
use App\Entity\Resource;
use App\Entity\RessourceType;
use App\Entity\User;
use App\Repository\DashboardStatsRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly DashboardStatsRepository $dashboardStatsRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    public function index(): Response
    {
        $request = $this->requestStack->getMainRequest() ?? $this->requestStack->getCurrentRequest();
        $period = (string) ($request?->query->get('period', '30d'));
        $stats = $this->dashboardStatsRepository->getStats($period);

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

    #[Route('/admin/stats/export', name: 'admin_stats_export', methods: ['GET'])]
    public function exportStatistics(Request $request): Response
    {
        $period = (string) $request->query->get('period', '30d');
        $stats = $this->dashboardStatsRepository->getStats($period);
        $generatedAt = new \DateTimeImmutable();

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create statistics export.');
        }

        fwrite($handle, "\xEF\xBB\xBF");

        $this->writeCsvRow($handle, ['Section', 'Libelle', 'Valeur 1', 'Valeur 2', 'Valeur 3']);
        $this->writeCsvRow($handle, ['Meta', 'Periode active', $stats['selectedPeriodLabel']]);
        $this->writeCsvRow($handle, ['Meta', 'Export genere le', $generatedAt->format('Y-m-d H:i:s')]);
        $this->writeCsvRow($handle, []);

        $this->writeCsvRow($handle, ['Indicateurs', 'Ressources', (string) $stats['totalResources']]);
        $this->writeCsvRow($handle, ['Indicateurs', 'Utilisateurs actifs', (string) $stats['activeUsers']]);
        $this->writeCsvRow($handle, ['Indicateurs', 'Ressources exploitees', (string) $stats['exploitedResources']]);
        $this->writeCsvRow($handle, ['Indicateurs', 'Favoris', (string) $stats['favoritesCount']]);
        $this->writeCsvRow($handle, ['Indicateurs', 'Partages', (string) $stats['sharesCount']]);
        $this->writeCsvRow($handle, ['Indicateurs', 'Commentaires', (string) $stats['commentsCount']]);
        $this->writeCsvRow($handle, ['Indicateurs', 'Taux d exploitation', sprintf('%.1f%%', $stats['exploitationRate'])]);
        $this->writeCsvRow($handle, []);

        $this->writeCsvRow($handle, ['Insights', 'Libelle', 'Valeur', 'Tonalite']);
        foreach ($stats['quickInsights'] as $insight) {
            $this->writeCsvRow($handle, ['Insights', $insight['label'], $insight['value'], $insight['tone']]);
        }
        $this->writeCsvRow($handle, []);

        $this->writeCsvRow($handle, ['Repartition', 'Categorie', 'Total', 'Pourcentage']);
        foreach ($stats['categoryDistribution'] as $item) {
            $percent = $stats['distributionTotal'] > 0
                ? round(($item['total'] / $stats['distributionTotal']) * 100, 1)
                : 0.0;

            $this->writeCsvRow($handle, [
                'Repartition',
                $item['label'],
                (string) $item['total'],
                sprintf('%.1f%%', $percent),
            ]);
        }
        $this->writeCsvRow($handle, []);

        $this->writeCsvRow($handle, ['Top ressources', 'Titre', 'Partages', 'Commentaires', 'Score']);
        foreach ($stats['topResources'] as $resource) {
            $this->writeCsvRow($handle, [
                'Top ressources',
                $resource['title'],
                (string) $resource['shares'],
                (string) $resource['comments'],
                (string) $resource['engagementScore'],
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $filename = sprintf(
            'admin-statistiques-%s-%s.csv',
            $stats['selectedPeriod'],
            $generatedAt->format('Y-m-d')
        );

        $response = new Response($content !== false ? $content : '');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    #[Route('/admin/stats/export/report', name: 'admin_stats_report_export', methods: ['GET'])]
    public function exportStatisticsReport(Request $request): Response
    {
        $period = (string) $request->query->get('period', '30d');
        $stats = $this->dashboardStatsRepository->getStats($period);
        $generatedAt = new \DateTimeImmutable();
        $filename = sprintf(
            'admin-statistiques-%s-%s.html',
            $stats['selectedPeriod'],
            $generatedAt->format('Y-m-d')
        );

        $content = $this->renderView('admin/export_report.html.twig', [
            'stats' => $stats,
            'generatedAt' => $generatedAt,
        ]);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }


    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/easyadmin.css');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Ressources Relationnelles')
            ->renderContentMaximized()
            ->disableDarkMode();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateur', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Commentaire', 'fas fa-comment', Comment::class);
        yield MenuItem::linkToCrud('Ressource', 'fas fa-image', Resource::class);
        yield MenuItem::linkToCrud('Types de ressource', 'fas fa-tags', RessourceType::class);
        yield MenuItem::linkToCrud('Publics concernés', 'fas fa-users', RelationKind::class);
        yield MenuItem::linkToCrud('Modération', 'fas fa-user-tie', ModerationLog::class);
        yield MenuItem::section();
        yield MenuItem::linkToRoute('Quitter', 'fas fa-right-from-bracket', 'app_home');
    }

    private function writeCsvRow(mixed $handle, array $row): void
    {
        if (fputcsv($handle, $row, ';') === false) {
            throw new \RuntimeException('Unable to write statistics export.');
        }
    }
}
