<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
// use Twilio\Rest\Client;
use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Mail;
use App\Services\FirebaseService;

class OtpController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendOtp(Request $request)
    {
        $user = $request->user ?: auth()->user();
        $phone = '+62' . substr($user->phone_number, 1);
        $otp = rand(1000, 9999);
        $existingOtp = Otp::where('user_id', $user->id)->first();
        $expiredOtps = Otp::where('created_at', '<=', Carbon::now()->subMinutes(5))->delete();
    
        if ($existingOtp != null) {
            return response([
                'status' => 'failed',
                'message' => 'Try Again After 5 Minutes',
            ], 200);
        }

        Otp::create([
            'otp' => $otp,
            'user_id' => $user->id,
        ]);

        if($user->notification_token) {
            $this->firebaseService->sendNotification($user->notification_token, 'OTP Verification', 'Your OTP is ' . $otp, '');
        }

        return response([
            'status' => 'success',
            'message' => 'OTP sent successfully',
            'otp' => $otp, // Menampilkan OTP di respons
        ], 200);
    }
    
    public function sendOtpwithPhoneNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);
        
        $user = User::where('phone_number', $request->phone_number)->first();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor Hp belum terdaftar',
            ], 404);
        }
       
        $phone = '+62' . substr($user->phone_number, 1);
        $otp = rand(1000, 9999);
        $existingOtp = Otp::where('user_id', $user->id)->first();
        $expiredOtps = Otp::where('created_at', '<=', Carbon::now()->subMinutes(5))->delete();
    
        if ($existingOtp != null) {
            return response([
                'status' => 'failed',
                'message' => 'Try Again After 5 Minutes',
            ], 200);
        }

        Otp::create([
            'otp' => $otp,
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('psikolog')->plainTextToken;

        return response([
            'status' => 'success',
            'message' => 'OTP sent successfully',
            'otp' => $otp, // Menampilkan OTP di respons
            'token' => $token,
        ], 200);
    }

    
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|min:4|max:4',
        ]);
        $user = $request->user ?: User::where('id', auth()->user()->id)->first();
        $otp = $request->otp;
        $existingOtp = Otp::where('user_id', $user->id)->first();

        if ($existingOtp && $otp == $existingOtp->otp) {
            $existingOtp->delete();
            $user->phone_verified_at = Carbon::now();
            $user->save();

            return response([
                'status' => 'success',
                'message' => 'OTP verified successfully',
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'OTP verification failed',
            ], 200);
        }
    }

}
