<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AvatarChangeTest extends WebTestCase
{
    public function testRedirectIfNoUserFound(): void
    {
        $client = static::createClient();
        /** assume that userid 999 does not exists */
        $crawler = $client->request('GET', '/user/999');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testRedirectIfUsersDoesNotMatch(): void
    {
        $client = static::createClient();

        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);

        $users = $userRepo->findAll();

        // get a random user to simulate a connected user
        $randomConnectedUser = $users[array_rand($users, 1)];
        $client->loginUser($randomConnectedUser);

        // get another random user
        $newUsers = array_splice($users, (int) array_keys($users, $randomConnectedUser), 1);
        $anotherRandomUser = $newUsers[array_rand($newUsers, 1)];

        $client->request('GET', '/user/' . $anotherRandomUser->getId());

        $this->assertResponseStatusCodeSame(302);
    }
}
