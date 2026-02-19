<?php

namespace App\Controller\Admin;

use App\Entity\Resource;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
            ->setSearchFields(['id', 'title', 'content', 'resourceStatus', 'visibilityStatus'])
            ->setEntityLabelInSingular('Ressource')
            ->setEntityLabelInPlural('Ressources')
            ->setAutofocusSearch()
            ->hideNullValues()
            ->setDefaultSort(['id' => 'DESC']);
    }

     public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action): Action => $action->setIcon('fa-regular fa-image')->setLabel('Créer une ressource'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action): Action => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action): Action => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action): Action => $action->setIcon('fa fa-trash'))
            ;
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            // =========================
            // TAB 1 : GÉNÉRAL
            // =========================
            FormField::addTab('Général')->setIcon('fa fa-sliders-h'),

            FormField::addColumn(6),
            FormField::addPanel('Informations')->setIcon('fa fa-info-circle'),
            IdField::new('id')->hideOnForm(),

            TextField::new('title', 'Titre'),
            TextareaField::new('content', 'Contenu'),

            FormField::addColumn(6),
            FormField::addPanel('Liens & médias')->setIcon('fa fa-photo-video'),

            TextField::new('externalUrl', 'URL externe'),
            TextField::new('video', 'Vidéo'),

            // Affichage de l'image sur index/detail
            ImageField::new('image', 'Image')
                ->setBasePath('/images/resources')
                ->onlyOnIndex(),

            // VRAI input upload (Vich) sur les formulaires
            TextField::new('imageFile', 'Uploader une image')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setFormTypeOptions([
                    'required'      => false,
                    'allow_delete'  => true,
                    'download_uri'  => true,
                ])
                ->setHelp('Formats conseillés : JPG/PNG/WebP'),

            // =========================
            // TAB 2 : MÉTADONNÉES
            // =========================
            FormField::addTab('Métadonnées')->setIcon('fa fa-database'),

            FormField::addColumn(6),
            FormField::addPanel('Statuts')->setIcon('fa fa-tags'),

            TextField::new('resourceStatus', 'Statut ressource'),
            TextField::new('visibilityStatus', 'Visibilité'),

            FormField::addColumn(6),
            IntegerField::new('sharesCount', 'Partages'),

            FormField::addPanel('Dates')->setIcon('fa fa-clock'),
            DateTimeField::new('publishedAt', 'Publié le')
                ->hideWhenCreating()
                ->setFormTypeOption('disabled', true),

            DateTimeField::new('createdAt', 'Créé le')
                ->hideWhenCreating()
                ->setFormTypeOption('disabled', true),

            DateTimeField::new('updatedAt', 'Mis à jour le')
                ->hideWhenCreating()
                ->setFormTypeOption('disabled', true),
        ];
    }
    
}
