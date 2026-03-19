<?php

namespace App\Controller\Admin;

use App\Entity\RelationKind;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RelationKindCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RelationKind::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'name'])
            ->setEntityLabelInSingular('Public concerné')
            ->setEntityLabelInPlural('Publics concernés')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn(Action $action): Action => $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Créer un public')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::EDIT,
                fn(Action $action): Action => $action->setIcon('fa fa-pen')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::DETAIL,
                fn(Action $action): Action => $action->setIcon('fa fa-eye')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                fn(Action $action): Action => $action->setIcon('fa fa-trash')
            );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom'),
        ];
    }
}
