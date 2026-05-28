<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_customer_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Beruki');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkToRoute(
            'Clientes',
            'fa fa-users',
            'admin_customer_index'
        );

        yield MenuItem::linkToRoute(
            'Planes',
            'fa fa-list',
            'admin_plan_index'
        );

        yield MenuItem::linkToRoute(
            'Planes de clientes',
            'fa fa-user-tag',
            'admin_customer_plan_index'
        );

        yield MenuItem::linkToRoute(
            'Usuarios',
            'fa fa-user-shield',
            'admin_user_index'
        );

        yield MenuItem::linkToRoute(
            'Cortes de caja',
            'fa fa-cash-register',
            'admin_cash_cuts_pending'
        );
    }
}