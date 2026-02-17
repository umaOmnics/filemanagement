<?php

namespace Omnics\FileManagement\Http\Controllers\FileManager;

use App\Http\Controllers\Controller;
use App\Models\Files;
use App\Models\Folders;
use App\Services\MasterData\AssignTagsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\ValidationException;
use Exception;

class FolderController extends Controller
{
    /**
     * macOS-like browse endpoint:
     * Response includes:
     * - current_folder (or null at root)
     * - breadcrumbs (sliding window, default max_levels=3)
     * - folders (direct children only)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'max_levels' => 'sometimes|integer|min:1|max:10',
        ]);
        $folderId  = $request->folder_id;
        $maxLevels = (int) ($validated['max_levels'] ?? 3);
        // ------------------------------------------------
        // ROOT VIEW (folder_id = null)
        // ------------------------------------------------
        if ($folderId === null) {
            $folders = Folders::query()
                ->select(['id', 'parent_id', 'name', 'created_at', 'updated_at'])
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
            $files = Files::query()
                ->select([
                    'id',
                    'folders_id',
                    'title',
                    'original_name',
                    'size',
                    'mime',
                    'path',
                    'created_at'
                ])
                ->whereNull('folders_id')       // ROOT FILES
                ->where('is_entity', 0)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'current_folder' => null,
                'breadcrumbs'    => [],
                'folders'        => $folders,
                'files'          => $files,
            ]);
        }
        // ------------------------------------------------
        // LOAD CURRENT FOLDER
        // ------------------------------------------------
        $current = Folders::query()
            ->select(['id', 'parent_id', 'name', 'created_at', 'updated_at'])
            ->findOrFail($folderId);
        // ------------------------------------------------
        // LOAD DIRECT CHILD FOLDERS
        // ------------------------------------------------
        $children = Folders::query()
            ->select(['id', 'parent_id', 'name', 'created_at', 'updated_at'])
            ->where('parent_id', $current->id)
            ->orderBy('name')
            ->get();
        // ------------------------------------------------
        // LOAD FILES IN CURRENT FOLDER
        // ------------------------------------------------
        $files = Files::query()
            ->select([
                'id',
                'folders_id',
                'title',
                'original_name',
                'size',
                'mime',
                'path',
                'created_at'
            ])
            ->where('folders_id', $current->id)
            ->where('is_entity', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        // ------------------------------------------------
        // BUILD BREADCRUMBS (NO FILES INSIDE)
        // ------------------------------------------------
        $breadcrumbs = $this->buildBreadcrumbsSliding($current, $maxLevels);

        return response()->json([
            'current_folder' => $current,
            'breadcrumbs'    => $breadcrumbs,
            'folders'        => $children,
            'files'          => $files,
        ]);
    } // End Function

    /**
     * Build breadcrumb chain from root -> current (inclusive),
     * then apply a sliding window of the last $maxLevels items.
     *
     * Why this is safe & performant:
     * - Fetches only ancestors of the current folder (depth is usually small).
     * - Includes cycle protection to avoid infinite loops if data is corrupted.
     * - Returns only id/name/parent_id + timestamps.
     */
    private function buildBreadcrumbsSliding(Folders $current, int $maxLevels = 3): array
    {
        $maxLevels = max(1, min($maxLevels, 10));
        $chain   = [];
        $node    = $current;
        $visited = [];

        // ------------------------------------------------
        // Build full chain root → current
        // ------------------------------------------------
        while ($node !== null) {
            if (isset($visited[$node->id])) {
                break; // safety against corrupted hierarchy
            }
            $visited[$node->id] = true;
            $chain[] = $node;
            if ($node->parent_id === null) {
                break;
            }
            $node = Folders::query()
                ->select(['id', 'parent_id', 'name', 'created_at', 'updated_at'])
                ->find($node->parent_id);
        }
        $chain = array_reverse($chain);
        $totalLevels = count($chain);
        $result = [];
        foreach ($chain as $index => $folder) {
            $isCurrent = $folder->id === $current->id;
            // Determine hidden logic
            $hidden = ($totalLevels - $index) > $maxLevels;
            $breadcrumbItem = [
                'id'         => (int) $folder->id,
                'parent_id'  => $folder->parent_id ? (int) $folder->parent_id : null,
                'name'       => (string) $folder->name,
                'hidden'     => $hidden,
                'files'      => [],
                'created_at' => $folder->created_at,
                'updated_at' => $folder->updated_at,
            ];
            // ------------------------------------------------
            // Include files only if:
            // - NOT hidden
            // - NOT current folder
            // ------------------------------------------------
            if (!$hidden && !$isCurrent) {
                $breadcrumbItem['files'] = Files::query()
                    ->select([
                        'id',
                        'folders_id',
                        'title',
                        'original_name',
                        'size',
                        'mime',
                        'path',
                        'created_at'
                    ])
                    ->where('folders_id', $folder->id)
                    ->where('is_entity', 0)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            $result[] = $breadcrumbItem;
        }
        return $result;
    } // End Function

    /**
     * Method Allow to create the Folder and also the Sub Folders
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required'
            ]);
            $user = Auth::user();
            // Store creator details as JSON
            $created_by = [
                'id' => $user->id,
                'type' => request()->attributes->get('auth.driver') ?? 'passport',
            ];
            // Insert the Data for creation of the Folder
            Folders::insert([
                'name' => $request->name,
                'is_private' => $request->is_private ?? 0,
                'description' => $request->description ?? null,
                'parent_id' => $request->parent_id ?? null,
                'created_by' => json_encode($created_by),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Folder is created successfully',
            ],200);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method Allow to Update the basic details of the Folders
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required',
            ]);
            if (Folders::where('id', $id)->exists()) {
                $folder = Folders::where('id', $id)->first();
                $folder->name = $request->name;
                $folder->description = $request->description ?? null;
                $folder->is_private = $request->is_private ?? 0;
                $folder->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                $folder->save();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'Folder is updated successfully',
                ],200);
            } else {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception,
            ], 500);
        }
    } // End Function

    /**
     * Method allow to update the Tags for the Folders
     * @param Request $request
     * @param $id
     * @param AssignTagsService $assignTagsService
     * @return JsonResponse
     */
    public function updateTags(Request $request, $id, AssignTagsService $assignTagsService):JsonResponse
    {
        try {
            if (Folders::where('id', $id)->exists()) {
                $validated = $request->validate([
                    'tags_id' => 'sometimes|array',
                    'tags_id.*' => 'integer|exists:tags,id',
                    'tags_new' => 'sometimes|array',
                    'tags_new.*.name' => 'required|string|max:255',
                    'tags_new.*.color' => 'nullable|string|max:32',
                ]);
                $module = 'folders';
                // Implement the Service for Updating the Tags for Folders
                $result = $assignTagsService->update($module, $id, $validated);

                return response()->json([
                    'tags' => $result,
                    'status' => 'Success',
                    'message' => 'Tags are updated successfully'
                ], 200);
            } else{
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ],210);
            }
        } catch (Exception $exception){
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ],500);
        }
    } // End Function

    /**
     * Method allow to change status of particular Folder.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy($id): JsonResponse
    {
        try {
            if (!Folders::where('id', $id)->exists()) {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }

            $folderIds = $this->getAllFolderIds($id);
            Folders::whereIn('id', $folderIds)->delete();
            return response()->json([
                'status' => 'Success',
                'message' => 'Folder and subfolders deleted successfully',
            ], 200);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function


    /**
     * Method allow to Retrieve list of deleted Folders.
     * @return JsonResponse
     * @throws Exception
     */
    public function retrieve():JsonResponse
    {
        try {
            $folders = Folders::onlyTrashed()->get();

            return response()->json([
                'data' => $folders,
                'message' => 'Success',
            ], 200);
        } catch (Exception $exception)
        {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getLine(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to Restore the particular Folder.
     * @param $id
     * @return JsonResponse
     */
    public function restore($id): JsonResponse
    {
        try {
            if (!Folders::withTrashed()->where('id', $id)->exists()) {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
            $folderIds = $this->getAllFolderIds($id, true);
            Folders::withTrashed()
                ->whereIn('id', $folderIds)
                ->restore();

            return response()->json([
                'status' => 'Success',
                'message' => 'Folder and subfolders restored successfully'
            ], 200);

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function


    /**
     * Method allow to Restore group of Folders.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massRestore(Request $request): JsonResponse
    {
        try {
            if (empty($request->folders_id)) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one folder to restore'
                ], 422);
            }
            $allIds = [];
            foreach ($request->folders_id as $folderId) {
                $allIds = array_merge(
                    $allIds,
                    $this->getAllFolderIds($folderId, true)
                );
            }
            Folders::withTrashed()
                ->whereIn('id', array_unique($allIds))
                ->restore();

            return response()->json([
                'status' => 'Success',
                'message' => 'Folders and subfolders restored successfully'
            ], 200);

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function

    /**
     * Method allow to delete the particular Folder Permanently from the System.
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function forceDelete($id): JsonResponse
    {
        try {
            if (!Folders::withTrashed()->where('id', $id)->exists()) {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }
            $folderIds = $this->getAllFolderIds($id, true);
            // delete children first (important)
            Folders::withTrashed()
                ->whereIn('id', array_reverse($folderIds))
                ->forceDelete();

            return response()->json([
                'status' => 'Success',
                'message' => 'Folder and subfolders permanently deleted successfully',
            ], 200);

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function


    /**
     * Method allow to soft delete the set of Folders.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massDelete(Request $request): JsonResponse
    {
        try {
            if (empty($request->folders_id)) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one unit to delete'
                ], 422);
            }
            $allIds = [];
            foreach ($request->folders_id as $folderId) {
                $allIds = array_merge($allIds, $this->getAllFolderIds($folderId));
            }
            Folders::whereIn('id', array_unique($allIds))->delete();
            return response()->json([
                'status' => 'Success',
                'message' => 'Folders and subfolders deleted successfully',
            ], 200);

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function


    /**
     * Method allow to permanent delete the set of Folders from the System including the Subfolders.
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function massForceDelete(Request $request): JsonResponse
    {
        try {
            if (empty($request->folders_id)) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one unit to delete'
                ], 422);
            }
            $allIds = [];
            foreach ($request->folders_id as $folderId) {
                $allIds = array_merge(
                    $allIds,
                    $this->getAllFolderIds($folderId, true)
                );
            }
            Folders::withTrashed()
                ->whereIn('id', array_reverse(array_unique($allIds)))
                ->forceDelete();
            return response()->json([
                'status' => 'Success',
                'message' => 'Folders and subfolders permanently deleted successfully',
            ], 200);

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function


    /**
     * Get all subfolder IDs recursively (including parent)
     */
    private function getAllFolderIds(int $folderId, bool $withTrashed = false): array
    {
        $query = $withTrashed
            ? Folders::withTrashed()
            : Folders::query();
        $ids = [$folderId];
        $children = $query->where('parent_id', $folderId)->pluck('id');
        foreach ($children as $childId) {
            $ids = array_merge(
                $ids,
                $this->getAllFolderIds($childId, $withTrashed)
            );
        }
        return $ids;
    } // End Function

}
