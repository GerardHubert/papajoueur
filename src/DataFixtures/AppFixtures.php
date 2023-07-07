<?php

namespace App\DataFixtures;

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

        // $product = new Product();
        // $manager->persist($product);
        $admin = new User();
        $admin->setEmail("mikado842@gmail.com")
            ->setPassword($this->hasher->hashPassword($admin, 'papajoueur!2023'))
            ->setPasswordConfirm($admin->getPassword())
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);
        dump('admin créé');

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail("user" . $i . "@papajoueur.fr")
                ->setPassword($this->hasher->hashPassword($user, 'password'))
                ->setPasswordConfirm($user->getPassword())
                ->setRoles(['ROLE_USER']);
            $manager->persist($user);
            dump('user créé : ' . $user->getEmail());
        }

        $key = $_ENV['RAWG_API_KEY'];
        /** @var GameRepository */
        $gameRepo = $manager->getRepository(Game::class);

        /** @var UserRepository */
        $userRepo = $manager->getRepository(User::class);

        for ($i = 0; $i < 30; $i++) {
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

            if ($query['game'] !== false && array_key_exists('id', $result) && $gameRepo->findOneBy(['apiId' => $result['id']]) === null) {
                $game = new Game();
                $game->setApiId($result['id'])
                    ->setGenres($result['genres'])
                    ->setName($result['name'])
                    ->setReleasedAt(new DateTime($result['released']))
                    ->setDevelopers($result['developers'])
                    ->setPlatforms($result['platforms']);
                $result['background-image'] === null ? $game->setImage('/images/review_default.jpg') : $game->setImage($result['background-image']);
                $manager->persist($game);
                dump('jeu créé !: ' . $game->getName());

                $review = new Review;
                $review->setApiGameId($game->getApiId())
                    ->setAuthor($admin)
                    ->setContent($faker->paragraphs(7, true))
                    ->setCreatedAt($faker->dateTime())
                    ->setGame($game)
                    ->setStatus('published')
                    ->setTitle($faker->text(15))
                    ->setSummary($faker->text(25));

                $manager->persist($review);
                dump('review créée!: ' . $review->getTitle());
            }
        }

        $manager->flush();
    }
}
