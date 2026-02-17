<?php

namespace Omnics\FileManagement\Services\FileManager;

use Omnics\FileManagement\Models\FileEntity;
use Omnics\FileManagement\Models\Files;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileDeleteService
{
    /**
     * Delete file from:
     * - S3
     * - Related entity table
     * - Database
     */
    public function delete($file): void
    {
        $disk = $file->visibility === 'public'
            ? 'hetznerPublic'
            : 'hetzner';

        $path = $file->object_key;
        /**
         * Delete from S3 (if exists)
         * We do NOT throw if file missing
         */
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        /**
         * Remove entity relations
         */
        FileEntity::where('files_id', $file->id)->delete();
    }
}
