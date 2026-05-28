<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\PendingRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApprovalService
{
    public function __construct(
        private PendingRegistrationRepository $pendingRepository,
        private EntityManagerInterface $entityManager,
        private Mailer $mailer,
    ) {}

    public function accept(string $id): array
    {
        $pending = $this->pendingRepository->find($id);
        if (!$pending) {
            return ['success' => false, 'message' => 'Row not found'];
        }

        $user = new User();
        $user->setFirstname($pending->getFirstname() ?? '');
        $user->setLastname($pending->getLastname() ?? '');
        $user->setEmail($pending->getEmail() ?? '');
        $user->setPassword($pending->getPassword() ?? '');
        $user->setPhone($pending->getPhone());
        $user->setBirthdate($pending->getBirthdate());
        $user->setDepartment($pending->getDepartment());
        $user->setFieldOfStudy($pending->getFieldOfStudy());
        $user->setYearOfStudy($pending->getYearOfStudy());
        $user->setPicture($pending->getPicture());
        $user->setRole('user');

        $this->entityManager->persist($user);
        $this->entityManager->remove($pending);
        $this->entityManager->flush();

        $this->mailer->send(
            $pending->getEmail() ?? '',
            'Welcome to Theatro INSAT!',
            EmailTemplates::accept($pending->getFirstname() ?? '', $pending->getLastname() ?? '')
        );

        return ['success' => true];
    }

    public function decline(string $id): array
    {
        $pending = $this->pendingRepository->find($id);
        if (!$pending) {
            return ['success' => false, 'message' => 'Row not found'];
        }

        $this->entityManager->remove($pending);
        $this->entityManager->flush();

        $this->mailer->send(
            $pending->getEmail() ?? '',
            'Your Theatro INSAT registration request',
            EmailTemplates::decline($pending->getFirstname() ?? '', $pending->getLastname() ?? '')
        );

        return ['success' => true];
    }
}