<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameUser;
use App\Models\Transaction; // Make sure this model exists
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\Notification;

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

        // Save transaction
        Transaction::create([
            'user_id'    => $user->id,
            'amount'     => $totalAmount,
            'description'=> 'Ticket purchase for Game ID: ' . $gameId,
            'type'       => 'debit', // or 'purchase' depending on your type values
        ]);

        $notification = Notification::create([
            'title'      => 'Money Debited',
            'message'    => 'Ticket purchase for Game ID: ' . $gameId,
            'user_id'    => $user->id,
            'type'       => 'debit',
            'read'       => 'false'
        ]);

        // Send FCM push notification using Kreait
        if (!empty($user->device_token)) {
            $messaging = Firebase::messaging();

            $message = CloudMessage::withTarget('token', $user->device_token)
                ->withNotification(FirebaseNotification::create($notification->title, $notification->message))
                ->withHighestPossiblePriority();

            try {
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                \Log::error('FCM Messaging Error: ' . $e->getMessage());
            } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
                \Log::error('Firebase Error: ' . $e->getMessage());
            }
        }

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
