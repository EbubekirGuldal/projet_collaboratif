<?php

namespace App\Controller\Admin;

use App\Entity\Resource;
use App\Enum\ResourceStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichImageType;

#[IsGranted('ROLE_ADMIN')]
class ResourceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Resource::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields([
                'id',
                'title',
                'content',
                'resourceStatus',
                'category.name',
                'ressourceType.label',
                'relationKind.name',
                'user.email',
                'user.username',
            ])
            ->setEntityLabelInSingular('Ressource')
            ->setEntityLabelInPlural('Ressources')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action): Action => $action->setLabel('Creer une ressource'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action): Action => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action): Action => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action): Action => $action->setIcon('fa fa-trash'));
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Général')->setIcon('fa fa-folder-open'),

            FormField::addColumn(6),
            FormField::addPanel('Informations principales')->setIcon('fa fa-info-circle'),
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextareaField::new('content', 'Contenu'),
            AssociationField::new('category', 'Categorie'),
            AssociationField::new('ressourceType', 'Type de ressource'),
            AssociationField::new('relationKind', 'Public concerne'),

            FormField::addColumn(6),
            FormField::addPanel('Medias et liens')->setIcon('fa fa-photo-video'),
            TextField::new('externalUrl', 'URL externe')->hideOnIndex(),
            TextField::new('video', 'Video')->hideOnIndex(),
            ImageField::new('image', 'Image')
                ->setBasePath('/images/resources')
                ->onlyOnIndex(),
            TextField::new('imageFile', 'Uploader une image')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setFormTypeOptions([
                    'required' => false,
                    'allow_delete' => true,
                    'download_uri' => true,
                ]),

            FormField::addColumn(12),
            FormField::addPanel('Utilisateur')->setIcon('fa fa-user'),
            AssociationField::new('user', false)->setFormTypeOption('disabled', true),

            FormField::addTab('Metadonnees')->setIcon('fa fa-database'),

            FormField::addColumn(6),
            FormField::addPanel('Statut et compteurs')->setIcon('fa fa-chart-bar'),
            ChoiceField::new('resourceStatus', 'Statut')
                ->setChoices([
                    'Privee' => ResourceStatus::PRIVATE,
                    'Partagee' => ResourceStatus::SHARED,
                    'Publique' => ResourceStatus::PUBLIC,
                    'En revue' => ResourceStatus::UNDER_REVIEW,
                ]),
            IntegerField::new('likesCount', 'Likes')
                ->setFormTypeOption('disabled', true),
            IntegerField::new('sharesCount', 'Partages')
                ->setFormTypeOption('disabled', true),

            FormField::addColumn(6),
            FormField::addPanel('Dates')->setIcon('fa fa-calendar-alt'),
            DateTimeField::new('publishedAt', 'Publie le')
                ->hideWhenCreating()
                ->setFormTypeOption('disabled', true),
            DateTimeField::new('createdAt', 'Cree le')
                ->hideWhenCreating()
                ->setFormTypeOption('disabled', true),
            DateTimeField::new('updatedAt', 'Mis a jour le')
                ->hideWhenCreating()
                ->setFormTypeOption('disabled', true),
        ];
    }
}
