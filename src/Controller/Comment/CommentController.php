<?php

declare(strict_types=1);

namespace App\Controller\Comment;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Comment;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentController extends AbstractController
{

  #[Route('/comment/add/{userId}/{reviewId}', name: 'app_comment_add')]
  public function add(int $userId, int $reviewId, Request $request, ManagerRegistry $manager, EntityManagerInterface $em): Response
  {
    $user = $manager->getRepository(User::class)->find($userId);
    $review = $manager->getRepository(Review::class)->find($reviewId);

    // vérifier le token csrf
    $submittedToken = $request->request->get('comment-token');
    if (
      $this->isCsrfTokenValid('new-comment-token', $submittedToken) === false
      || $user === null
      || $review === null
    ) {
      $this->addFlash('error', 'Une erreur est survenue...');
      return $this->redirectToRoute('app_review_show_one', ['id' => $reviewId]);
    }

    // nettoyer l'input
    $cleanedInput = filter_var($request->request->get('comment'), FILTER_SANITIZE_SPECIAL_CHARS);

    // enregistrer le nouveau commentaire
    $comment = new Comment;
    $comment->setAuthor($user)
      ->setContent($cleanedInput)
      ->setCreatedAt(new DateTime('now'))
      ->setReview($review)
      ->setLikes(0)
      ->setDislikes(0)
      ->setReported(false);

    $em->persist($comment);
    $em->flush();

    return $this->redirectToRoute('app_review_show_one', ['id' => $reviewId]);
  }
}
