<?php

use App\Http\Controllers\Api\InspectionIngestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Machine-to-machine endpoints for the computer vision (YOLO) pipeline to
| post inspection results into the system. Authenticated via Sanctum
| personal access tokens issued to a dedicated service account.
|
*/

Route::middleware(['auth:sanctum', 'abilities:inspections:create'])->group(function () {
    Route::post('/inspections', [InspectionIngestController::class, 'store'])
        ->name('api.inspections.store');
});
