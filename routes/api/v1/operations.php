<?php

use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\MaintenanceOrderController;
use App\Http\Controllers\Api\V1\MaintenancePlanController;
use App\Http\Controllers\Api\V1\SlaDefinitionController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketReplyController;
use Illuminate\Support\Facades\Route;

Route::apiResource('tickets', TicketController::class);
Route::apiResource('tickets.replies', TicketReplyController::class)->shallow();
Route::apiResource('sla-definitions', SlaDefinitionController::class)->only(['index', 'show']);
Route::apiResource('maintenance-plans', MaintenancePlanController::class);
Route::apiResource('maintenance-orders', MaintenanceOrderController::class);
Route::apiResource('contracts', ContractController::class);
