<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'content', 'status', 'resource.title', 'resource.id'])
            ->setEntityLabelInSingular('Commentaire')
            ->setEntityLabelInPlural('Commentaires')
            ->setAutofocusSearch()
            ->hideNullValues()
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action): Action => $action->setIcon('fa fa-comment')->setLabel('Créer un commentaire'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action): Action => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action): Action => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action): Action => $action->setIcon('fa fa-trash'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextEditorField::new('content'),
            TextField::new('status'),
            AssociationField::new('resource'),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }
    
}
