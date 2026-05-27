<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordReset;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordReset>
 */
final class PasswordResetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordReset::class);
    }

    public function findValidReset(User $user, string $plainCode): ?PasswordReset
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.tokenHash = :tokenHash')
            ->andWhere('p.used = false')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('tokenHash', hash('sha256', $plainCode))
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
