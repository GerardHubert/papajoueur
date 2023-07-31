<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentController extends AbstractController
{
  #[Route('/admin/comments', name: 'app_admin_comments')]
  public function showComments(ManagerRegistry $manager): Response
  {
    $comments = $manager->getRepository(Comment::class)->findAll();
    return $this->render('admin/comments.html.twig', [
      'comments' => $comments
    ]);
  }

  #[Route('/admin/comments/reported', name: 'app_admin_comments_reported')]
  public function showReportedComments(EntityManagerInterface $manager)
  {
    /** @var CommentRepository */
    $commentRepo = $manager->getRepository(Comment::class);

    $reportedComments = $commentRepo->findByReportedStatus(true);

    return $this->render('admin/reported_comments.html.twig', [
      'comments' => $reportedComments
    ]);
  }

  #[Route('/admin/comment/delete/{id}', name: 'app_admin_comment_delete')]
  public function delete(int $id, EntityManagerInterface $manager): Response
  {
    $comment = $manager->getRepository(Comment::class)->find($id);

    // commentaire non trouvé
    if ($comment === null) {
      $this->addFlash('error', 'Commentaire non trouvé');
    }

    // message de confirmation
    if ($comment !== null) {
      $manager->remove($comment);
      $manager->flush();
      $this->addFlash('success', 'le commentaire a bien été supprimé');
    }

    // retour sur la page des commentaires
    return $this->redirectToRoute('app_admin_comments_reported');
  }

  #[Route('/admin/comment/allow/{id}', name: 'app_admin_comment_allow')]
  public function allowComment(int $id, EntityManagerInterface $manager): Response
  {
    $comment = $manager->getRepository(Comment::class)->find($id);

    if ($comment === null) {
      $this->addFlash('error', 'Aucun commentaire trouvé');
    }

    if ($comment instanceof Comment) {
      $comment->setReported(false);
      $manager->persist($comment);
      $manager->flush();
      $this->addFlash('success', 'Le commentaire a été autorisé');
    }

    return $this->redirectToRoute('app_admin_comments_reported');
  }
}
