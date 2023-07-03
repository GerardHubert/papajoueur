<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Review;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{

  #[Route('/review/{id}', name: 'app_review_show_one')]
  public function showOneReview(Review $review): Response
  {
    dump($review->getGame()->getGenres());
    return $this->render('review_show.html.twig', [
      'review' => $review
    ]);
  }
}
