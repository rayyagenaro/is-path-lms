<?php

use App\Http\Controllers\Api\CompetencyController;
use App\Http\Controllers\Api\CareerController;
use App\Http\Controllers\Api\OntologyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'student'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'student'])->group(function () {
    Route::get('/v1/competencies/me', [CompetencyController::class, 'profile']);
    Route::get('/v1/recommendations/me', [CompetencyController::class, 'recommendations']);
    Route::get('/v1/career-clusters', [CareerController::class, 'clusters']);
    Route::get('/v1/career-roles/{slug}', [CareerController::class, 'show']);
    Route::get('/v1/career-roles/{slug}/readiness', [CareerController::class, 'readiness']);
    Route::get('/v1/courses', [CareerController::class, 'courses']);
    Route::get('/v1/courses/{course}/modules', [CareerController::class, 'modules']);
    Route::get('/v1/ontology/graph', [OntologyController::class, 'graph']);
});
Route::get('/health', fn () => response()->json(['status'=>'ok','service'=>'is-path']));
