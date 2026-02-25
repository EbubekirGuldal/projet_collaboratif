<?php

namespace App\Controller\Admin;

use App\Entity\RessourceType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RessourceTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RessourceType::class;
    }

     public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'resourceType'])
            ->setEntityLabelInSingular('Type de Ressource')
            ->setEntityLabelInPlural('Type de Ressources')
            ->setAutofocusSearch()
            ->hideNullValues()
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action): Action => $action->setIcon('fa fa-bootstrap')->setLabel('Créer un type de ressource'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action): Action => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action): Action => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action): Action => $action->setIcon('fa fa-trash'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('resourceType'),
        ];
    }
}
