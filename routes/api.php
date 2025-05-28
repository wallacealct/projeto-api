<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController; // Import ProductController

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

// Authentication Routes (JWT)
Route::group([
    "middleware" => "api",
    "prefix" => "auth"
], function ($router) {
    Route::post("login", [AuthController::class, "login"]);
    Route::post("register", [AuthController::class, "register"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::post("refresh", [AuthController::class, "refresh"]);
    Route::get("me", [AuthController::class, "me"]);
});

// Product Routes (Protected by JWT middleware in Controller constructor)
Route::apiResource("products", ProductController::class);

// Custom route for searching products by name
Route::get("products/search", [ProductController::class, "showByName"])->middleware("auth:api");


