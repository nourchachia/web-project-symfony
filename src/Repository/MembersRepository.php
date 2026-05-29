<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
final class MembersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class); // ← User pas Member
    }

    public function findByFilter(string $column, string $value): array
    {
        $allowedColumns = ['firstname', 'lastname', 'department', 'fieldOfStudy', 'yearOfStudy']; // ← camelCase comme dans l'Entity

        if (!in_array($column, $allowedColumns)) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->where("u.{$column} LIKE :value")
            ->setParameter('value', "%{$value}%")
            ->getQuery()
            ->getResult();
    }
}