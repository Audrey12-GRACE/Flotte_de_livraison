<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\CourseController;
use App\Http\Controllers\LivreurController;

Route::apiResource('livreurs', LivreurController::class);
Route::apiResource('courses', CourseController::class);
Route::patch('courses/{course}/avancer', [CourseController::class, 'avancer']);
Route::patch('courses/{course}/annuler', [CourseController::class, 'annuler']);
Route::get('stats', [CourseController::class, 'stats']);