<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

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

        $notification = Notification::create([
            'title'      => 'Money Deposited',
            'message'    => $user->name . ' deposited ' . $request->amount . ' Rupees',
            'user_id'    => $user->id,
            'type'       => 'credit',
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
            'message' => 'Deposit successful and recorded in transactions.',
            'deposit' => $deposit,
            'updated_balance' => $user->balance,
        ], 201);
    }
}
