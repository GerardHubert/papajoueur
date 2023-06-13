<?php

declare(strict_types=1);

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use App\Controller\RegistrationController;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;

final class RegistrationControllerTest extends TestCase
{
  private $user;
  private $validator;
  private $violationList;

  protected function setUp(): void
  {
    $this->user = new User;
    $this->user->setEmail('test@phpunit.fr')
      ->setPassword('password')
      ->setPasswordConfirm('password')
      ->setRoles(['ROLE_USER']);

    $this->validator = Validation::createValidator();
    $this->violationList = ConstraintViolationList::class;
  }

  public function testCannotRegisterIfPasswordTooShort()
  {
    $this->user->setPassword('tes');
    dd(
      $this->user,
      $this->validator->validate($this->user),
      $this->violationList
    );
  }
}
