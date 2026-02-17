<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage,
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
}
