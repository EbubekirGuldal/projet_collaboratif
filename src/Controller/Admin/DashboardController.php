<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\ModerationLog;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\DashboardStatsRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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
        yield MenuItem::linkToCrud('Moderation', 'fas fa-user-tie', ModerationLog::class);
        yield MenuItem::section();
        yield MenuItem::linkToRoute('Quitter', 'fas fa-right-from-bracket', 'app_home');
        yield MenuItem::linkToCrud('TypeResource', 'fas fa-bootstrap', RessourceType::class);
        yield MenuItem::linkToCrud('Category', 'fas fa-group-layer', Category::class);
    }
}
