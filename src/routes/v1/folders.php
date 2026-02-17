<?php


use Illuminate\Support\Facades\Route;
use Omnics\FileManagement\Http\Controllers\FileManager\FolderController;

/*
|--------------------------------------------------------------------------
| API Routes for - FOLDERS
|--------------------------------------------------------------------------
|
| CRUD Operations of the FOLDERS
*/

Route::group(['prefix' => 'folders'], function () {

    Route::post('index/{folder_id?}',[FolderController::class, 'index'])->name('folders.index');

    Route::post('massDelete',[FolderController::class, 'massDelete'])->name('folders.massDelete');

    Route::post('tags/{id}',[FolderController::class, 'updateTags'])->name('folders.update.tags');

    Route::post('general/{id}',[FolderController::class, 'update'])->name('folders.update');

    /* BEGIN -- FOLDERS - TRASH */
    Route::get('retrieve/all',[FolderController::class, 'retrieve'])->name('folders.retrieve');

    Route::post('restore/{id}',[FolderController::class, 'restore'])->name('folders.restore');

    Route::post('massRestore',[FolderController::class, 'massRestore'])->name('folders.massRestore');

    Route::post('forceDelete/{id}',[FolderController::class, 'forceDelete'])->name('folders.forceDelete');

    Route::post('massForceDelete',[FolderController::class, 'massForceDelete'])->name('folders.massForceDelete');
    /* END -- FOLDERS - TRASH */

    Route::get('{id}',[FolderController::class, 'show'])->name('folders.show');

    Route::delete('{id}',[FolderController::class, 'destroy'])->name('folders.destroy');

    // IMPORTANT: keep this LAST
    Route::post('{parent_id?}',[FolderController::class, 'store'])->name('folders.store');
});

