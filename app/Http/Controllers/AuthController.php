<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Exception;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Helpers\FirebaseHelper;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_no' => 'required|string|unique:users,phone_no',
            'password' => 'required|string|min:6|same:confirm_password',
            'confirm_password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();

            return response()->json([
                'status' => 'error',
                'message' => $firstErrorMessage
            ], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'phone_no' => $request->phone_no,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email_or_phone', 'password');

        $loginField = filter_var($credentials['email_or_phone'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_no';

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

    public function firebaseLogin(Request $request)
    {
        $request->validate([
            'idToken' => 'required|string'
        ]);

        $firebaseUser = FirebaseHelper::verifyIdToken($request->idToken);

        if (!$firebaseUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Firebase ID token.'
            ], 401);
        }

        $email = $firebaseUser['email'] ?? null;
        $uid = $firebaseUser['sub'] ?? null;
        $name = $firebaseUser['name'] ?? 'Firebase User';

        if (!$email || !$uid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Required user data missing in Firebase token.'
            ], 422);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'username' => Str::slug($name) . '-' . rand(1000, 9999),
                'name' => $name,
                'password' => bcrypt(Str::random(16)),
                'verification_status' => 'Y', // Assuming Firebase-verified users are trusted
            ]
        );

        $token = JWTAuth::fromUser($user);
        $refreshToken = JWTAuth::fromUser($user, ['exp' => strtotime('+1 week')]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'user' => $user
            ]
        ]);
    }

    public function updatePassword(Request $request)
    {
        // Attempt to parse and authenticate user via JWT token
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // Catch JWT exception and handle errors
            return response()->json([
                'status' => 'error',
                'Message' => 'Token is invalid or expired'], 401);
        }

        // If user is not authenticated
        if (!$user) {
            return response()->json([
                
                'status' => 'error',
                'Message' => 'User not authenticated'], 401);
        }

        // Validate the input request
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'Message' => 'Current password is incorrect'
            ], 400);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Return success message
        return response()->json([

            'status' => 'success',
            'message' => 'Password updated successfully'
        ], 200);
    }


    public function updateProfile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'Mesaage' => 'User not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_no' => 'sometimes|string|unique:users,phone_no,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'email', 'phone_no']));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 401);
        }
        
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([

                'status' => 'success',
                'message' => 'Successfully logged out'

            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([

                'status' => 'error',
                'Message' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Refresh token not provided'
            ], 400);
        }

        try {

            $token = JWTAuth::parseToken()->refresh();


            return response()->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully via parsed token',
                'data' => [
                    'access_token' => $token,
                    'refresh_token' => $refreshToken,
                ],
            ]);
        } catch (JWTException $e) {

            try {

                JWTAuth::setToken($refreshToken);
                $payload = JWTAuth::getPayload($refreshToken);
                $userId = $payload['sub'];


                $user = User::find($userId);
                $newAccessToken = JWTAuth::refresh($refreshToken);
                $newRefreshToken = JWTAuth::fromUser($user, ['exp' => strtotime('+1 week')]);

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'access_token' => $newAccessToken,
                        'refresh_token' => $newRefreshToken,
                    ],
                    'message' => 'Token refreshed successfully using the refresh token',
                ]);
            } catch (JWTException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to refresh token: ' . $e->getMessage(),
                ], 401);
            }
        }
    }

    public function addDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
        ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()
        //     ], 422);
        // }

        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();
    
            return response()->json([
                'status' => 'error',
                'message' => $firstErrorMessage
            ], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $user->device_token = $request->input('device_token');
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Device token added successfully',
            'data' => $user
        ]);
    }

    public function removeDeviceToken(Request $request)
    {
        $user = JWTAuth::user();

        if (!$user->device_token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device token not found'
            ], 404);
        }
        
        $user->device_token = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Device token removed successfully',
            'data' => $user
        ]);
    }

    public function redirect()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function callback()
    {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        // Find user by email or create
        $user = User::where('email', $facebookUser->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $facebookUser->name ?? 'Facebook User',
                'email' => $facebookUser->email ?? $facebookUser->id . '@facebook.com',
                'password' => bcrypt(str()->random(16)),
                'verification_status' => 'Y', // Optional: Mark as verified since FB login
            ]);
        }

        try {
            // 🔹 Generate JWT access token
            $token = JWTAuth::fromUser($user);

            // 🔹 Generate refresh token (1 week expiry)
            $refreshToken = JWTAuth::fromUser($user, ['exp' => strtotime('+1 week')]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create token.'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'user' => $user
            ]
        ]);
    }
}
