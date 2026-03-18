<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Resource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                    'placeholder' => 'Ex. Guide d’entraide étudiante',
                    'maxlength' => 255,
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Explique clairement en quoi consiste la ressource, à qui elle sert et comment y accéder.',
                    'rows' => 8,
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
                'help' => 'Optionnel. Formats conseillés : JPG, PNG, WebP.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
