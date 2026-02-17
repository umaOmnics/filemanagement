<?php

namespace Omnics\FileManagement\Services\MasterData;

use Omnics\FileManagement\Models\Files;
use Omnics\FileManagement\Models\Folders;
use Omnics\FileManagement\Models\Tags;
use Omnics\FileManagement\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTagsService
{
    /**
     * Update tags for a module entity (task/file/folder).
     *
     * @param string $module  Allowed: tasks|files|folders
     * @param int    $id
     * @param array  $payload ['tags_id' => [], 'tags_new' => []]
     * @return array
     * @throws ValidationException
     */
    public function update(string $module, int $id, array $payload): array
    {
        $entity = $this->resolveEntity($module, $id);

        // Normalize arrays
        $tagIds = array_values(array_filter($payload['tags_id'] ?? [], fn ($v) => $v !== null && $v !== ''));
        $newTags = $payload['tags_new'] ?? [];

        return DB::transaction(function () use ($entity, $tagIds, $newTags) {
            // 1) Create tags if needed, collect IDs
            $createdTagIds = $this->createOrGetTagIds($newTags);

            // 2) Merge + unique
            $finalTagIds = array_values(array_unique(array_merge($tagIds, $createdTagIds)));

            // 3) Sync pivot (replaces detach/attach loops)
            // Add created_at if your pivot requires it:
            $now = Carbon::now()->format('Y-m-d H:i:s');

            $syncData = [];
            foreach ($finalTagIds as $tid) {
                $syncData[$tid] = ['created_at' => $now];
            }

            // Ensure relation exists on the model: tags()
            $entity->tags()->sync($syncData);

            // 4) Return fresh tags
            $entity->load('tags');

            return [
                'status'  => 'Success',
                'message' => 'Tags are updated successfully',
                'data'    => [
                    'module' => class_basename($entity),
                    'id'     => $entity->getKey(),
                    'tags'   => $entity->tags,
                ],
            ];
        });
    }

    /**
     * Resolve module entity by module name and id.
     */
    private function resolveEntity(string $module, int $id): Model
    {
        return match ($module) {
            'tasks'   => Task::query()->findOrFail($id),
            'files'   => Files::query()->findOrFail($id),
            'folders' => Folders::query()->findOrFail($id),
            default   => throw ValidationException::withMessages([
                'module' => ['Invalid module. Allowed values: tasks, files, folders'],
            ]),
        };
    }

    /**
     * Create new tags (if not existing) and return their IDs.
     *
     * tags_new item: ['name' => string, 'color' => string|null]
     */
    private function createOrGetTagIds(array $newTags): array
    {
        $ids = [];
        foreach ($newTags as $tag) {
            $name = trim((string)($tag['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $color = $tag['color'] ?? null;
            // Ensure uniqueness by name (case-insensitive optional)
            $existing = Tags::query()->where('name', $name)->first();
            if ($existing) {
                $ids[] = (int) $existing->id;
                continue;
            }
            $created = Tags::query()->create([
                'name'  => $name,
                'color' => $color,
            ]);
            $ids[] = (int) $created->id;
        }
        return $ids;
    }
}
