<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'professional_title' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'This email is already registered.',
            'phone.unique' => 'This phone number is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'professional_title' => $validated['professional_title'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            if (! $token = Auth::guard('api')->attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials.',
                ], 401);
            }
        } catch (Exception) {
            return response()->json([
                'message' => 'Unable to login right now. Please try again later.',
            ], 500);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
        ]);
    }

    public function me()
    {
        return response()->json([
            'user' => Auth::guard('api')->user(),
        ]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
