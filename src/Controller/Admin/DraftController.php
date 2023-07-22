<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DraftController extends AbstractController
{
  #[Route('/admin/drafts', name: 'app_admin_drafts')]
  public function showDrafts(EntityManagerInterface $em): Response
  {
    /** @var ReviewRepository */
    $reviewRepo = $em->getRepository(Review::class);
    $drafts = $reviewRepo->findBy(['status' => 'draft'], ['createdAt' => 'DESC']);
    return $this->render('admin/reviews.html.twig', [
      'reviews' => $drafts
    ]);
  }
}
