<?php

namespace App\Tests\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginTest extends WebTestCase
{
    public function testUserCanLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = sprintf('login.test.%s@example.com', bin2hex(random_bytes(6)));
        $plainPassword = 'Password123!';

        $user = (new User())
            ->setEmail($email)
            ->setUsername('login_test_user');
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $client->submitForm('Se connecter', [
            'email' => $email,
            'password' => $plainPassword,
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/logout"]');

        $entityManager->clear();
        $reloadedUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($reloadedUser);
        self::assertNotNull($reloadedUser->getLastConnexion());
    }
}
