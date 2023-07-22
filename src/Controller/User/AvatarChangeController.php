<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User;
use App\Services\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AvatarChangeController extends AbstractController
{
  #[Route('/user/{id}', name: 'app_avatar_change')]
  public function avatarChange(?User $user, Request $request, EntityManagerInterface $em): Response
  {
    /** vérifier si le resolver trouve un utilisateur */
    if (!$user) {
      $this->addFlash('error', 'Aucun utilisateur trouvé');
      return $this->redirectToRoute('app_home');
    }

    /** vérifier si l'utilisateur trouvé correpsond à l'utilisateur connecté */
    if ($user !== $this->getUser()) {
      $this->addFlash('error', 'Les utilisateurs ne corresopndent pas');
      return $this->redirectToRoute('app_home');
    }

    /** on traite le formulaire si tout va bien */
    if ($request->getMethod() === 'POST') {
      try {
        $upload = FileUploadService::avatarUpload($_FILES['avatar-change']);
        $user->setAvatar($upload[1]);
      } catch (\Throwable $exc) {
        $this->addFlash('error', $exc->getMessage());
        return $this->redirectToRoute('app_home');
      }

      $em->persist($user);
      $em->flush();

      $this->addFlash('success', 'Image de profil modifiée avec succès');
    }

    return $this->render('user/avatar_change.html.twig', []);
  }
}
