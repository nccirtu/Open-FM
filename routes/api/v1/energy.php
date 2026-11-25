<?php

use App\Http\Controllers\Api\V1\MeterController;
use App\Http\Controllers\Api\V1\MeterReadingController;
use Illuminate\Support\Facades\Route;

Route::get('meters/{meter}/consumption', [MeterController::class, 'consumption'])->name('meters.consumption');
Route::apiResource('meters', MeterController::class)->only(['index', 'show']);
Route::apiResource('meter-readings', MeterReadingController::class)->only(['index', 'store']);
