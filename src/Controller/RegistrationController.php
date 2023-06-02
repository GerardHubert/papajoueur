<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
  #[Route('/registration', name: 'app_registration')]
  public function registration(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
  {
    $user = new User();
    $user->setRoles(['ROLE_USER']); //default role at registration;
    $registrationForm = $this->createForm(RegistrationFormType::class, $user);

    $registrationForm->handleRequest($request);
    $errors = $validator->validate($user);

    if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
      // hash du mot de passe
      $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
      // suppression de la confirmation du mot de passe
      $user->setPasswordConfirm('');

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
}
