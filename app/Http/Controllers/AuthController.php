<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth; // ✅ REQUIRED


class AuthController extends Controller
{
   public function register(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_no' => 'required|string|unique:users,phone_no',
            'password' => 'required|string|min:6|same:confirm_password',
            'confirm_password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create and save user
        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'phone_no' => $request->phone_no,
            'password' => Hash::make($request->password),
        ]);

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }


public function login(Request $request)
    {
        $credentials = $request->only('email_or_phone', 'password');

        $loginField = filter_var($credentials['email_or_phone'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        
        $user = User::where($loginField, $credentials['email_or_phone'])->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }
        
        $credentials = [
            $loginField => $credentials['email_or_phone'],
            'password' => $credentials['password']
        ];

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The provided credentials are incorrect.'
                ], 400);
            }
            if ($user->verification_status == 'N') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please verify your email before logging in.'
                ], 403);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create token.'
            ], 500);
        }
        $refreshToken = JWTAuth::fromUser(auth()->user(), ['exp' => strtotime('+1 week')]);
       
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'user' => auth()->user()
            ]
        ]);
    }

public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:6|confirmed',
    ]);

    // Ensure the user is authenticated via JWT
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }

    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'message' => 'Current password is incorrect'
        ], 400);
    }

    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'message' => 'Password updated successfully'
    ], 200);
}

 public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_no' => 'sometimes|string|unique:users,phone_no,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the user
        $user->update($request->only(['name', 'email', 'phone_no']));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
    public function logout(Request $request)
{
    try {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json([
            'error' => 'Failed to logout, please try again'
        ], 500);
    }
}
}
