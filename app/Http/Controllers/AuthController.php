<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15',
            'role' => 'nullable|string|in:user,dokter,admin', // Optional role, defaults to 'user'
        ]);

        $user = User::create([
            'phone_number' => $request->phone_number,
            'role' => $request->role ?? 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    /**
     * Log in an existing user.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

   public function detailUser(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Check the user's role (admin, user, dokter).
     */
    public function checkRole(Request $request)
    {
        $role = $request->user()->role;

        return response()->json([
            'role' => $role,
            'is_admin' => $request->user()->isAdmin(),
            'is_user' => $request->user()->isUser(),
            'is_dokter' => $request->user()->isDokter(),
        ]);
    } 
}
