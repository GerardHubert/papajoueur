<?php

declare(strict_types=1);

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
  public function testUserCanBeCreated()
  {
    $email = 'test@mail.com';
    $password = 'password';
    $confirmPassword = 'password';
    $role = ['ROLE_USER'];

    $user = new User;
    $user->setEmail($email)
      ->setPassword($password)
      ->setPasswordConfirm($confirmPassword)
      ->setRoles($role);
  }
}
