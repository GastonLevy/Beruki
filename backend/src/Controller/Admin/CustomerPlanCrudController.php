<?php

namespace App\Controller\Admin;

use App\Entity\CustomerPlan;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $queryBuilder
            ->join('entity.customer', 'customer')
            ->andWhere('customer.isArchived = :isArchived')
            ->setParameter('isArchived', false);

        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('customer', 'Cliente')
                ->setQueryBuilder(
                    fn (QueryBuilder $queryBuilder): QueryBuilder => $queryBuilder
                        ->andWhere('customer.isArchived = :isArchived')
                        ->setParameter('isArchived', false)
                ),

            AssociationField::new('plan', 'Plan')
                ->setQueryBuilder(
                    fn (QueryBuilder $queryBuilder): QueryBuilder => $queryBuilder
                        ->andWhere('plan.isActive = :isActive')
                        ->setParameter('isActive', true)
                ),

            DateTimeField::new('startedAt', 'Fecha de inicio'),

            BooleanField::new('isActive', 'Activo'),

            TextField::new('serviceIp', 'IP de servicio')
                ->setRequired(false)
                ->formatValue(static fn (?string $value): string => $value ?: '—'),

            TextField::new('macAddress', 'Dirección MAC')
                ->setRequired(false)
                ->formatValue(static fn (?string $value): string => $value ?: '—'),
        ];
    }
}
