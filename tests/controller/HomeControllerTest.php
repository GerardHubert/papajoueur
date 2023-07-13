<?php

namespace App\Tests\Controller;

use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testIfHomeIsShown(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Papajoueur - Accueil');
    }

    public function testIfTenReviewsAreFetched(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var ReviewRepository */
        $reviewRepo = $container->get(ReviewRepository::class);

        $reviews = $reviewRepo->findLastTenReviews();

        $this->assertCount(10, $reviews, "accueil doit récupérer 10 reviews");
    }
}
