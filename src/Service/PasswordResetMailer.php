<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class PasswordResetMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerSender,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendResetCode(User $user, string $code, DateTimeImmutable $expiresAt): void
    {
        $resetEmail = (new TemplatedEmail())
            ->from(new Address($this->mailerSender, 'Theatro INSAT'))
            ->to((string) $user->getEmail())
            ->subject('Your Theatro INSAT password reset code')
            ->htmlTemplate('email/password_reset.html.twig')
            ->context([
                'firstname' => $user->getFirstname(),
                'code' => $code,
                'expiresAt' => $expiresAt,
            ]);

        $this->mailer->send($resetEmail);
    }
}
