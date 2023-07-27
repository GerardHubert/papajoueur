<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use DateTime;
use Faker;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Review;
use App\Services\QueryService;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private QueryService $queryService
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Faker\Generator */
        $faker = Faker\Factory::create();
        $users = [];
        $reviews = [];

        // création d'un administrateur
        $admin = new User();
        $admin->setEmail("admin@papajoueur.fr")
            ->setPassword($this->hasher->hashPassword($admin, 'password'))
            ->setPasswordConfirm($admin->getPassword())
            ->setRoles(['ROLE_ADMIN'])
            ->setAvatar("https://picsum.photos/100");
        $manager->persist($admin);
        array_push($users, $admin);
        dump('admin créé : ' . $admin->getEmail());

        // création de 10 utilisateurs avec le status user
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail("user" . $i . "@papajoueur.fr")
                ->setPassword($this->hasher->hashPassword($user, 'password'))
                ->setPasswordConfirm($user->getPassword())
                ->setRoles(['ROLE_USER'])
                ->setAvatar("https://picsum.photos/100");
            $manager->persist($user);
            array_push($users, $user);
        }
        dump('10 USERS CREES');

        $key = $_ENV['RAWG_API_KEY'];
        /** @var GameRepository */
        $gameRepo = $manager->getRepository(Game::class);

        /** @var UserRepository */
        $userRepo = $manager->getRepository(User::class);

        // création des reviews
        for ($i = 0; $i < 30; $i++) {
            // on récupère d'abord des jeux aléatoirement avec une plage d'id entre 2000 et 8000
            $randomInt = rand(2000, 8000);
            $query = $this->queryService->findById($randomInt, $key);
            $result = json_decode($query['game'], true);

            if ($query['failure'] !== '') {
                dump('echec requete', $query['failure']);
                continue;
            }

            if (array_key_exists('details', $result)) {
                dump('pas de résultat');
                continue;
            }

            if (array_key_exists('id', $result) && $gameRepo->findOneBy(['apiId' => $result['id']]) !== null) {
                dump('jeu existant');
                continue;
            }

            // si l'api nous renvoie bien un jeu, on enregistre le jeu et on y associe une review
            if ($query['game'] !== false && array_key_exists('id', $result) && $gameRepo->findOneBy(['apiId' => $result['id']]) === null) {
                $game = new Game();
                $game->setApiId($result['id'])
                    ->setGenres($result['genres'])
                    ->setName($result['name'])
                    ->setReleasedAt(new DateTime($result['released']))
                    ->setDevelopers($result['developers'])
                    ->setPlatforms($result['platforms']);
                $result['background_image'] === null ? $game->setImage('/images/review_default.jpg') : $game->setImage($result['background_image']);
                $manager->persist($game);

                $review = new Review;
                $review->setApiGameId($game->getApiId())
                    ->setAuthor($admin)
                    ->setContent($faker->paragraphs(20, true))
                    ->setCreatedAt($faker->dateTime())
                    ->setGame($game)
                    ->setStatus('published')
                    ->setTitle($faker->text(150))
                    ->setSummary($faker->text(500));

                $manager->persist($review);
                array_push($reviews, $review);
            }
        }
        dump('30 JEUX ET 30 REVIEWS CREES');

        foreach ($reviews as $review) {
            // pour chaque nouvellle review, on ajoute 5 commentaires
            for ($i = 0; $i < 5; $i++) {
                // choisir un utilisateur au hasard pour en faire l'auteur
                $randomUser = $users[array_rand($users, 1)];

                $comment = new Comment;
                $comment->setContent($faker->text(150))
                    ->setAuthor($randomUser)
                    ->setCreatedAt($faker->dateTime('now'))
                    ->setReview($review)
                    ->setLikes(0)
                    ->setDislikes(0)
                    ->setReported(false);
                $manager->persist($comment);
            }
            dump('5 COMMENTAIRES CREES POUR 1 REVIEW : ' . $review->getId());
        }

        $manager->flush();
    }
}
