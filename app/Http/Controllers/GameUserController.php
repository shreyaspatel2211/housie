<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameUser;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class GameUserController extends Controller
{
    public function store(Request $request)
    {
        // Authenticate user from token
        $user = JWTAuth::parseToken()->authenticate();

        // Validate input
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'no_of_tickets' => 'required|integer|min:1',
        ]);

        $gameId = $request->game_id;
        $ticketCount = $request->no_of_tickets;

        // Get ticket price from games table
        $game = Game::findOrFail($gameId);
        $ticketPrice = $game->ticket_price;

        // Calculate total cost
        $totalAmount = $ticketCount * $ticketPrice;

        // Check if user has enough balance
        if ($user->balance < $totalAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient balance'
            ], 400);
        }

        // Save booking
        $gameUser = GameUser::create([
            'game_id' => $gameId,
            'user_id' => $user->id,
            'no_of_tickets' => $ticketCount,
        ]);

        // Deduct balance and save
        $user->balance -= $totalAmount;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket(s) booked and balance updated successfully.',
            'game_user' => $gameUser,
            'ticket_price' => $ticketPrice,
            'total_amount' => $totalAmount,
            'remaining_balance' => $user->balance,
        ], 201);
    }
}
