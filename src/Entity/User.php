<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'User_email_key', columns: ['email']),
])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'An account with this email already exists.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', columnDefinition: 'UUID DEFAULT gen_random_uuid() NOT NULL')]
    private ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter an email address.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Please enter a first name.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $firstname = null;

    #[Assert\NotBlank(message: 'Please enter a last name.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $birthdate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(name: 'fieldofstudy', type: Types::TEXT, nullable: true)]
    private ?string $fieldOfStudy = null;

    #[ORM\Column(name: 'yearofstudy', nullable: true)]
    private ?int $yearOfStudy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(name: 'createdat', nullable: true, columnDefinition: 'TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updatedat', nullable: true, columnDefinition: 'TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP')]
    private ?DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role !== null && $this->role !== '') {
            $role = strtoupper($this->role);
            $roles[] = str_starts_with($role, 'ROLE_') ? $role : 'ROLE_'.$role;
        }

        return array_unique($roles);
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role ? trim($role) : null;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = trim($firstname);

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = trim($lastname);

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone ? trim($phone) : null;

        return $this;
    }

    public function getBirthdate(): ?DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function setBirthdate(?DateTimeImmutable $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department ? trim($department) : null;

        return $this;
    }

    public function getFieldOfStudy(): ?string
    {
        return $this->fieldOfStudy;
    }

    public function setFieldOfStudy(?string $fieldOfStudy): static
    {
        $this->fieldOfStudy = $fieldOfStudy ? trim($fieldOfStudy) : null;

        return $this;
    }

    public function getYearOfStudy(): ?int
    {
        return $this->yearOfStudy;
    }

    public function setYearOfStudy(?int $yearOfStudy): static
    {
        $this->yearOfStudy = $yearOfStudy;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function setInitialValues(): void
    {
        $this->id ??= self::generateUuidV4();
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
