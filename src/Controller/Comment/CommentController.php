<?php

declare(strict_types=1);

namespace App\Controller\Comment;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Comment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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

    // vérifier le token csrf et si un utilisateur est bien connecté
    $submittedToken = $request->request->get('comment-token');
    if ($user === null || $review === null) {
      $this->addFlash('error', 'Une erreur est survenue: utilisateur ou review non trouvé');
      return $this->redirectToRoute('app_review_show_one', ['id' => $reviewId], 303);
    }

    if ($this->isCsrfTokenValid('new-comment-token', $submittedToken) === false) {
      $this->addFlash('error', 'Une erreur est survenue...');
      return $this->redirectToRoute('app_review_show_one', ['id' => $reviewId], 303);
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

  #[Route('/comment/feeling/{id}', name: 'app_comment_feeling')]
  public function handleLikes(int $id, EntityManagerInterface $manager, Request $request): Response
  {
    // obtenir la route pour définir l'action (like ou dislike)
    $action = $request->query->get('action');

    /** @var Comment */
    $comment = $manager->getRepository(Comment::class)->find($id);
    $review = $comment->getReview();

    if ($comment === null) {
      $this->addFlash('error', "Ce commentaire n'existe pas");
      return $this->redirectToRoute('app_review_show_one', [
        'id' => $review->getId()
      ]);
    }

    if ($action === 'like') {
      $comment->setLikes($comment->getLikes() + 1);
    };

    if ($action === 'dislike') {
      $comment->setDislikes($comment->getDislikes() + 1);
    }

    $manager->persist($comment);
    $manager->flush();

    return $this->redirect($this->generateUrl('app_review_show_one', ['id' => $review->getId()]) . "#comment-" . $comment->getId());
  }

  #[Route('/comment/report/{id}', name: 'app_comment_report')]
  public function handleReport(int $id, EntityManagerInterface $manager): Response
  {
    $comment = $manager->getRepository(Comment::class)->find($id);
    $review = $comment->getReview();

    if ($comment === null) {
      $this->addFlash('error', "Ce commentaire n'existe pas");
      return $this->redirectToRoute('app_review_show_one', [
        'id' => $review->getId()
      ]);
    }

    $comment->setReported(true);
    $manager->persist($comment);
    $manager->flush();

    return $this->redirect($this->generateUrl('app_review_show_one', ['id' => $review->getId()]) . '#comment-' . $comment->getId());
  }
}
