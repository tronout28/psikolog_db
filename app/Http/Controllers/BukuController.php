<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;

class BukuController extends Controller
{
    public function insertBook(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
            'description' => 'required|string',
            'price' => 'required|numeric',
        ]);
        
        $buku = Buku::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => '',
            'price' => $request->price,
        ]);

         if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-book'), $imageName);

            // Only try to delete if the buku has a previous image
            if ($buku->image && file_exists(public_path('images-book/' . $buku->image))) {
                unlink(public_path('images-book/' . $buku->image));
            }

            // Save only the image file name in the database
            $buku->image = $imageName;
            $buku->image = url('images-book/' . $buku->image);
            $buku->save();
        }
        
        return response()->json([
            'data' => $buku,
            'status' => 'success',
            'message' => 'Buku has been inserted successfully',
        ], 201);
    }
    

    public function index ()
    {
        $buku = Buku::all();


        return response()->json([
            'success' => true,
            'message' => 'List Buku',
            'data' => $buku,
        ], 200);
    }

    public function show($id)
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json([
                'success' => false,
                'message' => 'Buku not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Buku',
            'data' => $buku,
        ], 200);
    }

    public function updateBook(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string',
            'image' => 'nullable|image',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json([
                'success' => false,
                'message' => 'Buku not found',
            ], 404);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-book'), $imageName);

            if ($buku->image && file_exists(public_path('images-book/' . $buku->image))) {
                unlink(public_path('images-book/' . $buku->image));
            }
            $buku->image = $imageName;
            $buku->image = url('images-book/' . $buku->image);
            $buku->save();
        }

        $buku->title = $request->title ?? $buku->title;
        $buku->description = $request->description ?? $buku->description;
        $buku->price = $request->price ?? $buku->price;

        $buku->save();


        return response()->json([
            'data' => $buku,
            'status' => 'success',
            'message' => 'Buku has been updated successfully',
        ], 200);
    }

    public function updateisavaible(Request $request, $id)
    {
        $request->validate([
            'is_available' => 'required|boolean',
        ]);

        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json([
                'success' => false,
                'message' => 'Buku not found',
            ], 404);
        }

        $buku->is_available = $request->is_available;
        $buku->save();


        return response()->json([
            'data' => $buku,
            'status' => 'success',
            'message' => 'Buku is avaible status has been updated successfully',
        ], 200);
    }
    
    
}
