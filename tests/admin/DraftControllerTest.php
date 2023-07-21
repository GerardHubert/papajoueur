<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DraftControllerTest extends WebTestCase
{
    public function testRedirectionIfNotConnected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/drafts');

        $this->assertResponseStatusCodeSame(302, 'must redirect to login page');
        $this->assertPageTitleContains('login');
    }

    public function testRedirectionIfNotAdmin(): void
    {
        $client = static::createClient();
        /** @var UserRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('user0@papajoueur.fr');

        $client->loginUser($user);
        $client->request('GET', '/admin/drafts');

        $this->assertResponseStatusCodeSame(403, 'message 403');
        $this->assertSelectorTextContains('h1', 'Accès non autorisé');
    }

    public function testShowDrafts(): void
    {
        $client = static::createClient();

        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);

        $admin = $userRepo->findOneBy(['email' => 'admin@papajoueur.fr']);
        $client->loginUser($admin);

        $crawler = $client->request('GET', '/admin/drafts');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'brouillon');
    }
}
