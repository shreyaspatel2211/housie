<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DepositController extends Controller
{
    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // Create deposit record
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'amount'  => $request->amount,
        ]);

        // Add to user balance
        $user->balance += $request->amount;
        $user->save();

        // Log transaction
        Transaction::create([
            'user_id'    => $user->id,
            'amount'     => $request->amount,
            'description'=> $user->name . ' deposited ' . $request->amount . ' Rupees',
            'type'       => 'credit',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Deposit successful and recorded in transactions.',
            'deposit' => $deposit,
            'updated_balance' => $user->balance,
        ], 201);
    }
}
