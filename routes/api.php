<?php

use App\Http\Controllers\GenModel\RandomFlow;
use App\Http\Controllers\GenModel\PredictFlow;
use App\Http\Controllers\CommonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/gen-randomflow', [RandomFlow::class, 'matchDetails']);
Route::post('/gen-predictflow', [PredictFlow::class, 'matchDetails']);
Route::post('/update-stats', [CommonController::class, 'updateStats']);
Route::get('/list-teams', [CommonController::class, 'listTeams']);
