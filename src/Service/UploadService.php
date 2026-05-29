<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class UploadService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SluggerInterface $slugger,
        private readonly string $supabaseUrl,
        private readonly string $supabaseServiceKey,
        private readonly string $supabaseStorageBucket,
    ) {
    }

    public function savePendingProfilePicture(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName)->lower()->toString();
        $extension = $file->guessExtension() ?: 'bin';
        $filename = $safeName . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $storagePath = 'pending/' . date('Y/m') . '/' . $filename;
        return $this->uploadToSupabase($file, $storagePath, 'picture');
    }

    public function saveWorkshopVideo(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName)->lower()->toString();
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = $safeName . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $storagePath = 'workshops/videos/' . date('Y/m') . '/' . $filename;

        return $this->uploadToSupabase($file, $storagePath, 'video');
    }

    private function uploadToSupabase(UploadedFile $file, string $storagePath, string $label): string
    {
        $localPath = $file->getRealPath();

        if ($localPath === false) {
            throw new RuntimeException(sprintf('The uploaded %s could not be read.', $label));
        }

        $supabaseUrl = rtrim($this->supabaseUrl, '/');
        $uploadUrl = $supabaseUrl
            . '/storage/v1/object/'
            . rawurlencode($this->supabaseStorageBucket)
            . '/'
            . $this->encodeStoragePath($storagePath);

        try {
            $response = $this->httpClient->request('POST', $uploadUrl, [
                'headers' => [
                    'apikey' => $this->supabaseServiceKey,
                    'Authorization' => 'Bearer ' . $this->supabaseServiceKey,
                    'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
                    'cache-control' => '3600',
                    'x-upsert' => 'false',
                ],
                'body' => fopen($localPath, 'rb'),
                'timeout' => 300,
            ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf('The uploaded %s could not be sent to Supabase Storage.', $label), previous: $exception);
        }

        if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED], true)) {
            throw new RuntimeException(sprintf('Supabase Storage rejected the uploaded %s.', $label));
        }

        return $supabaseUrl
            . '/storage/v1/object/public/'
            . rawurlencode($this->supabaseStorageBucket)
            . '/'
            . $this->encodeStoragePath($storagePath);
    }

    private function encodeStoragePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
