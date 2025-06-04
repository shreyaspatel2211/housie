<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;


class GameController extends Controller
{
    public function index(): JsonResponse
    {
        $games = Game::all(); // Get all games from the database
        return response()->json($games);
    }

    public function getTodayGames(): \Illuminate\Http\JsonResponse
    {
        $today = \Carbon\Carbon::today()->toDateString();
        $games = \App\Models\Game::whereDate('date', $today)->get();
        return response()->json($games);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $game = Game::find($id);

        if (!$game) {
            return response()->json(['error' => 'Game not found'], 404);
        }

        return response()->json($game);
    }
}
