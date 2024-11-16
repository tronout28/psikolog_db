<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
 
    public function seeOnlyDoctor()
    {
        $users = User::where('role', 'dokter')
        ->where('id', '!=', auth()->user()->id)
        ->get();
        

        return response()->json($users);
    }

    public function createProfileUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gender'=>['required', Rule::in(['laki-laki', 'perempuan'])],
            'email' => 'required|email|unique:users',
            // 'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'ages' => 'required|integer',
            'status' => ['required', Rule::in(['berkeluarga', 'tidak berkeluarga'])],
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated or not found'
            ], 401);
        }

        // if ($request->hasFile('profile_picture')) {
        //     $image = $request->file('profile_picture');
        //     $imageName = time() . '.' . $image->extension();
        //     $image->move(public_path('images-user'), $imageName);

        //     if ($user->profile_picture && file_exists(public_path('images-user/' . $user->profile_picture))) {
        //         unlink(public_path('images-user/' . $user->profile_picture));
        //     }

        //     $user->profile_picture = $imageName;
        //     $user->save();
        // }

        // Update informasi pengguna lainnya
        $user->name = $request->name;
        $user->email = $request->email;
        $user->gender = $request->gender;
        $user->address = $request->address;
        $user->ages = $request->ages;
        $user->status = $request->status;

        $user->save();

        // Ganti nilai `profile_picture` dengan URL lengkap
        // $user->profile_picture = url('images-user/' . $user->profile_picture);

        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'User profile has been updated successfully',
        ], 201);
    }

    public function updateProfileUser(Request $request,$id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'gender' => ['nullable', Rule::in(['laki-laki', 'perempuan'])],
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'ages' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['berkeluarga', 'tidak berkeluarga'])],
        ]);

        if ($request->filled('name')) $user->name = $request->name;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('gender')) $user->gender = $request->gender;
        if ($request->filled('ages')) $user->ages = $request->ages;
        if ($request->filled('status')) $user->status = $request->status;

        $user->save();

        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'User profile has been updated successfully',
        ], 201);
    }

    
}
