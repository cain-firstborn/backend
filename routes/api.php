<?php

use App\Http\Controllers\API;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->group(function (): void {
        Route::post('/sign-up', API\SignUpController::class);
        Route::post('/contact', API\ContactController::class);
    });
