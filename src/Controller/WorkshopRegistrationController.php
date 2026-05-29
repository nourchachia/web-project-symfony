<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkshopRegistration;
use App\Repository\WorkshopRegistrationRepository;
use App\Repository\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class WorkshopRegistrationController extends AbstractController
{
    public function __construct(
        private readonly WorkshopRegistrationRepository $workshopRegistrationRepository,
        private readonly WorkshopRepository $workshopRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/workshop-registrations', name: 'app_workshop_registrations_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('workshop_registration/index.html.twig');
    }

    #[Route('/api/workshop-registrations', name: 'api_workshop_registrations_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        return $this->json($this->workshopRegistrationRepository->findAllWithDetails());
    }

    #[Route('/api/workshop-registrations', name: 'api_workshop_registrations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getId() === null) {
            return $this->json(['error' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $idWorkshop = isset($data['idWorkshop']) ? trim((string) $data['idWorkshop']) : '';
        if (!$this->isValidUuid($idWorkshop)) {
            return $this->json(['error' => 'Invalid idWorkshop.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['rating']) || !is_numeric($data['rating'])) {
            return $this->json(['error' => 'Invalid rating.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            return $this->json(['error' => 'Rating must be between 1 and 5.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->workshopRepository->find($idWorkshop) === null) {
            return $this->json(['error' => 'Workshop not found.'], Response::HTTP_NOT_FOUND);
        }

        $registration = (new WorkshopRegistration())
            ->setIdUser($user->getId())
            ->setIdWorkshop($idWorkshop)
            ->setRating($rating);

        try {
            $this->entityManager->persist($registration);
            $this->entityManager->flush();
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to register for workshop.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(self::serializeRegistration($registration), Response::HTTP_CREATED);
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

    private function isValidUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    /**
     * @return array{id: string|null, idUser: string|null, idWorkshop: string|null, rating: int|null, createdAt: string|null}
     */
    private static function serializeRegistration(WorkshopRegistration $registration): array
    {
        return [
            'id' => $registration->getId(),
            'idUser' => $registration->getIdUser(),
            'idWorkshop' => $registration->getIdWorkshop(),
            'rating' => $registration->getRating(),
            'createdAt' => $registration->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
