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
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|confirmed|min:6',
            'email' => 'nullable|email|unique:users',
            'role' => 'nullable|string|in:user,dokter,admin', 
            'notification_token' => 'nullable|string',
        ]);

        $user = User::create([
            'phone_number' => $request->phone_number,
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'email' => $request->email,
            'role' => $request->role ?? 'user',
            'notification_token' => $request->notification_token,
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
            'notification_token' => 'nullable|string',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();
        
        $token = $user->createToken('auth_token')->plainTextToken;

           // Update the notification token if provided
        if ($request->filled('notification_token')) {
            $user->notification_token = $request->notification_token;
            $user->save();
        }

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

        $request->user()->notification_token = null;

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

    /**
     * Log in an existing user.
     */
    public function loginForadmin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Email or name is required.',
            'password.required' => 'Password is required.',
        ]);
        
        $user = User::where('email', $request->login)
                    ->orWhere('name', $request->login)
                    ->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;
    
    
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'admin' => $user->only(['id', 'name', 'email', 'role']),  // Include role in response
        ]);
    }

     /**
     * Log in an existing user.
     */
    public function loginFordokter(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'notification_token' => 'nullable|string',
        ], [
            'login.required' => 'Email or username is required.',
            'password.required' => 'Password is required.',
        ]);
        
        $user = User::where('email', $request->login)
                    ->orWhere('name', $request->login)
                    ->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }
        // Update the notification token if provided
        if ($request->filled('notification_token')) {
            $user->notification_token = $request->notification_token;
            $user->save();
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;
    
    
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'dokter' => $user->only(['id', 'name', 'email', 'role']),  // Include role in response
        ]);
    }
}
