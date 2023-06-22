<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Services\QueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewsController extends AbstractController
{
  #[Route('/admin/reviews', name: 'app_admin_reviews')]
  public function adminHome(): Response
  {
    return $this->render('admin/reviews.html.twig', []);
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
    }

    // afficher les résultats et sélectionner un jeu
    return $this->render('admin/game_search.html.twig', [
      // envoyer à twig les résultats de la requête
      'results' => $results,
      'games' => $results !== null ? json_decode($results['success']) : null
    ]);
    // afficher la page de création de la review avec la possibilité de publier oude sauvegarder le brouillon
  }
}
