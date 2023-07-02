<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $admin = new User();
        $admin->setEmail("admin@papajoueur.fr")
            ->setPassword($this->hasher->hashPassword($admin, 'password'))
            ->setPasswordConfirm($admin->getPassword())
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        for ($i = 0; $i < 6; $i++) {
            $user = new User();
            $user->setEmail("user" . $i . "@papajoueur.fr")
                ->setPassword($this->hasher->hashPassword($user, 'password'))
                ->setPasswordConfirm($user->getPassword())
                ->setRoles(['ROLE_USER']);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
