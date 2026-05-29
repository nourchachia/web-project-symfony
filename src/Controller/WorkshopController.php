<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Workshop;
use App\Repository\WorkshopRepository;
use App\Service\UploadService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class WorkshopController extends AbstractController
{
    private const MAX_VIDEO_SIZE = 104_857_600;
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/quicktime',
        'video/webm',
        'video/x-m4v',
    ];

    public function __construct(
        private readonly WorkshopRepository $workshopRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadService $uploadService,
    ) {
    }

    #[Route('/workshops', name: 'app_workshop_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('workshop/index.html.twig');
    }

    #[Route('/change-workshop', name: 'app_workshop_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function manage(): Response
    {
        return $this->render('change_workshop/index.html.twig');
    }

    #[Route('/api/workshops', name: 'api_workshops_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $workshops = array_map(
            static fn (Workshop $workshop): array => self::serializeWorkshop($workshop),
            $this->workshopRepository->findAllOrderedByDate(),
        );

        return $this->json($workshops);
    }

    #[Route('/api/workshops/{id}', name: 'api_workshops_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        if (!$this->isValidUuid($id)) {
            return $this->json(['error' => 'Invalid workshop id.'], Response::HTTP_BAD_REQUEST);
        }

        $workshop = $this->workshopRepository->find($id);
        if ($workshop === null) {
            return $this->json(['error' => 'Workshop not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(self::serializeWorkshop($workshop));
    }

    #[Route('/api/workshops', name: 'api_workshops_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $payload = $this->validateWorkshopPayload($data, requireAll: true);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $workshop = (new Workshop())
            ->setTitle($payload['title'])
            ->setDescription($payload['description'])
            ->setDepartement($payload['departement'])
            ->setDate($payload['date'])
            ->setVideoUrl($payload['videoUrl']);

        try {
            $this->entityManager->persist($workshop);
            $this->entityManager->flush();
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to create workshop.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(self::serializeWorkshop($workshop), Response::HTTP_CREATED);
    }

    #[Route('/api/workshops/{id}', name: 'api_workshops_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, Request $request): JsonResponse
    {
        if (!$this->isValidUuid($id)) {
            return $this->json(['error' => 'Invalid workshop id.'], Response::HTTP_BAD_REQUEST);
        }

        $workshop = $this->workshopRepository->find($id);
        if ($workshop === null) {
            return $this->json(['error' => 'Workshop not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $payload = $this->validateWorkshopPayload($data, requireAll: false);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if ($payload === []) {
            return $this->json(['error' => 'At least one workshop field is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('title', $payload)) {
            $workshop->setTitle($payload['title']);
        }

        if (array_key_exists('description', $payload)) {
            $workshop->setDescription($payload['description']);
        }

        if (array_key_exists('departement', $payload)) {
            $workshop->setDepartement($payload['departement']);
        }

        if (array_key_exists('date', $payload)) {
            $workshop->setDate($payload['date']);
        }

        if (array_key_exists('videoUrl', $payload)) {
            $workshop->setVideoUrl($payload['videoUrl']);
        }

        try {
            $this->entityManager->flush();
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to update workshop.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(self::serializeWorkshop($workshop));
    }

    #[Route('/api/workshops/{id}', name: 'api_workshops_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): JsonResponse
    {
        if (!$this->isValidUuid($id)) {
            return $this->json(['error' => 'Invalid workshop id.'], Response::HTTP_BAD_REQUEST);
        }

        $workshop = $this->workshopRepository->find($id);
        if ($workshop === null) {
            return $this->json(['error' => 'Workshop not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($workshop);
            $this->entityManager->flush();
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to delete workshop.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/api/upload/video', name: 'api_upload_video', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function uploadVideo(Request $request): JsonResponse
    {
        $contentLength = (int) $request->server->get('CONTENT_LENGTH', 0);

        // upload_max_filesize is the per-file PHP limit — it is enforced before
        // Symfony runs and silently nullifies the uploaded file when exceeded.
        $uploadMaxFilesize = self::parsePhpSize((string) ini_get('upload_max_filesize'));
        if ($contentLength > 0 && $uploadMaxFilesize > 0 && $contentLength > $uploadMaxFilesize) {
            return $this->json([
                'error' => sprintf(
                    'Uploaded video exceeds PHP upload_max_filesize (%s). Raise upload_max_filesize and post_max_size in php.ini.',
                    (string) ini_get('upload_max_filesize'),
                ),
            ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        // post_max_size caps the entire request body (file + other fields).
        $postMaxSize = self::parsePhpSize((string) ini_get('post_max_size'));
        if ($contentLength > 0 && $postMaxSize > 0 && $contentLength > $postMaxSize) {
            return $this->json([
                'error' => sprintf(
                    'Request body exceeds PHP post_max_size (%s). Raise post_max_size in php.ini.',
                    (string) ini_get('post_max_size'),
                ),
            ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $file = $request->files->get('video');
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'Field "video" is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$file->isValid()) {
            return $this->json(['error' => 'Uploaded video is invalid.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $size = $file->getSize();
        if ($size === null || $size <= 0 || $size > self::MAX_VIDEO_SIZE) {
            return $this->json(['error' => 'Video size must be between 1 byte and 100 MB.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        if (!\in_array($mimeType, self::ALLOWED_VIDEO_TYPES, true)) {
            return $this->json(['error' => 'Unsupported video type.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return $this->json(['url' => $this->uploadService->saveWorkshopVideo($file)], Response::HTTP_CREATED);
        } catch (\Throwable) {
            return $this->json(['error' => 'Unable to upload video.'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * @return array<string, mixed>|JsonResponse
     */
    private function validateWorkshopPayload(array $data, bool $requireAll): array|JsonResponse
    {
        $payload = [];

        $title = $this->readText($data, 'title', required: $requireAll);
        if ($title instanceof JsonResponse) {
            return $title;
        }
        if ($title !== null) {
            $payload['title'] = $title;
        }

        $description = $this->readNullableText($data, 'description', required: $requireAll);
        if ($description instanceof JsonResponse) {
            return $description;
        }
        if (array_key_exists('description', $data) || $requireAll) {
            $payload['description'] = $description;
        }

        $departement = $this->readText($data, 'departement', required: $requireAll);
        if ($departement instanceof JsonResponse) {
            return $departement;
        }
        if ($departement !== null) {
            $payload['departement'] = $departement;
        }

        if (array_key_exists('date', $data)) {
            if ($data['date'] === null || trim((string) $data['date']) === '') {
                return $this->json(['error' => 'Field "date" is required.'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $payload['date'] = new DateTimeImmutable((string) $data['date']);
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid date datetime.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } elseif ($requireAll) {
            return $this->json(['error' => 'Field "date" is required.'], Response::HTTP_BAD_REQUEST);
        }

        $videoUrlKey = array_key_exists('video_url', $data) ? 'video_url' : (array_key_exists('videoUrl', $data) ? 'videoUrl' : null);
        if ($videoUrlKey !== null) {
            $value = $data[$videoUrlKey];
            $payload['videoUrl'] = $value !== null && trim((string) $value) !== '' ? trim((string) $value) : null;
        } elseif ($requireAll) {
            $payload['videoUrl'] = null;
        }

        return $payload;
    }

    private function readText(array $data, string $field, bool $required): string|JsonResponse|null
    {
        if (!array_key_exists($field, $data)) {
            if ($required) {
                return $this->json(['error' => sprintf('Field "%s" is required.', $field)], Response::HTTP_BAD_REQUEST);
            }

            return null;
        }

        $value = trim((string) $data[$field]);
        if ($value === '') {
            return $this->json(['error' => sprintf('Field "%s" is required.', $field)], Response::HTTP_BAD_REQUEST);
        }

        return $value;
    }

    private function readNullableText(array $data, string $field, bool $required): string|JsonResponse|null
    {
        if (!array_key_exists($field, $data)) {
            if ($required) {
                return $this->json(['error' => sprintf('Field "%s" is required.', $field)], Response::HTTP_BAD_REQUEST);
            }

            return null;
        }

        $value = $data[$field] !== null ? trim((string) $data[$field]) : null;
        if ($required && ($value === null || $value === '')) {
            return $this->json(['error' => sprintf('Field "%s" is required.', $field)], Response::HTTP_BAD_REQUEST);
        }

        return $value !== '' ? $value : null;
    }

    private function isValidUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    private static function parsePhpSize(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower($value[-1]);
        $size = (float) $value;

        return (int) match ($unit) {
            'g' => $size * 1024 * 1024 * 1024,
            'm' => $size * 1024 * 1024,
            'k' => $size * 1024,
            default => $size,
        };
    }

    /**
     * @return array{id: string|null, title: string|null, description: string|null, departement: string|null, date: string|null, videoUrl: string|null, createdAt: string|null, updatedAt: string|null}
     */
    private static function serializeWorkshop(Workshop $workshop): array
    {
        return [
            'id' => $workshop->getId(),
            'title' => $workshop->getTitle(),
            'description' => $workshop->getDescription(),
            'departement' => $workshop->getDepartement(),
            'date' => $workshop->getDate()?->format(\DateTimeInterface::ATOM),
            'videoUrl' => $workshop->getVideoUrl(),
            'createdAt' => $workshop->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $workshop->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
