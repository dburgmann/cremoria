<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\Game\ConstructionController;
use App\Http\Controllers\Game\HighscoreController;
use App\Http\Controllers\Game\MapController;
use App\Http\Controllers\Game\TownController;
use App\Http\Controllers\Game\MessagesController;
use App\Http\Controllers\Game\MovementsController;
use App\Http\Controllers\Game\OverviewController;
use App\Http\Controllers\Game\RecruitmentController;
use App\Http\Controllers\Game\ResearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('login'))->name('home');

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/verify/{code}', [VerifyController::class, 'verify'])->name('verify');

/*
|--------------------------------------------------------------------------
| Authenticated ingame routes (role: login)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:login'])->prefix('game')->name('game.')->group(function () {
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');

    // Town switching
    Route::post('/town', [TownController::class, 'setTown'])->name('setTown');

    // Construction
    Route::get('/construction', [ConstructionController::class, 'index'])->name('construction');
    Route::post('/construction', [ConstructionController::class, 'produce'])->name('construction.produce');

    // Recruitment
    Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('recruitment');
    Route::post('/recruitment', [RecruitmentController::class, 'produce'])->name('recruitment.produce');

    // Research
    Route::get('/research', [ResearchController::class, 'index'])->name('research');
    Route::post('/research', [ResearchController::class, 'produce'])->name('research.produce');

    // Movements
    Route::get('/movements', [MovementsController::class, 'index'])->name('movements');
    Route::post('/movements', [MovementsController::class, 'generate'])->name('movements.generate');

    // Messages
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages');
    Route::get('/messages/{id}', [MessagesController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{id}', [MessagesController::class, 'destroy'])->name('messages.destroy');

    // Map
    Route::get('/map', [MapController::class, 'index'])->name('map');

    // Highscore
    Route::get('/highscore', [HighscoreController::class, 'index'])->name('highscore');
});
