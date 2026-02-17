<?php


use Illuminate\Support\Facades\Route;
use Omnics\FileManagement\Http\Controllers\FileManager\FileController;

/*
|--------------------------------------------------------------------------
| API Routes for - FILES
|--------------------------------------------------------------------------
| CRUD Operations of the FILES
*/

Route::group(['prefix' => 'files'], function () {

    Route::post('massDelete',[FileController::class, 'massDelete'])->name('files.massDelete');

    Route::post('tags/{id}',[FileController::class, 'updateTags'])->name('files.update.tags');

    Route::post('download/{id}',[FileController::class, 'download'])->name('files.download');

    Route::post('general/{id}',[FileController::class, 'update'])->name('files.update');

    /* BEGIN -- FILES - TRASH */
    Route::get('retrieve/all',[FileController::class, 'retrieve'])->name('files.retrieve');

    Route::post('restore/{id}',[FileController::class, 'restore'])->name('files.restore');

    Route::post('massRestore',[FileController::class, 'massRestore'])->name('files.massRestore');

    Route::post('forceDelete/{id}',[FileController::class, 'forceDelete'])->name('files.forceDelete');

    Route::post('massForceDelete',[FileController::class, 'massForceDelete'])->name('files.massForceDelete');
    /* END -- FILES - TRASH */

    Route::delete('{id}',[FileController::class, 'destroy'])->name('files.destroy');

    // IMPORTANT: keep this LAST
    Route::post('{folder_id?}',[FileController::class, 'store'])->name('files.store');
});

