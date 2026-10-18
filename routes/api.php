<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

// Read-only company registry. Authenticate with: Authorization: Bearer <API_TOKEN>
Route::middleware([AuthenticateApiToken::class, 'throttle:60,1'])->group(function () {
    Route::get('/companies', [CompanyController::class, 'index'])->name('api.companies.index');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('api.companies.show');
});
