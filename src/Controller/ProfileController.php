<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/saved', name: 'app_profile_saved', methods: ['GET'])]
    public function saved(ResourceRepository $resourceRepository): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $user->getFavorites()->toArray();

        usort($favorites, static function ($a, $b) {
            $aDate = $a->getCreatedAt()?->getTimestamp() ?? 0;
            $bDate = $b->getCreatedAt()?->getTimestamp() ?? 0;

            return $bDate <=> $aDate;
        });

        $feedItems = [];
        foreach ($favorites as $resource) {
            $feedItems[] = [
                'resource' => $resource,
                'commentsCount' => $resource->getComments()->count(),
            ];
        }

        return $this->render('profile/saved.html.twig', [
            'feedItems' => $feedItems,
        ]);
    }

    #[Route('/profile/edit', name: 'edit_profile', methods: ['POST'])]
    public function edit(Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $data = $request->request->all();

        /** @var User $user */
        $user = $this->getUser();

        $user->setUsername($data['username'] ?? $user->getUsername())
            ->setFirstName($data['firstName'] ?? $user->getFirstName())
            ->setLastName($data['lastName'] ?? $user->getLastName());

        $emailChanged = false;
        $newEmail = trim((string) ($data['email'] ?? ''));

        if ($newEmail !== '' && $newEmail !== $user->getEmail()) {
            $emailChanged = true;
            $user->setEmail($newEmail);
            $user->setIsVerified(false);
        }

        $this->em->flush();

        if ($emailChanged) {
            $this->addFlash('success', 'Votre email a été modifié. Veuillez vous reconnecter après vérification.');
            $this->container->get('security.token_storage')->setToken(null);

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/change-password', name: 'change_password', methods: ['POST'])]
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

    #[Route('/profile/photo', name: 'update_profile_photo', methods: ['POST'])]
    public function updateProfilePhoto(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('update_profile_photo', (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Session invalide, merci de réessayer.');
            return $this->redirectToRoute('app_profile');
        }

        $pictureFile = $request->files->get('profilePicture');
        if (!$pictureFile instanceof UploadedFile) {
            $this->addFlash('warning', 'Aucun fichier image sélectionné.');
            return $this->redirectToRoute('app_profile');
        }

        if ($pictureFile->getError() !== \UPLOAD_ERR_OK) {
            $this->addFlash('warning', 'Le fichier n a pas pu etre téléversé.');
            return $this->redirectToRoute('app_profile');
        }

        if ($pictureFile->getSize() > 5 * 1024 * 1024) {
            $this->addFlash('warning', 'La taille maximale autorisée est de 5 Mo.');
            return $this->redirectToRoute('app_profile');
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($pictureFile->getMimeType(), $allowedMimeTypes, true)) {
            $this->addFlash('warning', 'Format invalide. Utilisez JPG, PNG, GIF ou WebP.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setImageFile($pictureFile);
        $this->em->flush();

        $this->addFlash('success', 'Photo de profil mise à jour.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/delete', name: 'delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Requête invalide.');
            return $this->redirectToRoute('app_profile');
        }

        foreach ($user->getResources() as $resource) {
            $resource->setUser(null);
        }

        foreach ($user->getComments() as $comment) {
            $this->em->remove($comment);
        }

        $user->getFavorites()->clear();

        $this->em->remove($user);
        $this->em->flush();

        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Votre compte a ete supprimé.');
        return $this->redirectToRoute('app_home');
    }
}