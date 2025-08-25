<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\game\GameController;
use App\Http\Controllers\game\MatchmakingController;
use App\Http\Controllers\game\LeaderboardController;

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

// --- Public routes ---
// These routes do not require authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/game/start', [GameController::class, 'startGame']);

// --- Protected routes ---
// These routes require a valid Sanctum token to be accessed
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Matchmaking Route ---
    Route::post('/matchmaking/join', [MatchmakingController::class, 'join']);
    // route for submitting a word. Note the {game} parameter ---
    Route::post('/game/{game}/submit', [GameController::class, 'submitWord']);
    // Add the leaderboard route
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
});


