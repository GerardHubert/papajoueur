<?php

namespace App\Tests\Admin;

use App\Entity\User;
use App\Entity\Comment;
use App\Repository\UserRepository;
use App\Repository\CommentRepository;
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

    public function testIfCommentIsDeleted(): void
    {
        $client = static::createClient();

        $users = static::getContainer()->get(UserRepository::class)->findAll();
        $client->loginUser($this->getAdmin());


        $commentRepo = static::getContainer()->get(CommentRepository::class);
        $comments = $commentRepo->findAll();
        $randomComment = $comments[array_rand($comments, 1)];
        $id = $randomComment->getId();

        $client->request('GET', '/admin/comment/delete/' . $randomComment->getId());

        $this->assertTrue(count($commentRepo->findAll()) < count($comments), "le nombre de commentaires en bdd n'a pas changé");
        $this->assertNull($commentRepo->find($id), "commentaire n'existe plus. résultat doit être null");
    }

    public function testCommentIsAllowed(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());

        $commentRepo = static::getContainer()->get(CommentRepository::class);
        $comments = $commentRepo->findBy(['reported' => true]);
        $comment = $comments[array_rand($comments, 1)];

        $client->request('GET', '/admin/comment/allow/' . $comment->getId());

        $this->assertNull($commentRepo->find($comment->getid())->isReported());
    }
}
