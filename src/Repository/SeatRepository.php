<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Seat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seat>
 */
class SeatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seat::class);
    }

    /**
     * @return list<Seat>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('CASE s.section WHEN \'left\' THEN 1 WHEN \'center\' THEN 2 WHEN \'right\' THEN 3 ELSE 4 END AS HIDDEN section_order')
            ->orderBy('section_order', 'ASC')
            ->addOrderBy('s.row', 'DESC')
            ->addOrderBy('s.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCoordinates(string $section, string $row, int $number): ?Seat
    {
        return $this->createQueryBuilder('s')
            ->andWhere('LOWER(TRIM(s.section)) = LOWER(TRIM(:section))')
            ->andWhere('UPPER(TRIM(s.row)) = UPPER(TRIM(:row))')
            ->andWhere('s.number = :number')
            ->setParameter('section', $section)
            ->setParameter('row', $row)
            ->setParameter('number', $number)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
