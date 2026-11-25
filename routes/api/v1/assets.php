<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetTypeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('assets', AssetController::class);
Route::apiResource('asset-types', AssetTypeController::class);
