<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\ForgottenPasswordType;
use App\Services\MailerService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;

class ForgottenPasswordController extends AbstractController
{
  #[Route('forgotten_password', name: 'app_forgotten_password')]
  public function forgottenPassword(Request $request, EntityManagerInterface $em, MailerService $mailerService): Response
  {
    $error = null;
    // get a user from email or null if email not found
    // from the method post
    if ($request->getMethod() === 'POST') {
      $submittedToken = $request->request->get('forgotten_token');
      if ($this->isCsrfTokenValid('forgotten-password-token', $submittedToken)) {
        $email = filter_var($request->request->get('email'), FILTER_SANITIZE_EMAIL);

        /**  @var UserRepository */
        $repo = $em->getRepository(User::class);

        $user = $repo->findOneByEmail($email);

        if ($user === null) {
          $this->addFlash('error', 'Aucun utilisateur trouvé avec cette adresse');
        }

        if ($user) {
          // enregistrer le token et la date d'envoi du token dans la bdd
          $user->setToken($submittedToken)
            ->setTokenCreatedAt(new DateTime('now'));
          $em->persist($user);
          $em->flush();

          // à partir de la date d'envoi, définir un délai avant expiration du token
          $mailerService->forgottenPassword(
            'admin@papajoueur.fr',
            $user,
            "Mot de pass oublié"
          );
        }
      }
    }

    return $this->render('login/forgotten_password.html.twig', [
      'error' => $error
    ]);
  }
}
