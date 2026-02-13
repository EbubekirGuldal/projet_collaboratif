<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: nom@domaine.com'],
            ])
            ->add('username', null, [
                'label' => "Nom d'utilisateur",
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: matt-theo'],
            ])
            ->add('firstName', null, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre prénom'],
            ])
            ->add('lastName', null, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre nom'],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => "J'accepte les conditions d'utilisation",
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter les conditions.']),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password', 'class' => 'form-control', 'placeholder' => '••••••••'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 255,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
