<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Form\CustomerPlanType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Cliente')
            ->setEntityLabelInPlural('Clientes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Clientes')
            ->setPageTitle(Crud::PAGE_NEW, 'Crear cliente')
            ->setPageTitle(Crud::PAGE_EDIT, 'Editar cliente')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Detalle del cliente');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('fullName', 'Nombre'),

            TextField::new('subscriberNumber', 'Número de abonado'),

            EmailField::new('email', 'Email'),

            MoneyField::new('monthlyAmount', 'Mensualidad manual')
                ->setCurrency('ARS')
                ->setStoredAsCents(false)
                ->hideOnForm(),

            BooleanField::new('monthlyDebt', 'Debe mes'),

            CollectionField::new('customerPlans', 'Planes asignados')
                ->setEntryType(CustomerPlanType::class)
                ->allowAdd()
                ->allowDelete()
                ->onlyOnForms(),
        ];
    }
}