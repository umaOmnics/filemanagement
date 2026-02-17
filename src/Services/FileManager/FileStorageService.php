<?php

namespace Omnics\FileManagement\Services\FileManager;

use Omnics\FileManagement\Models\Files;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    /**
     * Stores file bytes in object storage.
     * Returns the object key for later access.
     * @param Files $file
     * @param UploadedFile $upload
     * @return array
     */
    /**
     * Generate immutable object key.
     * Never depends on folder names.
     */
    public function generateObjectKey(Files $file, UploadedFile $upload): string
    {
        // Extract extension safely
        $extension = strtolower($upload->getClientOriginalExtension());

        // Fallback if extension missing
        if (!$extension) {
            $extension = $this->mimeToExtension($upload->getMimeType());
        }

        return sprintf(
            'FileManager/files/%s.%s',
            Str::uuid(),
            $extension
        );
    }

    /**
     * Upload file bytes to storage.
     */
    public function upload(Files $file, UploadedFile $upload, string $objectKey): void
    {
        $disk = $file->visibility === 'public'
            ? 'hetznerPublic'
            : 'hetzner';

        Storage::disk($disk)->putFileAs(
            dirname($objectKey),
            $upload,
            basename($objectKey)
        );
    }

    /**
     * Fallback MIME → extension mapping.
     */
    private function mimeToExtension(?string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            default => 'bin',
        };
    }
}
