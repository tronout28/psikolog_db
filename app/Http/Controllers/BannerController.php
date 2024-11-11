<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banner;
use GuzzleHttp\Promise\Create;

class BannerController extends Controller
{
    public function inputBanner(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
            'url' => 'nullable',
        ]);

        $banner = Banner::create([
            'title' => $request->title,
            'url' => $request->url,
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images-banner'), $imageName);

            if ($banner->image && file_exists(public_path('images-banner/' . $banner->image))) {
                unlink(public_path('images-banner/' . $banner->image));
            }

            $banner->image = $imageName;
            $banner->save();
        }

        return response()->json([
            'message' => 'Banner created!',
            'data' => $banner
        ]);
    }

    public function index()
    {
        $banners = Banner::all();

        return response()->json([
            'message' => 'List banner',
            'data' => $banners
        ]);
    }

    public function deleteBanner(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:banners,id',
        ]);

        $banner = Banner::find($request->id);
        $banner->delete();

        return response()->json([
            'message' => 'Banner deleted!',
        ]);
    }

    public function detailBanner(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:banners,id',
        ]);

        $banner = Banner::find($request->id);

        return response()->json([
            'message' => 'Detail banner',
            'data' => $banner
        ]);
    }
}

