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

            // Only try to delete if the banner has a previous image
            if ($banner->image && file_exists(public_path('images-banner/' . $banner->image))) {
                unlink(public_path('images-banner/' . $banner->image));
            }

            // Save only the image file name in the database
            $banner->image = $imageName;
            $banner->image = url('images-banner/' . $banner->image);
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

   public function deleteBanner($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'message' => 'Banner not found!',
            ], 404);
        }

        // Only try to delete if the banner has an image
        if ($banner->image && file_exists(public_path('images-banner/' . $banner->image))) {
            unlink(public_path('images-banner/' . $banner->image));
        }

        $banner->delete();

        return response()->json([
            'message' => 'Banner deleted!',
        ]);
    }

    public function detailBanner($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'message' => 'Banner not found!',
            ], 404);
        }

        return response()->json([
            'message' => 'Banner found!',
            'data' => $banner
        ]);
    }
}

