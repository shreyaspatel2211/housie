<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

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

        $notification = Notification::create([
            'title'      => 'Money Withdraw',
            'message'    => $user->name . ' withdraw ' . $request->amount . ' Rupees',
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
            'message' => 'Withdrawal successful and recorded in transactions.',
            'withdrawal' => $withdrawal,
            'updated_balance' => $user->balance,
        ], 201);
    }
}
