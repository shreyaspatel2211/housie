<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

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
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'], 401);
        }

        $winners = WinnerHistory::with(['user', 'game'])->get();
        return response()->json([
            'status' => true,
            'data' => $winners
        ]);
    }

    public function getWinnerHistory(Request $request)
    {
        // Extract token from Authorization header
        $token = $request->header('Authorization');

        // If token is not provided, return an error
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 401);
        }

        try {
            // Attempt to authenticate the user via the token
            $user = JWTAuth::parseToken()->authenticate();

            // If no user is found, return error
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'], 401);
            }
        } catch (Exception $e) {
            // Catch exceptions and provide a detailed error message if token is invalid
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid: ' . $e->getMessage()], 401);
        }

        // Retrieve the winner history based on the authenticated user's ID
        $winnerHistory = WinnerHistory::where('user_id', $user->id)->get();

        // If no history found, return a message
        if ($winnerHistory->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No history found for this user.'], 404);
        }

        // Return the winner history
        return response()->json($winnerHistory);
    }
}
