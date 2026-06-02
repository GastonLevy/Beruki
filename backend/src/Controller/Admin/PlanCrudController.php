<?php

namespace App\Controller\Admin;

use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PlanCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

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

    public function configureActions(Actions $actions): Actions
    {
        $deactivate = Action::new('deactivatePlan', 'Desactivar')
            ->linkToCrudAction('deactivatePlan')
            ->displayIf(static fn (Plan $plan): bool => $plan->isActive() && $plan->hasCustomerPlans())
            ->setIcon('fas fa-ban');

        return $actions
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                static fn (Action $action): Action => $action
                    ->displayIf(static fn (Plan $plan): bool => !$plan->hasCustomerPlans())
            )
            ->update(
                Crud::PAGE_DETAIL,
                Action::DELETE,
                static fn (Action $action): Action => $action
                    ->displayIf(static fn (Plan $plan): bool => !$plan->hasCustomerPlans())
            )
            ->add(Crud::PAGE_INDEX, $deactivate)
            ->add(Crud::PAGE_DETAIL, $deactivate);
    }

    #[AdminRoute(path: '/deactivate-plan', name: 'deactivate_plan')]
    public function deactivatePlan(AdminContext $context): RedirectResponse
    {
        $plan = $context->getEntity()->getInstance();

        if (!$plan instanceof Plan) {
            return $this->redirectToRoute('admin_plan_index');
        }

        $plan->setIsActive(false);

        $this->entityManager->flush();

        $this->addFlash('success', 'Plan desactivado correctamente.');

        return $this->redirectToRoute('admin_plan_index');
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