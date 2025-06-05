<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Models\WinnerHistory;

class WinnerHistoryController extends Controller
{
    public function index(Request $request)
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

        $winners = WinnerHistory::all();
        return response()->json([
            'status' => true,
            'data' => $winners
        ]);
    }

    public function getWinnerHistory(Request $request, $user_id)
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
        $winnerHistory = WinnerHistory::where('user_id', $user_id)->get();

        if ($winnerHistory->isEmpty()) {
            return response()->json(['message' => 'No history found for this user.'], 404);
        }

        return response()->json($winnerHistory);
    }
}
