<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function registerDoctorfromAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'phone_number' => 'required|string',
            'ages' => 'required|string',
            'address' => 'required|string',
            'role' => 'nullable|string|in:dokter', // Optional role, defaults to 'user'
            'str_number' => 'required|int',
            'school' => 'required|string',
            'gender' => ['required', Rule::in(['laki-laki', 'perempuan'])],
            'description' => 'required|string',
            'experience' => 'required|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone_number' => $request->phone_number,
            'ages' => $request->ages,
            'address' => $request->address,
            'role' => $request->role ?? 'dokter',
            'str_number' => $request->str_number,
            'gender' => $request->gender,
            'description' => $request->description,
            'school' => $request->school,
            'experience' => $request->experience,
        ]);

        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-dokter'), $imageName);

            // Hapus gambar profil lama jika ada
            if ($user->profile_picture && file_exists(public_path('images-dokter/' . $user->profile_picture))) {
                unlink(public_path('images-dokter/' . $user->profile_picture));
            }

            // Simpan nama file gambar baru di database
            $user->profile_picture = $imageName;
            $user->profile_picture = url('images-dokter/' . $user->profile_picture);
            $user->save();
        }
         
        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'dokter has been registered successfully',
        ], 201);
    }

    public function updateDoctor(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone_number' => 'nullable|string',
            'ages' => 'nullable|string',
            'address' => 'nullable|string',
            'str_number' => 'nullable|int',
            'school' => 'nullable|string',
            'experience' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // Perbarui hanya jika properti diberikan dalam request
        if ($request->filled('name')) $user->name = $request->name;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('phone_number')) $user->phone_number = $request->phone_number;
        if ($request->filled('ages')) $user->ages = $request->ages;
        if ($request->filled('address')) $user->address = $request->address;
        if ($request->filled('str_number')) $user->str_number = $request->str_number;
        if ($request->filled('school')) $user->school = $request->school;
        if ($request->filled('experience')) $user->experience = $request->experience;

        $user->save();

        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-dokter'), $imageName);

            // Hapus gambar profil lama jika ada
            if ($user->profile_picture && file_exists(public_path('images-dokter/' . basename($user->profile_picture)))) {
                unlink(public_path('images-dokter/' . basename($user->profile_picture)));
            }

            // Simpan nama file gambar baru di database
            $user->profile_picture = url('images-dokter/' . $imageName);
            $user->save();
        }

        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'dokter has been updated successfully',
        ], 200);
    }


    public function showDetailDoctor($id)
    {
        $user = User::find($id);

        if (!$user) {
            
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'Detail dokter',
        ], 200);
    }

    public function allDoctor()
    {
        $users = User::where('role', 'dokter')->get();

        return response()->json($users);
    }
    
    public function getTotalDoctors()
    {
        $totalDoctors = User::countDoctors();

        return response()->json([
            'success' => true,
            'total_doctors' => $totalDoctors,
        ]);
    }

    public function updateactiveDoctor(Request $request, $id)
    {
        $user = User::find($id);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            'data' => $user,
            'status' => 'success',
            'message' => 'dokter has been updated successfully',
        ], 200);
    }

    public function allUser()
    {
        $users = User::where('role', 'user')->get();

        return response()->json($users);
    }
}
