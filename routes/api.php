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
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;


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

Route::get('/games', [GameController::class, 'index']);             // get all the games

Route::get('/games/today', [GameController::class, 'getTodayGames']);    // get all the games of today

Route::get('/games/{id}', [GameController::class, 'show']);              // get the game by game id

Route::get('/active-user-games', [GameController::class, 'getActiveGamesForUser']);       // get all the active games for the authenticated user

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

Route::post('/deposit', [DepositController::class, 'store']);

Route::post('/withdraw', [WithdrawalController::class, 'store']);

Route::get('/game-winners-top/{game_id}', [GameWinnersController::class, 'getWinners']);

Route::get('/transactions', [TransactionController::class, 'fetchAll']);

Route::get('/user/transactions', [TransactionController::class, 'userTransactions']);

Route::post('/generate-number', [GameController::class, 'generateNextNumber']);

Route::post('/autopush/{id}', [GameController::class, 'autoPushNumber']);

// Route::get('/games/view/{id}', [GameController::class, 'view'])->name('admin.games.view');
Route::post('/admin/games/push-number/{id}', [GameController::class, 'pushNumber'])->name('admin.games.pushNumber');

Route::post('/add-device-token', [AuthController::class, 'addDeviceToken']);

Route::post('/remove-device-token', [AuthController::class, 'removeDeviceToken']);

Route::get('/notifications', [NotificationController::class, 'userNotifications']);
Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

Route::post('/set-theme', [GameController::class, 'storeTheme']);
