<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WinnerHistory;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;


class HousieGameController extends Controller
{
    public function storeWinningPositions(Request $request, $winningCondition)
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
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        // Validation
        $request->validate([
            'game_id' => 'required|integer',
            'positions' => 'required|array',
        ]);

        // Check if the winning condition is valid
        $validConditions = ['early_five', 'first_row', 'second_row', 'third_row', 'full_housie'];
        if (!in_array($winningCondition, $validConditions)) {
            return response()->json(['message' => 'Invalid winning condition'], 400);
        }

        // Check if the game_id and user_id are correct (you can customize this based on your logic)
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'User authentication failed or user_id mismatch'], 401);
        }

        // Handle saving the positions for the specified user and game
        $winningHistory = WinnerHistory::where('game_id', $request->game_id)->first();

        // If no record exists, create a new one
        if (!$winningHistory) {
            $winningHistory = new WinnerHistory();
            $winningHistory->user_id = $request->user_id;
            $winningHistory->game_id = $request->game_id;
        }

        // Save the winning condition (dynamic field) with the positions
        $winningHistory->$winningCondition = json_encode($request->positions);
        $winningHistory->save();

        return response()->json([
            'message' => "$winningCondition positions saved successfully",
            'data' => $winningHistory
        ], 201);
    }
}
