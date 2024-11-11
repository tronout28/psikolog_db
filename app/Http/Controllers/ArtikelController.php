<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artikel;

class ArtikelController extends Controller
{
    public function index()
    {
        $artikel = Artikel::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $artikel
        ]);
    }

    public function show($id)
    {
        $artikel = Artikel::find($id);

        if (!$artikel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Artikel not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $artikel
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'subtitle' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
            'url' => 'nullable',
        ]);

        $artikel = Artikel::create([
            'title' => $request->title,
            'content' => $request->content,
            'subtitle' => $request->subtitle,
            'url' => $request->url,
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-artikel'), $imageName);

            if ($artikel->image && file_exists(public_path('images-artikel/' . $artikel->image))) {
                unlink(public_path('images-artikel/' . $artikel->image));
            }

            $artikel->image = $imageName;
            $artikel->image = url('images-artikel/' . $artikel->image);

            $artikel->save();
        }

        return response()->json([
            'status' => 'success',
            'data' => $artikel
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
            'url' => 'nullable',
        ]);

        $artikel = Artikel::find($id);

        if (!$artikel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Artikel not found'
            ], 404);
        }

        $artikel->update($request->all());

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-artikel'), $imageName);

            if ($artikel->image && file_exists(public_path('images-artikel/' . $artikel->image))) {
                unlink(public_path('images-artikel/' . $artikel->image));
            }

            $artikel->image = $imageName;
            $artikel->image = url('images-artikel/' . $artikel->image);

            $artikel->save();
        }

        return response()->json([
            'status' => 'success',
            'data' => $artikel
        ]);
    }

    public function destroy($id)
    {
        $artikel = Artikel::find($id);

        if (!$artikel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Artikel not found'
            ], 404);
        }

        $artikel->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Artikel deleted'
        ]);
    }
}
