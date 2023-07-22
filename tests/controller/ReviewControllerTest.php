<?php

namespace App\Tests\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReviewControllerTest extends WebTestCase
{
    public function testIfOneReviewIsShown(): void
    {
        $client = static::createClient();
        self::bootKernel();
        $container = static::getContainer();
        /** @var ReviewRepository */
        $reviewRepo = $container->get(ReviewRepository::class);

        $reviews = $reviewRepo->findAll();
        $id = [];

        foreach ($reviews as $review) {
            array_push($id, (int) $review->getId());
        }

        $randomId = array_rand($id, 1);
        $review = $reviewRepo->find($id[$randomId]);

        $this->assertInstanceOf(Review::class, $review);

        $crawler = $client->request('GET', '/review/' . $review->getId());

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame("Papajoueur - Lire une review");
    }
}
