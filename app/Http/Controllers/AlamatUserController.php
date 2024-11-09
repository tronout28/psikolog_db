<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AlamatUser;

class AlamatUserController extends Controller
{
    public function index()
    {
        $user = auth()->user()->id;

        $alamat = AlamatUser::where('user_id', $user)->orderBy('is_selected', 'desc')->orderBy('created_at', 'asc')->get(); 

        return response()->json([
            'status' => 'success',
            'data' => $alamat
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'postal_code' => 'required|string|min:5|max:5',
            'note' => 'nullable|string',
        ]);

        $user = auth()->user();

        $alamat = AlamatUser::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'postal_code' => $request->postal_code,
            'note' => $request->note,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $alamat
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
    
        $alamat = AlamatUser::find($id);
    
        if ($alamat == null) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Alamat not found'
            ]);
        }
    
        // Filter out null values
        $data = array_filter($request->all(), function ($value) {
            return !is_null($value);
        });
    
        // Update only non-null fields
        $alamat->update($data);
    
        return response()->json([
            'status' => 'success',
            'data' => $alamat
        ]);
    }
    

    public function destroy($id)
    {
        $alamat = AlamatUser::find($id);

        if ($alamat == null) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Alamat not found'
            ]);
        }

        $alamat->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat deleted successfully'
        ]);
    }

    public function showdetailalamat($id)
    {
        $alamat = AlamatUser::find($id);

        if ($alamat == null) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Alamat not found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $alamat
        ]);
    }

    public function selectAlamat($id)
    {
        $user = auth()->user();
    
        // Set all other addresses to false
        AlamatUser::where('user_id', $user->id)
            ->where('id', '!=', $id)
            ->update(['is_selected' => false]);
    
        // Find the selected address
        $alamat = AlamatUser::findOrFail($id);
        $alamat->is_selected = true; // Set is_selected to true
        $alamat->save(); // Save the change
    
        return response()->json([
            'status' => 'success',
            'data' => $alamat->fresh() // Reload the latest data for response
        ]);
    }    
}
