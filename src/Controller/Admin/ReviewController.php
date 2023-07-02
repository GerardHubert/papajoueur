<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\Review;
use App\Form\ReviewFormType;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Services\QueryService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
  // landpage du dashboard
  #[Route('/admin/reviews', name: 'app_admin_reviews')]
  public function adminHome(EntityManagerInterface $em): Response
  {
    /** @var ReviewRepository */
    $reviewRepo = $em->getRepository(Review::class);
    $reviews = $reviewRepo->findBy(['status' => 'published']);

    return $this->render('admin/reviews.html.twig', [
      'reviews' => $reviews
    ]);
  }

  #[Route('/admin/game_search', name: 'app_admin_game_search')]
  public function gameSearch(Request $request, QueryService $queryService): Response
  {
    $results = null;
    // lorsque le formulaire est soumis
    if ($request->getMethod() === 'POST') {
      $form = $request->request->all();

      // Vérification du token de sécurité
      if ($this->isCsrfTokenValid('game-search', $form['token']) === false) {
        $this->addFlash('error', "Une erreur est survenue");
        return $this->redirectToRoute('app_admin_game_search');
      }

      // si les tokens sont ok, on lance la requête via le service dédié
      $query = urlencode(filter_var($form['query'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
      $key = $_ENV['RAWG_API_KEY'];
      $platform = (int) $form['platform'];
      $results = $queryService->search($key, $query, $platform);

      // le service retourne un tableau à 2 entrées: success et failure
      // si la requête s'est bien passée: failure est vide et success est un json
      // sinon failure est de type string indiquant l'erreur et success est false
      if ($results['success'] === false) {
        $this->addFlash('error', $results['failure']);
        return $this->redirectToRoute('app_admin_game_search');
      }

      //  c'est ici qu'on traite la réponse de l'api s'il y a une erreur
      //  après avoir converti la réponse json en array
      $array = json_decode($results['success'], true);
      foreach ($array as $key => $value) {
        if ($key === 'error') {
          $this->addFlash('error', $value);
          return $this->redirectToRoute('app_admin_game_search');
        }
      }
    }

    return $this->render('admin/game_search.html.twig', [
      // envoyer à twig les résultats de la requête
      'results' => $results,
      'games' => $results !== null ? json_decode($results['success']) : null
    ]);
  }

  // Route pour la création d'une review
  // recoit l'id du jeu dans l'api
  #[Route('/admin/review/new/{gameId}', name: 'app_admin_review_new')]
  public function createReview(int $gameId, QueryService $queryService, EntityManagerInterface $em, Request $request): Response
  {
    $result = null;
    // récupération des données de jeu avec l'id
    $query = $queryService->findById($gameId, $_ENV['RAWG_API_KEY']);
    if ($query['game'] === false) {
      $this->addFlash('error', $query['failure']);
      return $this->redirectToRoute('app_admin_review_new');
    }

    // si la requête se passe bien, on enregistre une nouvelle entité Game
    $result = json_decode($query['game'], true);

    // vérifier si le jeu existe déjà dans la bdd
    /** @var GameRepository */
    $gameRepo = $em->getRepository(Game::class);
    $game = new Game;

    if ($gameRepo->findOneBy(['apiId' => $result['id']]) === null) {
      $game->setApiId($result['id'])
        ->setGenres($result['genres'])
        ->setName($result['name'])
        ->setReleasedAt(new DateTime($result['released']))
        ->setDevelopers($result['developers'])
        ->setPlatforms($result['platforms']);
      $em->persist($game);
      $em->flush();
    }

    if ($gameRepo->findOneBy(['apiId' => $result['id']]) !== null) {
      $game = $gameRepo->findOneBy(['apiId' => $result['id']]);
    }

    // on affiche la page de rédaction avec le nom du jeu pré-rempli
    return $this->render('admin/new_review.html.twig', [
      'game' => $game
    ]);
  }

  #[Route('/admin/review/add/{gameId}', name: 'app_admin_review_add')]
  public function addReview(int $gameId, Request $request, EntityManagerInterface $em)
  {
    /** @var GameRepository */
    $gameRepo = $em->getRepository(Game::class);
    $game = $gameRepo->findOneBy(['apiId' => $gameId]);
    $submittedToken = $request->request->get('new-review-token');
    if ($this->isCsrfTokenValid('new_review_token', $submittedToken) === false) {
      $this->addFlash('error', "Vous n'avez pas accès à cette page");
      return $this->redirectToRoute('app_admin_review_new', ['gameId' => $gameId]);
    }

    $action = filter_var($request->query->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);
    $action === 'publish' ? $status = 'published' : $status = 'draft';
    $data = $request->request->all();
    $review = new Review;

    $review->setApiGameId((int) $data['game-api-id'])
      ->setAuthor($this->getUser())
      ->setContent($data['review-content'])
      ->setCreatedAt(new DateTime('now'))
      ->setGame($game)
      ->setStatus($status)
      ->setTitle($data['new-review-title']);
    $em->persist($review);
    $em->flush();

    $this->addFlash('success', 'La review a bien été publiée');

    return $this->redirectToRoute('app_admin_reviews');
  }

  #[Route('/admin/review/update/{id}', name: 'app_admin_review_update')]
  public function update(Review $reviewToUpdate, int $id, EntityManagerInterface $em, Request $request): Response
  {
    if ($request->getMethod() === 'POST') {
      $data = $request->request->all();

      if ($this->isCsrfTokenValid('update_review_token', $data['update-review-token']) === false) {
        $this->addFlash('error', "Vous n'êtes pas autorisé à accéder à cette page");
        return $this->redirectToRoute('app_admin_review_update', ['id' => $id]);
      };

      // modifier la review to update avec les données du formulaire
      $reviewToUpdate->setContent($data['review-content'])
        ->setTitle($data['update-review-title']);

      // s'il s'agit d'un brouillon et qu'on  le publie
      if (key_exists('action', $request->query->all())) {
        $param = filter_var($request->query->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);
        if ($param === 'publish') {
          $reviewToUpdate->setStatus('published');
        }
      }

      // mettre à jour la base de données
      $em->persist($reviewToUpdate);
      $em->flush();

      $this->addFlash('success', 'Les modifications ont bien été enregistrées');
    }

    return $this->render('admin/review_update.html.twig', [
      'review' => $reviewToUpdate,
    ]);
  }

  #[Route('/admin/review/delete/{id}', name: 'app_admin_review_delete')]
  public function delete(Review $reviewToDelete, EntityManagerInterface $em): Response
  {
    $em->remove($reviewToDelete);
    $em->flush();
    $this->addFlash('success', 'La review a bien été supprimée');
    return $this->redirectToRoute('app_admin_reviews');
  }
}
