<?php

namespace App\Tests\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class RegistrationTest extends WebTestCase
{
    public function generateAnInexistantUserEmail(): string
    {
        /** @var UserRepository
         * on utilise la longueur de la liste des utilisateurs pour générer une adresse unique
         */
        $userRepo = static::getContainer()->get(UserRepository::class);
        $users = $userRepo->findAll();
        $email = "test-user" . count($users) . "@papunit.fr";
        return $email;
    }

    public function generateRegistrationForm(Crawler $crawler): Form
    {
        $button = $crawler->selectButton('envoyer');
        $form = $button->form();
        $form['registration_form[email]'] = $this->generateAnInexistantUserEmail();
        $form['registration_form[password]'] = 'password123';
        $form['registration_form[password_confirm]'] = 'password123';

        return $form;
    }

    /**
     * tester l'enregistrement d'un nouvel utilisateur
     * après l'ajout du formulaire, comparer la liste des users initiale et arpès l'update pour vérifier l'ajout et cérifier si le nouvel utilisateur figure dans la liste mise à jour
     *
     * @return void
     */
    public function testNewUserIsRegistered(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/registration');

        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);
        $initialUsersList = $userRepo->findAll();

        $client->submit($this->generateRegistrationForm($crawler));

        $updatedUsersList = $userRepo->findAll();

        $this->assertTrue(count($initialUsersList) < count($updatedUsersList));
        $this->assertResponseRedirects('/login', 302, 'redirection après enregistrement réussie');
    }

    public function testNoRegistrationIfPasswordsAreDifferent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/registration');

        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);
        $initialUsersList = $userRepo->findAll();

        $form = $this->generateRegistrationForm($crawler);
        $form['registration_form[password_confirm]'] = 'password456';
        $client->submit($form);

        $updatedUsersList = $userRepo->findAll();

        $this->assertTrue(count($initialUsersList) === count($updatedUsersList));
        $this->assertSelectorTextContains("p", "Les mots de passe ne correspondent pas");
    }

    public function testNoRegistrationIfEmailExists(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/registration');

        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);

        $initialUsersList = $userRepo->findAll();
        $randomUser = $initialUsersList[array_rand($initialUsersList, 1)];

        $form = $this->generateRegistrationForm($crawler);
        $form['registration_form[email]'] = $randomUser->getEmail();
        $client->submit($form);

        $updatedUsersList = $userRepo->findAll();

        $this->assertTrue(count($initialUsersList) === count($updatedUsersList));
        $this->assertSelectorTextContains("p", "Un compte avec cette adresse mail existe déjà");
    }

    public function testNoRegistrationIfPasswordIsTooShort(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/registration');

        /** @var UserRepository */
        $userRepo = static::getContainer()->get(UserRepository::class);

        $initialUsersList = $userRepo->findAll();

        $form = $this->generateRegistrationForm($crawler);
        $form['registration_form[password]'] = 123;
        $form['registration_form[password_confirm]'] = 123;
        $client->submit($form);

        $updatedUsersList = $userRepo->findAll();

        $this->assertTrue(count($initialUsersList) === count($updatedUsersList));
        $this->assertSelectorTextContains("p", "Le mot de passe doit faire au moins 4 caractères");
    }
}
