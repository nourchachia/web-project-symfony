<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PendingRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PendingRegistration>
 */
final class PendingRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PendingRegistration::class);
    }

    public function emailExists(string $email): bool
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]) !== null;
    }
}
