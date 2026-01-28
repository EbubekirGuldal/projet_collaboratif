<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
<<<<<<< HEAD
=======
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
>>>>>>> baptiste
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
<<<<<<< HEAD

=======
>>>>>>> baptiste
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

<<<<<<< HEAD
=======
    
>>>>>>> baptiste
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            TextField::new('password'),
<<<<<<< HEAD
            TextField::new('username'),
=======
            TextField::new('userName'),
>>>>>>> baptiste
            TextField::new('lastName'),
            TextField::new('firstName'),
            BooleanField::new('isVerified'),
            BooleanField::new('isActive'),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        if ($entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPassword());
            $entityInstance->setPassword($hashedPassword);
        }

        
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        // On récupère l'entité originale 
        $original = $em->getUnitOfWork()->getOriginalEntityData($entityInstance);

        if ($original["password"] != $entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPassword());
            $entityInstance->setPassword($hashedPassword);
        }

        parent::updateEntity($em, $entityInstance);
    }
<<<<<<< HEAD
=======


    
>>>>>>> baptiste
}
