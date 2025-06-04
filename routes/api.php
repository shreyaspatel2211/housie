<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Game;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;


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

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->post('/update-password', [\App\Http\Controllers\AuthController::class, 'updatePassword']);

Route::put('/user/update', [UserController::class, 'updateProfile']);

Route::post('/logout', [AuthController::class, 'logout']);

Route::delete('/user', [App\Http\Controllers\UserController::class, 'deleteUser']);

