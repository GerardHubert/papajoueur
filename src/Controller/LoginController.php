<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        $user = new User();

        $loginForm = $this->createForm(LoginFormType::class, $user);

        return $this->render('login/login.html.twig', [
            'loginform' => $loginForm,
        ]);
    }
}
