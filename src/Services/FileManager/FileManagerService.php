<?php

namespace Omnics\FileManagement\Services\FileManager;

use Omnics\FileManagement\Models\FileEntity;
use Omnics\FileManagement\Models\Files;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FileManagerService
{
    /**
     * Store one or many files.
     * Same logic, loop-based.
     * @param array $meta
     * @param array $uploads
     * @return array
     */
    public function storeMany(array $uploads, array $meta): array
    {
        $results = [];

        foreach ($uploads as $upload) {
            $results[] = $this->storeOne($upload, $meta);
        }

        return [
            'count' => count($results),
            'files' => $results,
        ];
    } // End Function

    /**
     * Main orchestration method.
     * Controls database writes, storage, and attachments.
     * @param UploadedFile $upload
     * @param array $meta
     * @return array
     * @throws \Throwable
     */
    public function storeOne(UploadedFile $upload, array $meta): array
    {
        DB::beginTransaction();

        try {
            /**
             * Create logical file record (DB)
             */
            $file = Files::create([
                'folders_id'     => $meta['folder_id'],
                'title'         => pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $upload->getClientOriginalName(),
                'size'          => $upload->getSize(),
                'mime'          => $upload->getMimeType(),
                'visibility'    => $meta['visibility'],
                'is_entity'     => $meta['is_entity'],
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            /**
             * Store physical bytes
             */
            // Generate immutable object key
            $objectKey = app(FileStorageService::class)->generateObjectKey($file, $upload);

            // Upload file to S3
            app(FileStorageService::class)->upload($file, $upload, $objectKey);

            /**
             * Save object key
             */
            $file->update([
                'object_key' => $objectKey,
            ]);

            /**
             * Attach to entity (optional)
             */
            if (!empty($meta['entity_type'])) {
                FileEntity::create([
                    'files_id'     => $file->id,
                    'entity_type' => $meta['entity_type'],
                    'entity_id'   => $meta['entity_id'],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();

            /**
             * Return file + access URL
             */
            $file_details = app(FileUrlService::class)->get($file);
            $path = $file_details['path'];
            return [
                'id'    => $file->id,
                'title'  => $file->title,
                'size'  => $file->size,
                'mime'  => $file->mime,
                'visibility'  => $file->visibility,
                'object_key' => $file->object_key,
                'path'   => $path,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    } // End Function
}
