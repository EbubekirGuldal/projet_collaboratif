<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichImageType;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'email', 'username', 'firstName', 'lastName'])
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setAutofocusSearch()
            ->hideNullValues()
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action): Action => $action->setIcon('fa fa-user')->setLabel('Créer un utilisateur'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action): Action => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action): Action => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action): Action => $action->setIcon('fa fa-trash'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Profil')->setIcon('fa fa-user'),

            FormField::addColumn(6),
            FormField::addPanel('Identité')->setIcon('fa fa-id-card'),
            IdField::new('id')->hideOnForm(),
            TextField::new('username', 'Nom d\'utilisateur'),
            EmailField::new('email', 'Email'),
            TextField::new('firstName', 'Prénom'),
            TextField::new('lastName', 'Nom'),
            TextField::new('password', 'Mot de passe')->hideOnIndex(),

            FormField::addColumn(6),
            FormField::addPanel('Photo de profil')->setIcon('fa fa-image'),
            ImageField::new('picture', 'Image')
                ->setBasePath('/images/users')
                ->onlyOnIndex(),
            TextField::new('imageFile', 'Uploader une image')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setFormTypeOptions([
                    'required' => false,
                    'allow_delete' => true,
                    'download_uri' => true,
                ])
                ->setHelp('Formats conseillés : JPG/PNG/WebP'),

            FormField::addTab('Statut')->setIcon('fa fa-shield-alt'),

            FormField::addColumn(6),
            FormField::addPanel('Accès & validation')->setIcon('fa fa-user-check'),
            BooleanField::new('isVerified', 'Compte vérifié'),
            BooleanField::new('isActive', 'Compte actif'),
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->onlyOnForms(),

            FormField::addColumn(6),
            FormField::addPanel('Dates')->setIcon('fa fa-clock'),
            DateTimeField::new('lastConnexion', 'Dernière connexion')
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

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        if ($entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPassword());
            $entityInstance->setPassword($hashedPassword);
        }

        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $original = $em->getUnitOfWork()->getOriginalEntityData($entityInstance);

        if ($original['password'] != $entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPassword());
            $entityInstance->setPassword($hashedPassword);
        }

        parent::updateEntity($em, $entityInstance);
    }
}
