<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return list<Ticket>
     */
    public function findByShowId(string $showId): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.show', 'sh')
            ->innerJoin('t.seat', 'se')
            ->addSelect('se')
            ->andWhere('sh.id = :showId')
            ->setParameter('showId', $showId)
            ->getQuery()
            ->getResult();
    }

    public function existsByShowAndSeat(string $showId, int $seatId): bool
    {
        $count = (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.show', 'sh')
            ->innerJoin('t.seat', 'se')
            ->andWhere('sh.id = :showId')
            ->andWhere('se.id = :seatId')
            ->setParameter('showId', $showId)
            ->setParameter('seatId', $seatId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function deleteByShowAndSeat(string $showId, int $seatId): int
    {
        $ticket = $this->createQueryBuilder('t')
            ->innerJoin('t.show', 'sh')
            ->innerJoin('t.seat', 'se')
            ->andWhere('sh.id = :showId')
            ->andWhere('se.id = :seatId')
            ->setParameter('showId', $showId)
            ->setParameter('seatId', $seatId)
            ->getQuery()
            ->getOneOrNullResult();

        if ($ticket === null) {
            return 0;
        }

        $this->getEntityManager()->remove($ticket);
        $this->getEntityManager()->flush();

        return 1;
    }
}
