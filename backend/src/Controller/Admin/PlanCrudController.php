<?php

namespace App\Controller\Admin;

use App\Entity\Plan;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Plan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Plan')
            ->setEntityLabelInPlural('Planes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Planes')
            ->setPageTitle(Crud::PAGE_NEW, 'Crear plan')
            ->setPageTitle(Crud::PAGE_EDIT, 'Editar plan')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Detalle del plan');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('name', 'Nombre'),

            TextareaField::new('description', 'Descripción')
                ->hideOnIndex(),

            MoneyField::new('monthlyPrice', 'Precio mensual')
                ->setCurrency('ARS')
                ->setStoredAsCents(false),

            BooleanField::new('isActive', 'Activo'),
        ];
    }
}