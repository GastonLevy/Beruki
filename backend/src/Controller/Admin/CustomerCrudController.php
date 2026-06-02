<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Form\CustomerPlanType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $showArchived = $this->isShowingArchived();

        return $crud
            ->setEntityLabelInSingular('Cliente')
            ->setEntityLabelInPlural($showArchived ? 'Clientes archivados' : 'Clientes')
            ->setPageTitle(Crud::PAGE_INDEX, $showArchived ? 'Clientes archivados' : 'Clientes')
            ->setPageTitle(Crud::PAGE_NEW, 'Crear cliente')
            ->setPageTitle(Crud::PAGE_EDIT, 'Editar cliente')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Detalle del cliente');
    }

    public function configureActions(Actions $actions): Actions
    {
        $showArchived = $this->isShowingArchived();

        $viewArchived = Action::new('viewArchivedCustomers', 'Ver archivados')
            ->setIcon('fa fa-box-archive')
            ->linkToUrl('?crudAction=index&crudControllerFqcn=' . self::class . '&archived=1')
            ->createAsGlobalAction();

        $viewActive = Action::new('viewActiveCustomers', 'Ver activos')
            ->setIcon('fa fa-users')
            ->linkToUrl('?crudAction=index&crudControllerFqcn=' . self::class)
            ->createAsGlobalAction();

        $archive = Action::new('archiveCustomer', 'Archivar')
            ->linkToCrudAction('archiveCustomer')
            ->displayIf(static fn (Customer $customer): bool => !$customer->isArchived())
            ->setIcon('fas fa-archive');

        $restore = Action::new('restoreCustomer', 'Recuperar')
            ->linkToCrudAction('restoreCustomer')
            ->displayIf(static fn (Customer $customer): bool => $customer->isArchived())
            ->setIcon('fa fa-rotate-left');

        $actions = $actions
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                static fn (Action $action): Action => $action
                    ->displayIf(static fn (Customer $customer): bool => !$customer->hasRelations() && !$customer->isArchived())
            )
            ->update(
                Crud::PAGE_DETAIL,
                Action::DELETE,
                static fn (Action $action): Action => $action
                    ->displayIf(static fn (Customer $customer): bool => !$customer->hasRelations() && !$customer->isArchived())
            );

        if ($showArchived) {
            return $actions
                ->disable(Action::NEW)
                ->disable(Action::EDIT)
                ->disable(Action::DELETE)
                ->add(Crud::PAGE_INDEX, $viewActive)
                ->add(Crud::PAGE_INDEX, $restore)
                ->add(Crud::PAGE_DETAIL, $restore);
        }

        return $actions
            ->add(Crud::PAGE_INDEX, $viewArchived)
            ->add(Crud::PAGE_INDEX, $archive)
            ->add(Crud::PAGE_DETAIL, $archive);
    }

    #[AdminRoute(path: '/archive-customer', name: 'archive_customer')]
    public function archiveCustomer(AdminContext $context): RedirectResponse
    {
        $customer = $context->getEntity()->getInstance();

        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('admin_customer_index');
        }

        $customer->setIsArchived(true);

        $this->entityManager->flush();

        $this->addFlash('success', 'Cliente archivado correctamente.');

        return $this->redirectToRoute('admin_customer_index');
    }

    #[AdminRoute(path: '/restore-customer', name: 'restore_customer')]
    public function restoreCustomer(AdminContext $context): RedirectResponse
    {
        $customer = $context->getEntity()->getInstance();

        if (!$customer instanceof Customer) {
            return $this->redirect(
                '/admin?crudAction=index&crudControllerFqcn=' . self::class . '&archived=1'
            );
        }

        $customer->setIsArchived(false);

        $this->entityManager->flush();

        $this->addFlash('success', 'Cliente recuperado correctamente.');

        return $this->redirect(
            '/admin?crudAction=index&crudControllerFqcn=' . self::class . '&archived=1'
        );
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $queryBuilder
            ->andWhere('entity.isArchived = :isArchived')
            ->setParameter('isArchived', $this->isShowingArchived());

        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('fullName', 'Nombre'),

            TextField::new('subscriberNumber', 'Número de abonado'),

            EmailField::new('email', 'Email'),

            MoneyField::new('monthlyAmount', 'Mensualidad')
                ->setCurrency('ARS')
                ->setStoredAsCents(false)
                ->hideOnForm(),

            BooleanField::new('monthlyDebt', 'Debe mes'),

            BooleanField::new('isArchived', 'Archivado')
                ->hideOnForm()
                ->hideOnIndex(),

            CollectionField::new('customerPlans', 'Planes asignados')
                ->setEntryType(CustomerPlanType::class)
                ->allowAdd()
                ->allowDelete()
                ->onlyOnForms(),
        ];
    }

    private function isShowingArchived(): bool
    {
        return $this->requestStack->getCurrentRequest()?->query->getBoolean('archived', false) ?? false;
    }
}