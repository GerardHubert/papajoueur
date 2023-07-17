<?php

namespace App\Tests\Admin;

use DateTime;
use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Services\QueryService;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReviewControllerTest extends WebTestCase
{
    public function getUser(): User
    {
        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);

        // fecth a user who has not an admin role
        $user = $userRepo->findOneByEmail('user9@papajoueur.fr');
        return $user;
    }

    public function getAdmin(): User
    {
        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);

        // fecth a user who has not an admin role
        $admin = $userRepo->findOneByEmail('admin@papajoueur.fr');
        return $admin;
    }

    public function getReviews(): array
    {
        // get initial count of reviews (before saving)
        /** @var ReviewRepository */
        $reviewRepo = self::getContainer()->get(ReviewRepository::class);
        $reviews = $reviewRepo->findAll();
        return $reviews;
    }

    public function gameQuery(): array
    {
        self::bootKernel();
        $game = QueryService::findById(388309, $_ENV['RAWG_API_KEY']);
        return $game;
    }

    public function getRandomIdFromGameRepo(): int
    {
        self::bootKernel();
        /** @var GameRepository */
        $gameRepo = static::getContainer()->get(GameRepository::class);
        $games = $gameRepo->findAll();
        $id = [];
        foreach ($games as $game) {
            array_push($id, $game->getApiId());
        }

        return array_rand($id, 1);
    }

    public function generateRandomApiId(): int
    {
        self::bootKernel();
        /** @@var GameRepository */
        $gameRepo = static::getContainer()->get(GameRepository::class);
        $games = $gameRepo->findAll();

        $haystack = [];
        foreach ($games as $game) {
            array_push($haystack, $game->getApiId());
        }

        // définir une liste de nombre aléatoires
        $randomInt = [];
        for ($i = 0; $i < 30; $i++) {
            array_push($randomInt, rand(100, 388309));
        }

        // de la liste, supprimer les id des jeux déjà enregistrés
        foreach ($randomInt as $i) {
            if (in_array($i, $haystack)) {
                array_splice($randomInt, $randomInt[$i]);
            }
        }

        // choisir un nombre de la liste finale
        return array_rand($randomInt, 1);
    }

    public function testRedirectionIfNoUserLogged(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/game_search');

        $this->assertResponseStatusCodeSame(302, "Redirection attendue si utilisateur non connecté ou utilisateur non ADMIN");
    }

    public function testRedirectToUnauthorizedIfUserIsNotAdmin(): void
    {
        $client = static::createClient();

        // simulate a login
        $client->loginUser($this->getUser());

        // test the route
        $client->request('GET', '/admin/reviews');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRedirectIfCsrfIsNotValid(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());
        $crawler = $client->request('GET', '/admin/game_search');

        // select the button
        $button = $crawler->selectButton('Rechercher');

        // retrieve the Form object for the form belonging to this button
        $form = $button->form();

        // set values on a form object

        $form['query'] = 'gears of war';
        $form['platform'] = "1";
        $form['token'] = 'token123';

        // submit the Form object
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
    }

    public function testSaveGameIfNotAlreadyInDatabase(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());

        // on récupère le nombre de jeux enregistrés en bdd
        /** @var GameRepository */
        $gameRepo = static::getContainer()->get(GameRepository::class);
        $games = $gameRepo->findAll();

        // on tente d'ajouter un jeu qui n'existe pas déjà en bdd
        $randomApiId = $this->generateRandomApiId();
        $crawler = $client->request('GET', '/admin/review/new/' . $randomApiId);
        $newGame = $gameRepo->findOneBy(['apiId' => $randomApiId]);

        // on s'assure que le jeu a bien été enregistré en bdd
        $newGamesList = $gameRepo->findAll();
        $this->assertContains($newGame, $newGamesList, 'nouveau jeu bien présent');
    }

    public function testSaveReviewAsPublished(): void
    {
        $client = static::createClient();
        // get initial reviews count in database
        $initialReviews = $this->getReviews();

        $client->loginUser($this->getAdmin());
        $gameId = $this->getRandomIdFromGameRepo();

        $crawler = $client->request('GET', '/admin/review/new/' . $gameId);

        // select the button of the form
        $button = $crawler->selectButton('Publier la review');

        // retrieve the Form object for the form belonging to this button
        $form = $button->form();

        // set values on a form object
        $form['new-review-title'] = 'Publié : ' . uniqid();
        $form['review-content'] = "Lorem ipsum dolor sit amet consectetur, adipisicing elit. Reiciendis dolor consectetur aliquid molestiae necessitatibus quis suscipit eum non accusamus officia.";
        $form['new-review-good'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Phasellus sit amet dolor eu erat dignissim mattis vehicula a erat.
        Etiam ac lectus a odio iaculis rutrum sit amet et enim.
        Integer nec erat ut quam dignissim consectetur a ut lectus.';
        $form['new-review-bad'] = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Phasellus sit amet dolor eu erat dignissim mattis vehicula a erat.
        Etiam ac lectus a odio iaculis rutrum sit amet et enim.
        Integer nec erat ut quam dignissim consectetur a ut lectus.";
        $form['new-review-summary'] = 'Ceci est une review publiée !';
        $form['game-api-id'] = $gameId;
        $form['new-review-opinion'] = '/images/smileys/bad.png';

        // submit the Form object
        $client->submit($form);

        // test if reviews has been added
        /** @var ReviewRepository */
        $reviewRepo = self::getContainer()->get(ReviewRepository::class);
        $newReview = $reviewRepo->findOneBy(['apiGameId' => $gameId]);
        $this->assertContains($newReview, $this->getReviews());
        // test if the new review has status published
        $this->assertSame('published', $newReview->getStatus());
        // check the response
        $this->assertResponseStatusCodeSame(302);
    }

    public function testSaveReviewAsDraft(): void
    {
        $client = static::createClient();
        // get initial reviews count in database
        $initialReviews = $this->getReviews();

        $client->loginUser($this->getAdmin());
        $gameId = $this->getRandomIdFromGameRepo();

        $crawler = $client->request('GET', '/admin/review/new/' . $gameId);

        // select the button
        $button = $crawler->selectButton('Enregistrer le brouillon');

        // retrieve the Form object for the form belonging to this button
        $form = $button->form();

        // set values on a form object
        $form['new-review-title'] = 'Brouillon : ' . uniqid();
        $form['review-content'] = "Lorem ipsum dolor sit amet consectetur, adipisicing elit. Reiciendis dolor consectetur aliquid molestiae necessitatibus quis suscipit eum non accusamus officia.";
        $form['new-review-good'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Phasellus sit amet dolor eu erat dignissim mattis vehicula a erat.
        Etiam ac lectus a odio iaculis rutrum sit amet et enim.
        Integer nec erat ut quam dignissim consectetur a ut lectus.';
        $form['new-review-bad'] = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Phasellus sit amet dolor eu erat dignissim mattis vehicula a erat.
        Etiam ac lectus a odio iaculis rutrum sit amet et enim.
        Integer nec erat ut quam dignissim consectetur a ut lectus.";
        $form['new-review-summary'] = 'Ceci est une review au status de brouillon !';
        $form['game-api-id'] = $gameId;
        $form['new-review-opinion'] = '/images/smileys/bad.png';

        // submit the Form object
        $client->submit($form);

        // test if reviews has been added
        /** @var ReviewRepository */
        $reviewRepo = self::getContainer()->get(ReviewRepository::class);
        $newReview = $reviewRepo->findOneBy(['apiGameId' => $gameId]);
        $this->assertContains($newReview, $this->getReviews(), 'nouvelle review doit être enregistrée');
        // test if the new review has status published
        $this->assertSame('draft', $newReview->getStatus(), 'nouvelle review doit avoir le status brouillon');
        // check the response
        $this->assertResponseStatusCodeSame(302);
    }

    public function testDeleteReview(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());
        /** @var ReviewRepository */
        $reviewRepo = static::getContainer()->get(ReviewRepository::class);
        $reviews = $reviewRepo->findAll();
        $randomReview = array_rand($reviews, 1);

        $client->request('GET', '/admin/review/delete/' . $reviews[$randomReview]->getId());

        $newReviewsList = $reviewRepo->findAll();
        $this->assertNotContains($reviews[$randomReview], $newReviewsList, 'la review doit être supprimée');
    }
}
