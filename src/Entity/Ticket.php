<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
#[ORM\UniqueConstraint(name: 'tickets_show_seat_unique', columns: ['show_id', 'seat_id'])]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', columnDefinition: 'UUID NOT NULL')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Show::class)]
    #[ORM\JoinColumn(name: 'show_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Show $show = null;

    #[ORM\ManyToOne(targetEntity: Seat::class)]
    #[ORM\JoinColumn(name: 'seat_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Seat $seat = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $status = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getShow(): ?Show
    {
        return $this->show;
    }

    public function setShow(Show $show): static
    {
        $this->show = $show;

        return $this;
    }

    public function getSeat(): ?Seat
    {
        return $this->seat;
    }

    public function setSeat(Seat $seat): static
    {
        $this->seat = $seat;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
