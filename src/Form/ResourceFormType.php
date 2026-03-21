<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\RelationKind;
use App\Entity\Resource;
use App\Entity\RessourceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ResourceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Donnez un titre clair à votre ressource',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Décrivez la ressource, son utilité et son contexte.',
                ],
            ])
            ->add('ressourceType', EntityType::class, [
                'class' => RessourceType::class,
                'choice_label' => 'label',
                'label' => 'Type de ressource',
                'required' => false,
                'placeholder' => 'Choisir un type',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('relationKind', EntityType::class, [
                'class' => RelationKind::class,
                'choice_label' => 'name',
                'label' => 'Public concerné',
                'required' => false,
                'placeholder' => 'Choisir un public',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('externalUrl', UrlType::class, [
                'label' => 'Lien externe',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://...',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Categorie',
                'required' => false,
                'placeholder' => 'Selectionner une categorie',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('video', TextType::class, [
                'label' => 'Vidéo',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Lien ou référence vidéo',
                ],
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Illustration',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'Formats conseillés : JPG, PNG, WebP.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}