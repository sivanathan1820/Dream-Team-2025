<?php

use App\Http\Controllers\GenModel\RandomFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/gen-randomflow', [RandomFlow::class, 'matchDetails']);
