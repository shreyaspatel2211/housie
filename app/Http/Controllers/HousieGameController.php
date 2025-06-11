<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WinnerHistory;
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
            return response()->json([
                'status' => 'error',
                'Message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'game_id' => 'required|integer',
            'ticket_id' => 'required|integer',
            'ticket_json' => 'required|array',  
        ]);

        $validConditions = ['early_five', 'first_row', 'second_row', 'third_row', 'full_house'];

        if (!in_array($winningCondition, $validConditions)) {
            return response()->json([
                'status' => 'error',
                'Message' => 'Invalid winning condition'], 400);
        }

        $positions = $request->ticket_json;

        $firstRow = [1, 4, 7, 10, 13, 16, 19, 22, 25];
        $secondRow = [2, 5, 8, 11, 14, 17, 20, 23, 26];
        $thirdRow = [3, 6, 9, 12, 15, 18, 21, 24, 27];

        switch ($winningCondition) {
            case 'early_five':
                $marked = array_filter($positions, fn($val) => $val === true);
                if (count($marked) < 5) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'At least 5 marked positions are required for early five.'], 422);
                }
                break;

            case 'first_row':
                foreach ($firstRow as $pos) {
                    if (empty($positions[$pos]) || $positions[$pos] !== true) {
                        return response()->json([
                            'status' => 'error',
                            'Message' => 'Not all positions in the first row are marked.'], 422);
                    }
                }
                break;

            case 'second_row':
                foreach ($secondRow as $pos) {
                    if (empty($positions[$pos]) || $positions[$pos] !== true) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Not all positions in the second row are marked.'], 422);
                    }
                }
                break;

            case 'third_row':
                foreach ($thirdRow as $pos) {
                    if (empty($positions[$pos]) || $positions[$pos] !== true) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Not all positions in the third row are marked.'], 422);
                    }
                }
                break;

            case 'full_house':
                for ($i = 1; $i <= 27; $i++) {
                    if (empty($positions[$i]) || $positions[$i] !== true) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'All 27 positions must be marked for full housie.'], 422);
                    }
                }
                break;
        }

        $existingClaim = WinnerHistory::where('user_id', $user->id)
            ->where('game_id', $request->game_id)
            ->where('ticket_id', $request->ticket_id)
            ->where($winningCondition, 1)
            ->first();

        if ($existingClaim) {
            return response()->json([
                'status' => 'error',
                'message' => "You have already claimed '$winningCondition' for this ticket.",
            ], 409);
        }

        $winnerHistory = new WinnerHistory();
        $winnerHistory->user_id = $user->id;
        $winnerHistory->game_id = $request->game_id;
        $winnerHistory->ticket_id = $request->ticket_id;
        $winnerHistory->ticket_json = json_encode($positions);

        $winnerHistory->early_five = 0;
        $winnerHistory->first_row = 0;
        $winnerHistory->second_row = 0;
        $winnerHistory->third_row = 0;
        $winnerHistory->full_house = 0;

        $winnerHistory->$winningCondition = 1;

        $winnerHistory->save();

        return response()->json([
            'status' => 'success',
            'message' => "$winningCondition claim stored successfully.",
            'data' => $winnerHistory
        ], 201);
    }
}
