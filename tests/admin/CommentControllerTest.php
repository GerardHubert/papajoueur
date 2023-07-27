<?php

namespace App\Tests\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentControllerTest extends WebTestCase
{

    public function getAdmin(): ?User
    {
        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);
        $users = $userRepo->findAll();

        foreach ($users as $key => $value) {
            if (in_array('ROLE_ADMIN', $value->getRoles())) {
                dump('TRUE : ' . $value->getEmail());
                return $value;
            }
        }

        return null;
    }

    public function testIfCommentsAreShown(): void
    {
        $client = static::createClient();
        $admin = $this->getAdmin();

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/admin/comments');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame("Papajoueur - Admin - Commentaires");
    }
}
