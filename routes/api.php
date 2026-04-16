<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MealPlanController;

Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [LoginController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LogoutController::class, 'store']);
    Route::get('/me', [MeController::class, 'show']);

    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::post('/weight-entry', [DashboardController::class, 'storeWeight']);
    Route::get('/weight-history', [DashboardController::class, 'history']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/meal-plans', [MealPlanController::class, 'index']);
    Route::post('/meal-plans', [MealPlanController::class, 'store']);
    Route::delete('/meal-plans/{mealPlan}', [MealPlanController::class, 'destroy']);
    Route::post('/meal-plans/{mealPlan}/cancel', [MealPlanController::class, 'cancel']);
    Route::get('/meal-plans/{mealPlan}', [MealPlanController::class, 'show']);
    Route::get('/meal-plans/{mealPlan}/weeks/{week}', [MealPlanController::class, 'showWeek']);
    Route::post('/meal-plans/{mealPlan}/weeks/{week}/generate', [MealPlanController::class, 'generateWeek']);
    Route::get('/meal-plans/{mealPlan}/days/{day}', [MealPlanController::class, 'showDay']);
    Route::get('/meals/{meal}', [MealPlanController::class, 'showMeal']);
});
