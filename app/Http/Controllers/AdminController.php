<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    public function registerDoctorfromAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'phone_number' => 'required|string',
            'role' => 'nullable|string|in:dokter', // Optional role, defaults to 'user'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone_number' => $request->phone_number,
            'role' => $request->role ?? 'dokter',
        ]);

        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'dokter has been registered successfully',
        ], 201);
    }

}
