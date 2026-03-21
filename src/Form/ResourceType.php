<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\RelationKind;
use App\Entity\Resource;
use App\Entity\RessourceType;
use App\Enum\ResourceStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Ex. Guide d entraide etudiante',
                    'maxlength' => 255,
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Explique clairement la ressource, son utilite et son public.',
                    'rows' => 8,
                ],
            ])
            ->add('ressourceType', EntityType::class, [
                'class' => RessourceType::class,
                'choice_label' => 'label',
                'label' => 'Type de ressource',
                'required' => false,
                'placeholder' => 'Choisir un type',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Categorie',
                'required' => false,
                'placeholder' => 'Choisir une categorie',
            ])
            ->add('relationKind', EntityType::class, [
                'class' => RelationKind::class,
                'choice_label' => 'name',
                'label' => 'Public concerne',
                'required' => false,
                'placeholder' => 'Choisir un public',
            ])
            ->add('resourceStatus', ChoiceType::class, [
                'label' => 'Visibilite',
                'choices' => [
                    'Privee' => ResourceStatus::PRIVATE,
                    'Partagee' => ResourceStatus::SHARED,
                    'Publique' => ResourceStatus::PUBLIC,
                ],
            ])
            ->add('externalUrl', UrlType::class, [
                'label' => 'Lien externe',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://...',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Categorie',
                'placeholder' => 'Selectionner une categorie',
                'required' => false,
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'asset_helper' => true,
                'help' => 'Optionnel. Formats conseilles : JPG, PNG, WebP.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
