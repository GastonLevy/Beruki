<?php

namespace App\Controller\Admin;

use App\Entity\CustomerPlan;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class CustomerPlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerPlan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Plan de cliente')
            ->setEntityLabelInPlural('Planes de clientes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Planes de clientes')
            ->setPageTitle(Crud::PAGE_NEW, 'Asignar plan a cliente')
            ->setPageTitle(Crud::PAGE_EDIT, 'Editar plan de cliente')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Detalle del plan de cliente');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('customer', 'Cliente'),

            AssociationField::new('plan', 'Plan'),

            DateTimeField::new('startedAt', 'Fecha de inicio'),

            BooleanField::new('isActive', 'Activo'),
        ];
    }
}