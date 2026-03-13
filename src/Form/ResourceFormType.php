<?php

namespace App\Form;

use App\Entity\Resource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('externalUrl', UrlType::class, [
                'label' => 'Lien externe',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://...',
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
            ->add('resourceStatus', ChoiceType::class, [
                'label' => 'Statut de la ressource',
                'choices' => [
                    'Publiée' => 'Publiee',
                    'Brouillon' => 'Brouillon',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('visibilityStatus', ChoiceType::class, [
                'label' => 'Visibilité',
                'choices' => [
                    'Public' => 'Public',
                    'Privé' => 'Prive',
                ],
                'attr' => ['class' => 'form-select'],
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
