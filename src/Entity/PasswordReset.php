<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PasswordResetRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasswordResetRepository::class)]
#[ORM\Table(name: 'password_resets')]
#[ORM\HasLifecycleCallbacks]
class PasswordReset
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', columnDefinition: 'UUID DEFAULT gen_random_uuid() NOT NULL')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'token_hash', type: Types::TEXT)]
    private ?string $tokenHash = null;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(columnDefinition: 'BOOLEAN DEFAULT false NOT NULL')]
    private bool $used = false;

    #[ORM\Column(name: 'createdat', type: Types::DATETIMETZ_IMMUTABLE, columnDefinition: 'TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL')]
    private ?DateTimeImmutable $createdAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markUsed(): void
    {
        $this->used = true;
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
