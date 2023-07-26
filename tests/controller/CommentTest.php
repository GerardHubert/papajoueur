<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentTest extends WebTestCase
{
    public function getUsers(): array
    {
        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);
        return $userRepo->findAll();
    }

    public function getReviews(): array
    {
        /** @var ReviewRepository */
        $reviewRepo = static::getContainer()->get(ReviewRepository::class);
        return $reviewRepo->findAll();
    }

    public function testRedirectionIfNoUserIsFound(): void
    {
        $client = static::createClient();

        // définir un utilisateur inexistant (expect null) 
        // définir un id supérieur  l'id du dernier user
        $users = $this->getUsers();

        $user = $users[array_key_last($users)];
        $randomUserId = rand($user->getId() + 1, 9999);

        // récupérer une review aléatoire
        $reviews = $this->getReviews();
        $randomReview = $reviews[array_rand($reviews, 1)];

        $crawler = $client->request('POST', '/comment/add/' . $randomUserId . '/' . $randomReview->getId());

        $this->assertResponseStatusCodeSame(303, 'user non trouvé');
    }

    public function testRedirectionIfNoReviewFound(): void
    {
        $client = static::createClient();
        $users = $this->getUsers();
        $randomUser = $users[array_rand($users, 1)];

        $reviews = $this->getReviews();
        $lastReview = $reviews[array_key_last($reviews)];
        $inexistantReviewId = rand($lastReview->getId() + 1, 9999);

        $crawler = $client->request('GET', '/comment/add/' . $randomUser->getId() . '/' . $inexistantReviewId);

        $this->assertResponseStatusCodeSame(303, 'review non trouvée');
    }

    public function testRedirectionIfTokenNotValid(): void
    {
        $client = static::createClient();

        // obtenir une review et un utilisateur aléatoires
        $reviews = $this->getReviews();
        $users = $this->getUsers();
        $randomReview = $reviews[array_rand($reviews, 1)];
        $randomUser = $users[array_rand($users, 1)];

        // simuler un utilisateur connecté
        $client->loginUser($randomUser);
        $crawler = $client->request('GET', '/review/' . $randomReview->getId());

        // récupérer le formulaire
        $button = $crawler->selectButton('valider');
        $form = $button->form();

        // alimenter le formulaire
        $form['comment'] = 'Ceci est un commentaire aléatoire !';
        $form['comment-token'] = 'UnTokenAleatoire123456789';
        $client->submit($form);

        $this->assertResponseStatusCodeSame(303, 'token non valide !');
    }

    public function testNewCommentIsSaved(): void
    {
        $client = static::createClient();

        // liste de commentaires initiale
        /** @var CommentRepository */
        $commentRepo = static::getContainer()->get(CommentRepository::class);
        $initialCommentsList = $commentRepo->findAll();

        // obtenir une review et un utilisateur aléatoires
        $reviews = $this->getReviews();
        $users = $this->getUsers();
        $randomReview = $reviews[array_rand($reviews, 1)];
        $randomUser = $users[array_rand($users, 1)];

        // simuler un utilisateur connecté
        $client->loginUser($randomUser);
        $crawler = $client->request('GET', '/review/' . $randomReview->getId());

        // récupérer le formulaire
        $button = $crawler->selectButton('valider');
        $form = $button->form();

        // alimenter le formulaire
        $form['comment'] = 'Ceci est un commentaire aléatoire : ' . uniqid();
        $client->submit($form);

        // comparer le nombre de commentaires
        $updatedCommentsList = $commentRepo->findAll();

        $this->assertResponseStatusCodeSame(302, 'le commentaire a bien été enregistré');
        $this->assertTrue(count($initialCommentsList) < count($updatedCommentsList));
    }
}
