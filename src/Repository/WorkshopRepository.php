<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Workshop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workshop>
 */
final class WorkshopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workshop::class);
    }

    /**
     * @return list<Workshop>
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.date', 'ASC')
            ->addOrderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
