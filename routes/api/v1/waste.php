<?php

use App\Http\Controllers\Api\V1\WasteCollectionController;
use App\Http\Controllers\Api\V1\WasteContainerController;
use App\Http\Controllers\Api\V1\WasteFillReadingController;
use App\Http\Controllers\Api\V1\WastePickupController;
use Illuminate\Support\Facades\Route;

Route::apiResource('waste-containers', WasteContainerController::class)->only(['index', 'show']);
Route::apiResource('waste-pickups', WastePickupController::class)->only(['index']);
Route::apiResource('waste-collections', WasteCollectionController::class)->only(['index', 'store']);
Route::apiResource('waste-fill-readings', WasteFillReadingController::class)->only(['store']);
