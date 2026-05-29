<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WorkshopRegistration;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkshopRegistration>
 */
final class WorkshopRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkshopRegistration::class);
    }

    /**
     * @return list<array{id: string, idUser: string, idWorkshop: string, rating: int, createdAt: string|null, workshop: string|null, firstname: string|null, lastname: string|null, identifier: string|null}>
     */
    public function findAllWithDetails(): array
    {
        $rows = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select(
                'wr.id',
                'wr.id_user AS "idUser"',
                'wr.id_workshop AS "idWorkshop"',
                'wr.rating',
                'wr.created_at AS "createdAt"',
                'w.title AS workshop',
                'u.firstname',
                'u.lastname',
                'u.email AS identifier',
            )
            ->from('workshop_registrations', 'wr')
            ->innerJoin('wr', 'users', 'u', 'u.id = wr.id_user')
            ->innerJoin('wr', 'workshops', 'w', 'w.id = wr.id_workshop')
            ->orderBy('wr.created_at', 'DESC')
            ->fetchAllAssociative();

        return array_map(static function (array $row): array {
            return [
                'id' => (string) $row['id'],
                'idUser' => (string) $row['idUser'],
                'idWorkshop' => (string) $row['idWorkshop'],
                'rating' => (int) $row['rating'],
                'createdAt' => self::formatDate($row['createdAt'] ?? null),
                'workshop' => $row['workshop'] !== null ? (string) $row['workshop'] : null,
                'firstname' => $row['firstname'] !== null ? (string) $row['firstname'] : null,
                'lastname' => $row['lastname'] !== null ? (string) $row['lastname'] : null,
                'identifier' => $row['identifier'] !== null ? (string) $row['identifier'] : null,
            ];
        }, $rows);
    }

    private static function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (new DateTimeImmutable((string) $value))->format(DateTimeInterface::ATOM);
    }
}
