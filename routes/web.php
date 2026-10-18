<?php

use App\Http\Controllers\OfferTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/track/offers/{token}', OfferTrackingController::class)
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('offers.track');
