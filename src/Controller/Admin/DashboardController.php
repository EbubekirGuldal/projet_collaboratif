<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\ModerationLog;
use App\Entity\Resource;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
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
    }
}
