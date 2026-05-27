<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PendingRegistrationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PendingRegistrationRepository::class)]
#[ORM\Table(name: '`PendingRegistration`', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'PendingRegistration_email_key', columns: ['email']),
])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'An application with this email is already pending review.')]
class PendingRegistration
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', columnDefinition: 'UUID DEFAULT gen_random_uuid() NOT NULL')]
    private ?string $id = null;

    #[Assert\NotBlank(message: 'First name is required.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $firstname = null;

    #[Assert\NotBlank(message: 'Last name is required.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastname = null;

    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $password = null;

    #[Assert\Length(max: 30)]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $phone = null;

    #[Assert\Range(min: 1, max: 6, notInRangeMessage: 'Year of study must be between {{ min }} and {{ max }}.')]
    #[ORM\Column(name: 'yearofstudy', nullable: true)]
    private ?int $yearOfStudy = null;

    #[Assert\NotBlank(message: 'Please select your field of study.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(name: 'fieldofstudy', type: Types::TEXT, nullable: true)]
    private ?string $fieldOfStudy = null;

    #[Assert\NotBlank(message: 'Please select a department.')]
    #[Assert\Choice(choices: ['Acting', 'Music', 'Dancing', 'Writing', 'Media'], message: 'Please select a valid department.')]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $birthdate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $picture = null;

    #[Assert\NotBlank(message: 'Please tell us why you want to join.')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'Please tell us why you want to join in at least {{ limit }} characters.',
        maxMessage: 'Motivation must be {{ limit }} characters or less.'
    )]
    #[ORM\Column(name: 'whyjoin', type: Types::TEXT, nullable: true)]
    private ?string $whyJoin = null;

    #[ORM\Column(type: Types::STRING, nullable: true, columnDefinition: "registrationstatus DEFAULT 'pending'::registrationstatus")]
    private ?string $status = null;

    #[ORM\Column(name: 'createdat', nullable: true, columnDefinition: 'TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updatedat', nullable: true, columnDefinition: 'TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP')]
    private ?DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone ? trim($phone) : null;

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

    public function getFieldOfStudy(): ?string
    {
        return $this->fieldOfStudy;
    }

    public function setFieldOfStudy(string $fieldOfStudy): static
    {
        $this->fieldOfStudy = trim($fieldOfStudy);

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): static
    {
        $this->department = $department;

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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    public function getWhyJoin(): ?string
    {
        return $this->whyJoin;
    }

    public function setWhyJoin(string $whyJoin): static
    {
        $this->whyJoin = trim($whyJoin);

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

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
        $this->status ??= 'pending';
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
