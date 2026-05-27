<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;

final class ContactMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $contactEmail,
        private readonly string $mailerSender,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(string $fullname, string $email, string $message, DateTimeImmutable $sentAt): void
    {
        $contactEmail = (new TemplatedEmail())
            ->from(new Address($this->mailerSender, 'Theatro INSAT'))
            ->to($this->contactEmail)
            ->replyTo(new Address($email, $fullname))
            ->subject('[Theatro Website] Message from ' . $fullname)
            ->htmlTemplate('email/contact.html.twig')
            ->context([
                'fullname' => $fullname,
                'visitorEmail' => $email,
                'message' => $message,
                'sentAt' => $sentAt,
            ]);

        $this->mailer->send($contactEmail);
    }
}
