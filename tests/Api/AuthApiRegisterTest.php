<?php

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test de non-regression de ANO-002.
 *
 * AuthApiController::register() testait la presence de $data["firstName"] mais
 * lisait la valeur dans $data["firstname"]. La cle absente valant null, le
 * prenom etait enregistre vide sans qu'aucune erreur ne remonte : la reponse
 * HTTP restait un 200 et le client ne pouvait pas detecter la perte.
 *
 * Ces tests echouent sur le code d'avant la correction et passent apres.
 */
class AuthApiRegisterTest extends WebTestCase
{
    private function courriel(): string
    {
        return sprintf('ano002-%s@exemple.test', bin2hex(random_bytes(6)));
    }

    public function testLePrenomEtLeNomSontEnregistresALInscription(): void
    {
        $client = static::createClient();
        $courriel = $this->courriel();

        $client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $courriel,
            'username' => 'ano002',
            'password' => 'MotDePasseDeTest!2026',
            'firstName' => 'Camille',
            'lastName' => 'Durand',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseIsSuccessful();

        $charge = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Le defaut se manifestait ici : firstName revenait a null.
        $this->assertSame('Camille', $charge['user']['firstName'] ?? null);
        $this->assertSame('Durand', $charge['user']['lastName'] ?? null);

        // Et surtout en base : c'est la perte de donnee qui compte, pas la reponse.
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = $em->getRepository(User::class)->findOneBy(['email' => $courriel]);

        $this->assertNotNull($utilisateur, 'Le compte doit avoir ete cree.');
        $this->assertSame('Camille', $utilisateur->getFirstName());
        $this->assertSame('Durand', $utilisateur->getLastName());
    }

    public function testLInscriptionResteAcceptEeSansPrenomNiNom(): void
    {
        $client = static::createClient();
        $courriel = $this->courriel();

        // La correction utilise l'operateur ?? : l'absence des cles ne doit
        // declencher ni avertissement PHP ni erreur 500.
        $client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $courriel,
            'username' => 'ano002-partiel',
            'password' => 'MotDePasseDeTest!2026',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseIsSuccessful();

        $charge = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('firstName', $charge['user']);
        $this->assertNull($charge['user']['firstName']);
    }
}
