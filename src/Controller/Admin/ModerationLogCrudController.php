<?php

namespace App\Controller\Admin;

use App\Entity\ModerationLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class ModerationLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ModerationLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'targetType', 'action', 'reason', 'createdAt', 'resource.title', 'resource.id', 'user.email', 'user.username'])
            ->setEntityLabelInSingular('Moderation')
            ->setEntityLabelInPlural('Moderation')
            ->setAutofocusSearch()
            ->hideNullValues()
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action): Action => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action): Action => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action): Action => $action->setIcon('fa fa-trash'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('targetType', 'Type cible'),
            AssociationField::new('resource', 'Ressource signalee'),
            TextField::new('action', 'Action'),
            AssociationField::new('user', 'Declare par'),
            TextField::new('reason', 'Motif'),
            DateTimeField::new('createdAt', 'Cree le')->hideOnForm(),
        ];
    }
}
