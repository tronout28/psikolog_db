<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\User;

class RatingController extends Controller
{
        public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',   // The user receiving the rating
            'rating' => 'required|numeric|min:0.5|max:5' // Assuming a rating scale of 1-5
        ]);

        // Save the rating
        $rating = Rating::create([
            'user_id' => $request->user_id,
            'rating' => $request->rating,
        ]);

        // Update the average rating for the user
        $this->updateUserRating($request->user_id);

        // Fetch the user details
        $user = User::find($request->user_id);

        // Return the response with user details
        return response()->json([
            'message' => 'Rating submitted successfully!',
            'rating' => $rating, // the saved rating
            'user' => $user      // the user details
        ]);
    }


    protected function updateUserRating($userId)
    {
        // Calculate the average rating for the user
        $averageRating = Rating::where('user_id', $userId)->avg('rating');
    
        // Update the user's average rating in the users table
        User::where('id', $userId)->update(['rating' => $averageRating]);
    
        // Fetch the user object to use after the update
        $user = User::find($userId);
    
        // Assign the formatted rating to the user object
        $user->rating = $averageRating ? number_format($averageRating, 1) : 0;
    
        // Save the updated user object if needed
        $user->save();
    }
}
