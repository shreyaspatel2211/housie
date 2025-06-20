<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function push()
    {
        // Get the latest notification
        $notification = Notification::latest()->first();

        if (!$notification) {
            return redirect()->back()->with('error', 'No notification found to push.');
        }

        // Get all users with FCM tokens
        $tokens = \App\Models\User::whereNotNull('device_token')->pluck('device_token')->toArray();

        if (empty($tokens)) {
            return redirect()->back()->with('error', 'No users with FCM tokens.');
        }

        $messaging = Firebase::messaging();

        // Create the base notification
        $firebaseNotification = FirebaseNotification::create($notification->title, $notification->message);

        // Create the message object (without target)
        $message = CloudMessage::new()->withNotification($firebaseNotification);

        try {
            // Send to multiple devices using multicast
            /** @var MulticastSendReport $report */
            $report = $messaging->sendMulticast($message, $tokens);

            if ($report->successes()->count() > 0) {
                return redirect()->back()->with('success', 'Notification sent to ' . $report->successes()->count() . ' users!');
            } else {
                return redirect()->back()->with('error', 'Notification failed for all tokens.');
            }
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            \Log::error('FCM Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Firebase Messaging Exception occurred.');
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            \Log::error('Firebase SDK Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Firebase SDK Exception occurred.');
        }
    }

    public function userNotifications(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'notifications' => $notifications
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token invalid or expired'], 401);
        }
    }

    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();
    
            return response()->json([
                'status' => 'error',
                'message' => $firstErrorMessage
            ], 422);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            $notification = Notification::where('id', $request->notification_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found or unauthorized.'
                ], 404);
            }

            $notification->read = 'true';
            $notification->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read.',
                'notification' => $notification
            ], 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token invalid or expired'], 401);
        }
    }


}
