<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PasswordReset;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordResetRepository $passwordResetRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordResetMailer $passwordResetMailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function requestResetCode(string $email): ?string
    {
        $user = $this->userRepository->findOneByEmail($email);

        if ($user === null) {
            return null;
        }

        $code = (string) random_int(100000, 999999);
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT15M'));

        $passwordReset = (new PasswordReset())
            ->setUser($user)
            ->setTokenHash(hash('sha256', $code))
            ->setExpiresAt($expiresAt);

        $this->entityManager->persist($passwordReset);
        $this->entityManager->flush();

        $this->passwordResetMailer->sendResetCode($user, $code, $expiresAt);

        return $code;
    }

    public function resetPassword(string $email, string $code, string $plainPassword): bool
    {
        $user = $this->userRepository->findOneByEmail($email);

        if ($user === null) {
            return false;
        }

        $passwordReset = $this->passwordResetRepository->findValidReset($user, preg_replace('/\s+/', '', $code));

        if ($passwordReset === null) {
            return false;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $passwordReset->markUsed();

        $this->entityManager->flush();

        return true;
    }
}
