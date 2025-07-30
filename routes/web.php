<?php

use Illuminate\Support\Facades\Route;
use TCG\Voyager\Facades\Voyager;
use App\Http\Controllers\HousieGameController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

Route::get('/reset-password-form', function () {
    return view('reset-password-form');
});

Route::get('/login', function () {
    return 'Login page placeholder.';
})->name('login');   // dummy login route

Route::get('/admin/games/view/{id}', [GameController::class, 'view'])->name('admin.games.view');
Route::get('/admin/notifications/push', [App\Http\Controllers\Admin\NotificationController::class, 'push'])->name('voyager.notifications.push');
Route::post('/admin/games/auto-push/{id}', [GameController::class, 'autoPushNumber'])->name('admin.games.autoPush');

Route::get('/game/viewtest', [GameController::class, 'viewTest'])->name('game.viewTest');

Route::get('/trigger-autopush/{id}', [GameController::class, 'triggerAutoPush']);

Route::get('auth/facebook', [AuthController::class, 'redirect'])->name('facebook.redirect');        
Route::get('auth/facebook/callback', [AuthController::class, 'callback'])->name('facebook.callback');
