<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\User;
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
            return $this->redirectToRoute("app_login");
        }

        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/saved-resources', name: 'app_profile_saved_resources', methods: ['GET'])]
    public function savedResources(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $resources = $user->getFavorites()->toArray();
        $query = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'new');
        $allowedSorts = ['new', 'top', 'title'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'new';
        }

        if ($query !== '') {
            $normalizedQuery = mb_strtolower($query);

            $resources = array_values(array_filter(
                $resources,
                static function (Resource $resource) use ($normalizedQuery): bool {
                    $haystack = implode(' ', [
                        $resource->getTitle(),
                        strip_tags($resource->getContent()),
                        $resource->getExternalUrl() ?? '',
                    ]);

                    return str_contains(mb_strtolower($haystack), $normalizedQuery);
                }
            ));
        }

        usort($resources, static function (Resource $left, Resource $right) use ($sort): int {
            if ($sort === 'title') {
                return strcasecmp($left->getTitle(), $right->getTitle());
            }

            if ($sort === 'top') {
                $leftScore = ($left->getLikesCount() ?? 0) + ($left->getSharesCount() ?? 0) + $left->getComments()->count();
                $rightScore = ($right->getLikesCount() ?? 0) + ($right->getSharesCount() ?? 0) + $right->getComments()->count();

                if ($leftScore !== $rightScore) {
                    return $rightScore <=> $leftScore;
                }
            }

            $leftDate = $left->getPublishedAt() ?? $left->getCreatedAt();
            $rightDate = $right->getPublishedAt() ?? $right->getCreatedAt();

            if ($leftDate === null && $rightDate === null) {
                return 0;
            }

            if ($leftDate === null) {
                return 1;
            }

            if ($rightDate === null) {
                return -1;
            }

            return $rightDate <=> $leftDate;
        });

        $savedItems = array_map(
            static fn (Resource $resource): array => [
                'resource' => $resource,
                'commentsCount' => $resource->getComments()->count(),
            ],
            $resources
        );

        return $this->render('profile/saved_resources.html.twig', [
            'savedItems' => $savedItems,
            'q' => $query,
            'sort' => $sort,
            'savedCount' => count($resources),
            'totalSavedCount' => $user->getFavorites()->count(),
        ]);
    }

    #[Route('/profile/edit', name: 'edit_profile')]
    public function edit(Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        $data = $request->request->all();

        /** @var User $user */
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