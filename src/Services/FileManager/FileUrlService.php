<?php

namespace Omnics\FileManagement\Services\FileManager;

use Omnics\FileManagement\Models\Files;
use Aws\S3\S3Client;

class FileUrlService
{
    public function get(Files $file): array
    {
        if ($file->visibility === 'public') {
            return $this->publicUrl($file);
        }

        return $this->getSignedUrl($file);
    }

    /**
     * Public file direct URL.
     */
    private function publicUrl(Files $file): array
    {
        $path = sprintf(
            '%s/%s/%s',
            config('filesystems.disks.hetznerPublic.endpoint'),
            config('filesystems.disks.hetznerPublic.bucket'),
            $file->object_key,
        );
        $file->update([
            'path' => $path,
        ]);
        return [
            'path' => $path,
            'expires_at' => null,
        ];
    }

    /**
     * Get cached signed URL or generate new one.
     */
    private function getSignedUrl(Files $file): array
    {
        if (
            $file->signed_url &&
            $file->signed_url_expires_at &&
            now()->lt($file->signed_url_expires_at)
        ) {
            return [
                'path'       => $file->signed_url,
                'expires_at' => $file->signed_url_expires_at,
            ];
        }

        return $this->generateAndStore($file);
    }

    /**
     * Generate new signed URL and persist.
     */
    public function generateAndStore(Files $file): array
    {
        $expiry = now()->addDay();

        $client = new S3Client([
            'version' => 'latest',
            'region'  => env('HETZNER_REGION'),
            'endpoint'=> env('HETZNER_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => env('HETZNER_ACCESS_KEY'),
                'secret' => env('HETZNER_SECRET_KEY'),
            ],
        ]);

        $command = $client->getCommand('GetObject', [
            'Bucket' => env('HETZNER_PRIVATE_BUCKET'),
            'Key'    => $file->object_key,
        ]);

        $request = $client->createPresignedRequest($command, $expiry);

        $url = (string) $request->getUri();

        $file->update([
            'path' => $url,
        ]);

        return [
            'path'       => $url,
            'expires_at' => $expiry->toDateTimeString(),
        ];
    }
}
