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
        $localPath = $file->getRealPath();

        if ($localPath === false) {
            throw new RuntimeException('The uploaded picture could not be read.');
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
            ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The uploaded picture could not be sent to Supabase Storage.', previous: $exception);
        }

        if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED], true)) {
            throw new RuntimeException('Supabase Storage rejected the uploaded picture.');
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
