<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailerService extends AbstractController
{
  public function __construct(private MailerInterface $mailer)
  {
  }
  public function forgottenPassword(string $from, User $user, string $subject): void
  {
    $email = new TemplatedEmail();
    $email->from($from)
      ->to('mikado842@gmail.com')
      ->subject($subject)
      ->htmlTemplate('emails/forgotten_password.html.twig')
      ->context([
        'user' => $user
      ]);

    try {
      $this->mailer->send($email);
      $this->addFlash('success', "Email envoyé avec succès");
    } catch (TransportException $exception) {
      $this->addFlash('error', "Une erreur est survenue, merci de réessayer");
    }
  }
}
