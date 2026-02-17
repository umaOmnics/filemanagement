<?php

namespace Omnics\FileManagement\Http\Controllers\FileManager;

use Illuminate\Routing\Controller;
use Omnics\FileManagement\Models\Files;
use Omnics\FileManagement\Models\Folders;
use Omnics\FileManagement\Services\FileManager\FileDeleteService;
use Omnics\FileManagement\Services\FileManager\FileDownloadService;
use Omnics\FileManagement\Services\MasterData\AssignTagsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;
use Omnics\FileManagement\Services\FileManager\FileManagerService;
use Illuminate\Http\JsonResponse;
use Exception;

class FileController extends Controller
{
    protected FileManagerService $fileManagerService;
    protected FileDownloadService $downloadService;


    public function __construct(FileManagerService $fileManagerService, FileDownloadService $downloadService)
    {
        $this->fileManagerService = $fileManagerService;
        $this->downloadService = $downloadService;
    }

    /**
     * Upload single or multiple files...
     *
     * This endpoint handles:
     * - Single file upload
     * - Multiple file upload
     * - Optional folder association
     * - Optional entity attachment
     *
     * Request:
     *  files[] (required)
     *  folder_id (nullable)
     *  entity_type (nullable)
     *  entity_id (required if entity_type exists)
     *  visibility (private|public)
     */
    public function store(Request $request): JsonResponse
    {
        // -----------------------------
        // Validate request
        // -----------------------------
        $validated = $request->validate([
            'files'        => 'required',
            'files.*'      => 'file|max:51200', // 50MB example limit
            'folder_id'    => 'nullable|exists:folders,id',
            'entity_type'  => 'nullable|string',
            'entity_id'    => 'required_with:entity_type|integer',
            'visibility'   => 'nullable|in:private,public',
        ]);

        // -----------------------------
        // Normalize uploads
        // Always treat as array
        // -----------------------------
        $uploads = $request->file('files');

        if (!is_array($uploads)) {
            $uploads = [$uploads];
        }

        // -----------------------------
        // Prepare meta information
        // -----------------------------
        $meta = [
            'folder_id'   => $validated['folder_id'] ?? null,
            'entity_type' => $validated['entity_type'] ?? null,
            'entity_id'   => $validated['entity_id'] ?? null,
            'is_entity'   => $validated['is_entity'] ?? 0,
            'visibility'  => $validated['visibility'] ?? 'private',
        ];

        // -----------------------------
        // Call FileManagerService
        // -----------------------------
        $result = $this->fileManagerService->storeMany($uploads, $meta);

        // -----------------------------
        // Return structured response
        // -----------------------------
        return response()->json([
            'status' => 'success',
            'count'  => $result['count'],
            'files'  => $result['files'],
        ], 201);
    } // End Function

    /**
     * Method Allow to Update the basic details of the Files
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
            if (Files::where('id', $id)->exists()) {
                $file = Files::where('id', $id)->first();
                $file->title = $request->name;
                $file->source_text = $request->source_text;
                $file->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                $file->save();

                return response()->json([
                    'status' => 'Success',
                    'message' => 'File Details are updated successfully',
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
     * Method allow to update the Tags for the Files
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
                $module = 'files';
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
     * Download file by ID.
     *
     * GET /api/files/{id}/download
     */
    public function download(int $id)
    {
        $file = Files::findOrFail($id);

        // Optional: Add authorization check here
        // $this->authorize('view', $file);

        return $this->downloadService->download($file);
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
            if (!Files::where('id', $id)->exists()) {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }

            Files::where('id', $id)->delete();
            return response()->json([
                'status' => 'Success',
                'message' => 'File deleted successfully',
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
            $folders = Files::onlyTrashed()->get();

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
            if (!Files::withTrashed()->where('id', $id)->exists()) {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }

            Files::withTrashed()
                ->where('id', $id)
                ->restore();

            return response()->json([
                'status' => 'Success',
                'message' => 'File restored successfully'
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
            if (empty($request->files_id)) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one File to restore'
                ], 422);
            }
            $allIds = [];
            foreach ($request->files_id as $file_id) {
                $allIds[] = $file_id;
            }
            Files::withTrashed()
                ->whereIn('id', array_unique($allIds))
                ->restore();

            return response()->json([
                'status' => 'Success',
                'message' => 'Files restored successfully'
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
     * @param FileDeleteService $deleteService
     * @return JsonResponse
     * @throws Exception
     */
    public function forceDelete($id, FileDeleteService $deleteService): JsonResponse
    {
        try {
            if (!Files::withTrashed()->where('id', $id)->exists()) {
                return response()->json([
                    'status' => 'No Content',
                    'message' => 'There is no relevant information for selected query'
                ], 210);
            }

            // Delete the file in the S3 Configuration
            $file = Files::withTrashed()->where('id', $id)->first();
            $deleteService->delete($file);

            // delete children first (important)
            Files::withTrashed()
                ->where('id',$id)
                ->forceDelete();

            return response()->json([
                'status' => 'Success',
                'message' => 'File permanently deleted successfully',
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
            if (empty($request->files_id)) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one file to delete'
                ], 422);
            }
            $allIds = [];
            foreach ($request->files_id as $file_id) {
                $allIds[] = $file_id;
            }
            Files::whereIn('id', array_unique($allIds))->delete();
            return response()->json([
                'status' => 'Success',
                'message' => 'Files deleted successfully',
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
     * @param FileDeleteService $deleteService
     * @return JsonResponse
     * @throws Exception
     */
    public function massForceDelete(Request $request, FileDeleteService $deleteService): JsonResponse
    {
        try {
            if (empty($request->files_id)) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Please select at least one unit to delete'
                ], 422);
            }
            $allIds = [];
            foreach ($request->files_id as $file_id) {
                // Delete the file in the S3 Configuration
                $file = Files::where('id', $file_id)->first();
                $deleteService->delete($file);
                $allIds[] = $file_id;
            }
            Files::whereIn('id', array_unique($allIds))->forceDelete();

            return response()->json([
                'status' => 'Success',
                'message' => 'Files are permanently deleted successfully',
            ], 200);

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    } // End Function
}
