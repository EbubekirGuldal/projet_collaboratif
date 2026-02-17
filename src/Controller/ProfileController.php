<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/edit', name: 'edit_profile')]
    public function edit(Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        $data = $request->request->all();

        /**@var User $user */
        $user = $this->getUser();

        $user->setUsername($data["username"])
            ->setFirstName($data["firstName"])
            ->setLastName($data["lastName"]);

        $emailChanged = false;

        $newEmail = trim((string) ($data['email'] ?? ''));

        if ($newEmail !== '' && $newEmail !== $user->getEmail()) {
            $emailChanged = true;
            $user->setEmail($data["email"]);
            $user->setIsVerified(false);
        }

        $this->em->flush();

        if ($emailChanged) {

            $this->addFlash('success', 'Votre email a été modifié. Veuillez vous reconnecter après vérification.');
            $this->container->get('security.token_storage')->setToken(null);

            return $this->redirectToRoute("app_login");
        }

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
        return $this->redirectToRoute("app_profile");
    }

    #[Route('/profile/change-password', name: 'change_password', methods: ["POST"])]
    public function changePassword(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $data = $request->request->all();

        $current = trim((string) ($data['currentPassword'] ?? ''));
        $new = trim((string) ($data['newPassword'] ?? ''));
        $confirm = trim((string) ($data['confirmNewPassword'] ?? ''));

        if ($current === '' || $new === '' || $confirm === '') {
            $this->addFlash('warning', 'Veuillez renseigner tous les champs pour modifier votre mot de passe.');
            return $this->redirectToRoute('app_profile');
        }

        if (!password_verify($current, $user->getPassword())) {
            $this->addFlash('warning', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_profile');
        }

        if ($new !== $confirm) {
            $this->addFlash('warning', 'Les champs "Nouveau mot de passe" et "Confirmation du mot de passe" doivent être identiques.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $new));
        $this->em->flush();

        $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès.');
        return $this->redirectToRoute('app_profile');
    }
}
