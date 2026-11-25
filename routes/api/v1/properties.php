<?php

use App\Http\Controllers\Api\V1\ClassificationNodeController;
use App\Http\Controllers\Api\V1\CompanyAddressController;
use App\Http\Controllers\Api\V1\CompanyContactController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\StructureNodeController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('properties', PropertyController::class);
Route::apiResource('structure-nodes', StructureNodeController::class);
Route::apiResource('classification-nodes', ClassificationNodeController::class)->only(['index', 'show']);
Route::apiResource('companies', CompanyController::class);
Route::apiResource('companies.addresses', CompanyAddressController::class)->shallow();
Route::apiResource('companies.contacts', CompanyContactController::class)->shallow();
Route::apiResource('users', UserController::class)->only(['index', 'show']);
Route::get('organization', [OrganizationController::class, 'show'])->name('organization.show');
