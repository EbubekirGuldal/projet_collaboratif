<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MainTest extends WebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Vérifie que la page répond bien (HTTP 200)
        $this->assertResponseIsSuccessful();

        // Vérifie qu'un titre principal est présent
        $this->assertSelectorExists('h1');

        // Vérifie que le contenu correspond au projet CESIZen
        $this->assertSelectorTextContains('body', 'Ressources relationnelles');
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Vérifie que la page répond correctement
        $this->assertResponseIsSuccessful();

        // Vérifie qu'il y a un formulaire de connexion
        $this->assertSelectorExists('form');

        // Vérifie qu'un champ email existe (adapte si besoin)
        $this->assertSelectorExists('input[type="email"]');

        // Vérifie qu'il y a un bouton de connexion
        $this->assertSelectorExists('button');
    }
}
