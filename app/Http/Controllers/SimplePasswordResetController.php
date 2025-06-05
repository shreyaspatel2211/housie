<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SimplePasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $timestamp = now()->timestamp;
        $link = url("/reset-password-form?user_id={$user->id}&ts={$timestamp}");

        Mail::raw("Click here to reset your password: $link", function ($message) use ($user) {
            $message->to($user->email)->subject('Reset Password');
        });

        return response()->json(['message' => 'Reset link sent to your email.']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'password' => 'required|min:6|confirmed',
            'ts' => 'required|integer',
        ]);

        $linkTime = Carbon::createFromTimestamp($request->ts);
        $now = Carbon::now();

        if ($now->diffInMinutes($linkTime) > 10) {
            return response()->json(['message' => 'The reset link has expired.'], 403);
        }

        $user = User::find($request->user_id);
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
