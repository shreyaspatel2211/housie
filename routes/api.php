<?php

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Game;
use App\Http\Controllers\TicketController;


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
Route::middleware('auth:api')->group(function () {
    Route::get('/games', [GameController::class, 'index']);
});                                                                        // get all the games

Route::get('/games/today', [GameController::class, 'getTodayGames']);    // get all the games of today

Route::get('/games/{id}', [GameController::class, 'show']);              // get the game by game id

Route::get('/tickets', [TicketController::class, 'index']);              // get all the tickets

Route::get('/tickets/user/{userId}', [TicketController::class, 'getTicketsByUser']);      // it will give the tickets of the user by giving user id

Route::get('/tickets/{id}', [TicketController::class, 'show']);          // get the ticket by ticket id

