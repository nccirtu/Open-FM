<?php

use App\Http\Controllers\Api\V1\BudgetPositionController;
use App\Http\Controllers\Api\V1\ChangeOrderController;
use App\Http\Controllers\Api\V1\CostEntryController;
use App\Http\Controllers\Api\V1\MilestoneController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectPhaseController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::apiResource('projects', ProjectController::class);
Route::apiResource('project-phases', ProjectPhaseController::class);
Route::apiResource('milestones', MilestoneController::class);
Route::apiResource('tasks', TaskController::class);
Route::apiResource('budget-positions', BudgetPositionController::class);
Route::apiResource('cost-entries', CostEntryController::class);
Route::apiResource('change-orders', ChangeOrderController::class);
