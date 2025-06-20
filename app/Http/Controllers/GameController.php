<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Game;
use Carbon\Carbon;
use App\Events\NumberGenerated;

class GameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 401);
        }
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'], 401);
        }

        $games = Game::all(); 
        return response()->json($games);
    }

    public function getTodayGames(Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'Message' => 'Token not provided'
            ], 401);
        }
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'Message' => 'User not authenticated'], 401);
        }

        $today = \Carbon\Carbon::today()->toDateString();
        $games = \App\Models\Game::whereDate('date', $today)->get();
        return response()->json($games);
    }

    public function show($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 401);
        }
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'Message' => 'User not authenticated'], 401);
        }

        $game = Game::find($id);
        if (!$game) {
            return response()->json([
                'status' => 'error',
                'Message' => 'Game not found'], 404);
        }

        return response()->json($game);
    }

    public function generateNextNumber(Request $request)
    {
        $gameId = $request->input('game_id');
        $game = Game::findOrFail($gameId);

        // generate random number from remaining
        $queue = json_decode($game->queue, true) ?? [];
        $all = range(1, 89);
        $remaining = array_values(array_diff($all, $queue));
        if (empty($remaining)) return response()->json(['message' => 'All numbers generated']);

        $nextNumber = $remaining[array_rand($remaining)];
        $queue[] = $nextNumber;
        $game->queue = json_encode($queue);
        $game->save();

        broadcast(new NumberGenerated($nextNumber, $gameId))->toOthers();

        return response()->json(['next' => $nextNumber]);
    }

    public function view($id)
    {
        $game = Game::findOrFail($id);
        $queue = json_decode($game->queue, true) ?? [];
        $all = range(1, 89);
        $remaining = array_values(array_diff($all, $queue));

        return view('vendor.voyager.games.push', compact('game', 'queue', 'remaining'));
    }

    public function pushNumber(Request $request, $id)
    {
        $game = Game::findOrFail($id);
        $queue = json_decode($game->queue, true) ?? [];

        $number = (int) $request->input('number');

        // Prevent duplicate push
        if (in_array($number, $queue)) {
            return back()->with('error', 'Number already pushed');
        }

        $queue[] = $number;
        $game->queue = json_encode($queue);
        $game->save();

        broadcast(new NumberGenerated($number, $id))->toOthers();

        return back()->with('success', "Number $number pushed successfully");
    }

    public function autoPushNumber($id)
    {
        $game = Game::findOrFail($id);
        $queue = json_decode($game->queue, true) ?? [];

        $all = range(1, 89);
        $remaining = array_values(array_diff($all, $queue));

        if (count($remaining) === 0) {
            return response()->json(['message' => 'All numbers pushed'], 200);
        }

        // Pick a random number
        $randomNumber = $remaining[array_rand($remaining)];

        // Push to queue
        $queue[] = $randomNumber;
        $game->queue = json_encode($queue);
        $game->save();

        broadcast(new NumberGenerated($randomNumber, $id))->toOthers();

        return response()->json(['message' => "Number $randomNumber auto-pushed"], 200);
    }

}
