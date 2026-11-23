<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back-office EasyAdmin pour le catalogue (protégé par access_control ^/admin → ROLE_ADMIN).
 * Les emprunts passent par EmpruntController pour appliquer les règles d'EmpruntService.
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('app_tableau_de_bord');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Gestion de la bibliothèque');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Tableau de bord', 'fa fa-chart-line', 'app_tableau_de_bord');
        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(LivreCrudController::class, 'Livres', 'fa fa-book');
        yield MenuItem::linkTo(AuteurCrudController::class, 'Auteurs', 'fa fa-user-pen');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-tags');
        yield MenuItem::linkTo(EditeurCrudController::class, 'Éditeurs', 'fa fa-building');
        yield MenuItem::section('Bibliothèque');
        yield MenuItem::linkToRoute('Emprunts', 'fa fa-right-left', 'app_emprunt_index');
        yield MenuItem::linkToRoute('Adhérents', 'fa fa-id-card', 'app_adherent_index');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'app_home');
        yield MenuItem::section('Exercices');
        yield MenuItem::linkTo(PersonneCrudController::class, 'Personnes', 'fa fa-users');
    }
}
