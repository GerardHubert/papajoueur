<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
  // La page d'accueil affiche les X derniers posts
  #[Route('/', name: 'app_home')]
  public function home(EntityManagerInterface $em): Response
  {
    /** @var ReviewRepository */
    $reviewRepo = $em->getRepository(Review::class);
    $lastTenReviews = $reviewRepo->findLastTenReviews();

    return $this->render('home.html.twig', [
      'reviews' => $lastTenReviews
    ]);
  }
}
