<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransactionController extends Controller
{
    public function fetchAll()
    {
        // Get all transactions with user info
        $transactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'All transactions fetched successfully',
            'transactions' => $transactions,
        ]);
    }

    public function userTransactions()
    {
        $user = JWTAuth::parseToken()->authenticate();

        // Fetch this user's transactions with user info
        $transactions = Transaction::with('user')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'User transactions fetched successfully',
            'transactions' => $transactions,
        ]);
    }
}
