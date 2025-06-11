<?php

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Game;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketGenerator;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WinnerHistoryController;
use App\Http\Controllers\SimplePasswordResetController;
use App\Http\Controllers\HousieGameController;
use App\Http\Controllers\GameWinnersController;
use App\Http\Controllers\GameUserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/game-winners/{id}', function ($id) {
    $game = Game::findOrFail($id);
    return response()->json([
        'winner_for_full_house' => $game->winner_for_full_house,
        'winner_for_other_categories' => $game->winner_for_other_categories,
    ]);
});

Route::get('/games', [GameController::class, 'index']);
// get all the games

Route::get('/games/today', [GameController::class, 'getTodayGames']);    // get all the games of today

Route::get('/games/{id}', [GameController::class, 'show']);              // get the game by game id

Route::get('/tickets', [TicketController::class, 'index']);              // get all the tickets

Route::middleware('jwt.auth')->get('/tickets', [TicketController::class, 'getTicketsByUser']);      // it will give the tickets of the user by giving user id

Route::get('/tickets/{id}', [TicketController::class, 'show']);          // get the ticket by ticket id

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->post('/update-password', [\App\Http\Controllers\AuthController::class, 'updatePassword']);

Route::put('/user/update', [UserController::class, 'updateProfile']);

Route::post('/logout', [AuthController::class, 'logout']);

Route::delete('/user', [App\Http\Controllers\UserController::class, 'deleteUser']);

Route::get('/winners', [WinnerHistoryController::class, 'index']);

Route::get('/winner-history', [WinnerHistoryController::class, 'getWinnerHistory']);

Route::post('/refresh-token', [AuthController::class, 'refresh']);

Route::post('/generate-tickets', [TicketGenerator::class, 'generate']);

Route::post('/send-reset-link', [SimplePasswordResetController::class, 'sendResetLink']);

Route::post('/forgotten-update-password', [SimplePasswordResetController::class, 'updatePassword']);

Route::post('/store-win/{winningCondition}', [HousieGameController::class, 'storeWinningPositions']);

Route::post('/game-users', [GameUserController::class, 'store']);

Route::post('/game-data', [GameWinnersController::class, 'getGameData']);

Route::get('/game-winners-top/{game_id}', [GameWinnersController::class, 'getWinners']);

