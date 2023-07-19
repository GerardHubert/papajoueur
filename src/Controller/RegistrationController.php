<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Services\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function PHPUnit\Framework\isEmpty;

class RegistrationController extends AbstractController
{
  public function __construct(private ValidatorInterface $validator)
  {
  }

  #[Route('/registration', name: 'app_registration')]
  public function registration(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
  {
    $user = new User();
    $user->setRoles(['ROLE_USER']); //default role at registration;
    $registrationForm = $this->createForm(RegistrationFormType::class, $user);

    $registrationForm->handleRequest($request);

    $errors = $this->validation($user);

    if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
      // hash du mot de passe
      $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

      // suppression de la confirmation du mot de passe
      $user->setPasswordConfirm('');

      // si l'utilisateur n'envoie aucun fichier, on attribue un avatar par défaut
      if (empty($_FILES) || (isset(($_FILES)) && $_FILES['file']['error'] === 4)) {
        $user->setAvatar('images/default_user.png');
        // sinon, on gère l'upload d'une image en avatar
      } else {
        try {
          $upload = FileUploadService::avatarUpload($_FILES['file']);
          $user->setAvatar($upload[1]);
        } catch (\Throwable $exc) {
          $this->addFlash('error', $exc->getMessage());
          return $this->redirectToRoute('app_registration');
        }
      }

      $entityManager->persist($user);
      $entityManager->flush();

      //redirect to login page with a confirmation flash message
      $this->addFlash('success', "Ton compte a bien été créé. Maintenant connecte-toi !");
      return $this->redirectToRoute('app_login');
    }

    return $this->render('registration/registration.html.twig', [
      'registration' => $registrationForm,
      'errors' => $errors
    ]);
  }

  public function validation(User $user)
  {
    return $this->validator->validate($user);
  }
}
