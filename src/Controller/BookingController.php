<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Show;
use App\Exception\SeatAlreadyBookedException;
use App\Exception\SeatNotFoundException;
use App\Service\BookingService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    private const VALID_SECTIONS = ['left', 'center', 'right'];
    private const VALID_ROWS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
    private const VALID_STATUSES = ['reserved', 'paid'];

    public function __construct(
        private readonly BookingService $bookingService,
    ) {
    }

    #[Route('/booking', name: 'app_booking_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('booking/index.html.twig');
    }

    #[Route('/api/shows', name: 'api_shows_list', methods: ['GET'])]
    public function listShows(): JsonResponse
    {
        try {
            $shows = array_map(
                static fn (Show $show): array => self::serializeShow($show),
                $this->bookingService->listShows(),
            );

            return $this->json($shows);
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to list shows.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/shows/name/{name}', name: 'api_shows_by_name', methods: ['GET'], requirements: ['name' => '.+'])]
    public function getShowByName(string $name): JsonResponse
    {
        $show = $this->bookingService->getShowByName(urldecode($name));
        if ($show === null) {
            return $this->json(['error' => 'Show not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(self::serializeShow($show));
    }

    #[Route('/api/shows/ensure', name: 'api_shows_ensure', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function ensureShow(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        if ($name === '') {
            return $this->json(['error' => 'Field "name" is required.'], Response::HTTP_BAD_REQUEST);
        }

        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        if ($description === '') {
            $description = null;
        }

        $startsAt = null;
        if (array_key_exists('startsAt', $data) && $data['startsAt'] !== null && $data['startsAt'] !== '') {
            try {
                $startsAt = new DateTimeImmutable((string) $data['startsAt']);
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid startsAt datetime.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            $show = $this->bookingService->ensureShow($name, $description, $startsAt);

            return $this->json(['id' => $show->getId()], Response::HTTP_OK);
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to ensure show.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/shows/{showId}/seats', name: 'api_show_seats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function listSeats(string $showId): JsonResponse
    {
        if (!$this->isValidUuid($showId)) {
            return $this->json(['error' => 'Invalid show_id.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->bookingService->getShowById($showId) === null) {
            return $this->json(['error' => 'Show not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            return $this->json($this->bookingService->listSeatsWithOccupancy($showId));
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to load seats.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/tickets', name: 'api_tickets_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createTicket(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $validated = $this->validateSeatPayload($data, requireStatus: true);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        if ($this->bookingService->getShowById($validated['show_id']) === null) {
            return $this->json(['error' => 'Show not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $ticket = $this->bookingService->createTicket(
                $validated['show_id'],
                $validated['section'],
                $validated['row'],
                $validated['number'],
                $validated['status'],
            );

            return $this->json(['id' => $ticket->getId()], Response::HTTP_CREATED);
        } catch (SeatAlreadyBookedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (SeatNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to create ticket.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/tickets/release', name: 'api_tickets_release', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function releaseTicket(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $validated = $this->validateSeatPayload($data, requireStatus: false);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        if ($this->bookingService->getShowById($validated['show_id']) === null) {
            return $this->json(['error' => 'Show not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->bookingService->releaseTicket(
                $validated['show_id'],
                $validated['section'],
                $validated['row'],
                $validated['number'],
            );

            return $this->json(['success' => true]);
        } catch (SeatNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to release ticket.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function decodeJson(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{show_id: string, section: string, row: string, number: int, status?: string}|JsonResponse
     */
    private function validateSeatPayload(array $data, bool $requireStatus): array|JsonResponse
    {
        $showId = isset($data['show_id']) ? trim((string) $data['show_id']) : '';
        if (!$this->isValidUuid($showId)) {
            return $this->json(['error' => 'Invalid show_id.'], Response::HTTP_BAD_REQUEST);
        }

        $section = isset($data['section']) ? strtolower(trim((string) $data['section'])) : '';
        if (!\in_array($section, self::VALID_SECTIONS, true)) {
            return $this->json(['error' => 'Invalid section.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $row = isset($data['row']) ? strtoupper(trim((string) $data['row'])) : '';
        if (!\in_array($row, self::VALID_ROWS, true)) {
            return $this->json(['error' => 'Invalid row.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!isset($data['number']) || !is_numeric($data['number'])) {
            return $this->json(['error' => 'Invalid seat number.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $number = (int) $data['number'];
        if ($number <= 0) {
            return $this->json(['error' => 'Invalid seat number.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = [
            'show_id' => $showId,
            'section' => $section,
            'row' => $row,
            'number' => $number,
        ];

        if (!$requireStatus) {
            return $result;
        }

        $status = isset($data['status']) ? strtolower(trim((string) $data['status'])) : '';
        if (!\in_array($status, self::VALID_STATUSES, true)) {
            return $this->json(['error' => 'Invalid status.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result['status'] = $status;

        return $result;
    }

    private function isValidUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    /**
     * @return array{id: string|null, name: string|null, description: string|null, startsAt: string|null}
     */
    private static function serializeShow(Show $show): array
    {
        return [
            'id' => $show->getId(),
            'name' => $show->getName(),
            'description' => $show->getDescription(),
            'startsAt' => $show->getStartsAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
