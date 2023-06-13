<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ResetPasswordController extends AbstractController
{
  #[Route('/reset_password/{userId}/{token}', name: 'app_reset_password')]
  public function resetPassword(
    int $userId,
    string $token,
    Request $request,
    EntityManagerInterface $em,
    ValidatorInterface $validator,
    UserPasswordHasherInterface $passwordHasher
  ): Response {

    /** @var User */
    $user = $em->getRepository(User::class)->find($userId);

    // si l'id ne permet pas de trouver un user, on redirige
    if (!$user) {
      $this->addFlash('error', 'Aucun utilisateur trouvé avec cet ID');
      return $this->redirectToRoute("app_login");
    }

    // si les tokens ne correspondent, on redirige
    if ($user && $token !== $user->getToken()) {
      $this->addFlash('error', 'Les tokens ne correspondent pas');
      return $this->redirectToRoute("app_login");
    }

    // si la durée de validité du token est expirée, on redirige
    $tokenCreatedAt = $user->getTokenCreatedAt();
    $interval = $tokenCreatedAt->diff(new DateTime('now'), false)->i;
    if ($interval > 15) {
      $this->addFlash('error', 'Le lien a expiré, demandes-en un autre !');
      return $this->redirectToroute("app_forgotten_password");
    }

    // si tout va bien, on traite
    // validation du mot de passe (longueur de 4 caractères et identiques au password confirm)
    $errors = null;
    if ($request->getMethod() === 'POST') {
      $form = $request->request->all();

      $user->setPassword($form['password'])
        ->setPasswordConfirm($form['confirm-password']);

      $errors = $validator->validate($user);

      if (count($errors) === 0) {
        $user->setPassword($passwordHasher->hashPassword($user, $form['password']))
          ->setPasswordConfirm('');

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Ton mot de passe a bien été modifié.');
        return $this->redirectToRoute('app_login');
      }
    }

    return $this->render('login/reset_password.html.twig', [
      'user' => $user,
      'token' => $token,
      'errors' => $errors
    ]);
  }
}
