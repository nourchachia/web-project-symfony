<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkshopRegistrationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkshopRegistrationRepository::class)]
#[ORM\Table(name: 'workshop_registrations')]
#[ORM\HasLifecycleCallbacks]
class WorkshopRegistration
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', columnDefinition: 'UUID DEFAULT gen_random_uuid() NOT NULL')]
    private ?string $id = null;

    #[ORM\Column(name: 'id_user', type: 'guid', columnDefinition: 'UUID NOT NULL')]
    private ?string $idUser = null;

    #[ORM\Column(name: 'id_workshop', type: 'guid', columnDefinition: 'UUID NOT NULL')]
    private ?string $idWorkshop = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $rating = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE, nullable: true, columnDefinition: 'TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP')]
    private ?DateTimeImmutable $createdAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }

    public function setIdUser(string $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getIdWorkshop(): ?string
    {
        return $this->idWorkshop;
    }

    public function setIdWorkshop(string $idWorkshop): static
    {
        $this->idWorkshop = $idWorkshop;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setInitialValues(): void
    {
        $this->id ??= self::generateUuidV4();
        $this->createdAt ??= new DateTimeImmutable();
    }

    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
