<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Usuario')
            ->setEntityLabelInPlural('Usuarios')
            ->setPageTitle(Crud::PAGE_INDEX, 'Usuarios')
            ->setPageTitle(Crud::PAGE_NEW, 'Crear usuario')
            ->setPageTitle(Crud::PAGE_EDIT, 'Editar usuario')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Detalle del usuario');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            TextField::new('username', 'Usuario'),

            ChoiceField::new('roles', 'Roles')
                ->allowMultipleChoices()
                ->setChoices([
                    'Usuario' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN',
                ]),

            TextField::new('password', 'Contraseña')
                ->setFormType(PasswordType::class)
                ->onlyOnForms(),
        ];
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $entityInstance,
            $entityInstance->getPassword()
        );

        $entityInstance->setPassword($hashedPassword);

        parent::persistEntity($entityManager, $entityInstance);
    }
}