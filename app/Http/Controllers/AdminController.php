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

        $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone_number' => 'nullable|string',
            'ages' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => 'nullable|string|in:dokter', // Optional role, defaults to 'user'
            'str_number' => 'nullable|int',
            'school' => 'nullable|string',
            'experience' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->ages = $request->ages;
        $user->address = $request->address;
        $user->role = $request->role ?? 'dokter';
        $user->str_number = $request->str_number;
        $user->school = $request->school;
        $user->experience = $request->experience;

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
        }

        $user->save();

        $user->profile_picture = url('images-dokter/' . $user->profile_picture);

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

}
