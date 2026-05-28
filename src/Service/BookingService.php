<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Show;
use App\Entity\Ticket;
use App\Exception\SeatAlreadyBookedException;
use App\Exception\SeatNotFoundException;
use App\Repository\SeatRepository;
use App\Repository\ShowRepository;
use App\Repository\TicketRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

final class BookingService
{
    public function __construct(
        private readonly ShowRepository $showRepository,
        private readonly SeatRepository $seatRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<Show>
     */
    public function listShows(): array
    {
        return $this->showRepository->findAllOrdered();
    }

    public function getShowByName(string $name): ?Show
    {
        return $this->showRepository->findOneByName($name);
    }

    public function getShowById(string $showId): ?Show
    {
        return $this->showRepository->find($showId);
    }

    public function ensureShow(string $name, ?string $description, ?DateTimeInterface $startsAt): Show
    {
        $existing = $this->showRepository->findOneByName($name);
        if ($existing !== null) {
            return $existing;
        }

        $show = (new Show())
            ->setId(Ticket::generateUuidV4())
            ->setName(trim($name))
            ->setDescription($description !== null ? trim($description) : null)
            ->setStartsAt($startsAt instanceof DateTimeImmutable ? $startsAt : ($startsAt !== null ? DateTimeImmutable::createFromInterface($startsAt) : null));

        $this->entityManager->persist($show);
        $this->entityManager->flush();

        return $show;
    }

    /**
     * @return list<array{id: int, section: string, row: string, number: int, is_occupied: bool}>
     */
    public function listSeatsWithOccupancy(string $showId): array
    {
        $seats = $this->seatRepository->findAllOrdered();
        $tickets = $this->ticketRepository->findByShowId($showId);

        $occupiedSeatIds = [];
        foreach ($tickets as $ticket) {
            $seat = $ticket->getSeat();
            if ($seat?->getId() !== null) {
                $occupiedSeatIds[$seat->getId()] = true;
            }
        }

        $result = [];
        foreach ($seats as $seat) {
            $seatId = $seat->getId();
            if ($seatId === null) {
                continue;
            }

            $result[] = [
                'id' => $seatId,
                'section' => (string) $seat->getSection(),
                'row' => (string) $seat->getRow(),
                'number' => (int) $seat->getNumber(),
                'is_occupied' => isset($occupiedSeatIds[$seatId]),
            ];
        }

        return $result;
    }

    public function createTicket(string $showId, string $section, string $row, int $number, string $status): Ticket
    {
        $show = $this->showRepository->find($showId);
        if ($show === null) {
            throw new SeatNotFoundException('Show not found.');
        }

        $seat = $this->seatRepository->findOneByCoordinates($section, $row, $number);
        if ($seat === null || $seat->getId() === null) {
            throw new SeatNotFoundException('Seat not found.');
        }

        if ($this->ticketRepository->existsByShowAndSeat($showId, $seat->getId())) {
            throw new SeatAlreadyBookedException('Seat is already booked for this show.');
        }

        $ticket = (new Ticket())
            ->setId(Ticket::generateUuidV4())
            ->setShow($show)
            ->setSeat($seat)
            ->setStatus($status);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }

    public function releaseTicket(string $showId, string $section, string $row, int $number): void
    {
        $seat = $this->seatRepository->findOneByCoordinates($section, $row, $number);
        if ($seat === null || $seat->getId() === null) {
            throw new SeatNotFoundException('Seat not found.');
        }

        $deleted = $this->ticketRepository->deleteByShowAndSeat($showId, $seat->getId());
        if ($deleted === 0) {
            throw new SeatNotFoundException('No ticket found for this seat on this show.');
        }
    }
}
