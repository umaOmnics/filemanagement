<?php

use Illuminate\Support\Facades\Route;

Route::prefix('file-management')
    ->middleware('api')
    ->group(function () {

        Route::prefix('v1')->group(function () {

            require __DIR__ . '/v1/folders.php';
            require __DIR__ . '/v1/files.php';

        });

    });
