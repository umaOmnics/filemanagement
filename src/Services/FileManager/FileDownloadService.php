<?php

namespace Omnics\FileManagement\Services\FileManager;

use Omnics\FileManagement\Models\Files;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileDownloadService
{
    /**
     * Download a file using DB as source of truth.
     * @param Files $file
     * @return Response
     */
    public function download(Files $file):Response
    {
        // Decide storage based on visibility
        $disk = $file->visibility === 'public'
            ? 'hetznerPublic'
            : 'hetzner';

        // Path stored in DB (object_key)
        $path = $file->object_key;

        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found in storage');
        }

        // Read file content
        $content = Storage::disk($disk)->get($path);
        $mime    = Storage::disk($disk)->mimeType($path);

        // Use original filename from DB
        return response($content, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
        ]);
    } // End Function
}
