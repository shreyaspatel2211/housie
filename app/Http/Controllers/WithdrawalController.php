<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class WithdrawalController extends Controller
{
    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // Check if user has enough balance
        if ($user->balance < $request->amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient balance for withdrawal.'
            ], 400);
        }

        // Create withdrawal record
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount'  => $request->amount,
        ]);

        // Deduct from user balance
        $user->balance -= $request->amount;
        $user->save();

        // Log transaction
        Transaction::create([
            'user_id'    => $user->id,
            'amount'     => $request->amount,
            'description'=> $user->name . ' withdraw ' . $request->amount . ' Rupees',
            'type'       => 'debit',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Withdrawal successful and recorded in transactions.',
            'withdrawal' => $withdrawal,
            'updated_balance' => $user->balance,
        ], 201);
    }
}
