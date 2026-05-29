<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\MembersRepository;
use Doctrine\ORM\EntityManagerInterface;

class MembersService
{
    public function __construct(
        private MembersRepository $membersRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function getAll(): array
    {
        return $this->membersRepository->findAll();
    }

    public function getById(string $id): ?User  // ← string car UUID
    {
        return $this->membersRepository->find($id);
    }

    public function getByFilter(string $column, string $value): array
    {
        return $this->membersRepository->findByFilter($column, $value);
    }

    public function delete(string $id): array  // ← string car UUID
    {
        $member = $this->membersRepository->find($id);

        if (!$member) {
            return ['success' => false, 'message' => 'Member not found'];
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();

        return ['success' => true];
    }

    public function update(string $id, array $data): array  // ← string car UUID
    {
        $member = $this->membersRepository->find($id);

        if (!$member) {
            return ['success' => false, 'message' => 'Member not found'];
        }

        if (isset($data['firstname']))   $member->setFirstname($data['firstname']);
        if (isset($data['lastname']))    $member->setLastname($data['lastname']);
        if (isset($data['department']))  $member->setDepartment($data['department']);
        if (isset($data['fieldofstudy'])) $member->setFieldOfStudy($data['fieldofstudy']); // ← setFieldOfStudy
        if (isset($data['yearofstudy']))  $member->setYearOfStudy((int)$data['yearofstudy']); // ← setYearOfStudy + cast int
        if (isset($data['picture']))     $member->setPicture($data['picture']);

        $this->entityManager->flush();

        return ['success' => true];
    }
}