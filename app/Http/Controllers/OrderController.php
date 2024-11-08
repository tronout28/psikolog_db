<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function checkoutBooks(Request $request)
    {
        // Pastikan pengguna sudah terautentikasi
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Validasi input
        $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'name' => 'required|string',
            'detailed_address' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        // Buat pesanan baru di database
        $order = Order::create([
            'buku_id' => $request->buku_id,
            'user_id' => $user->id,
            'name' => $request->name,
            'detailed_address' => $request->detailed_address,
            'phone_number' => $request->phone_number,
            'status' => 'unpaid',
        ]);

        // Set konfigurasi Midtrans
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = false;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        // Cek apakah buku terkait ditemukan
        if (!$order->buku) {
            return response()->json([
                'success' => false,
                'message' => 'Buku not found for this order',
            ], 404);
        }

        // Parameter untuk transaksi
        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $order->buku->price,
            ],
            'customer_details' => [
                'name' => $request->name,
                'email' => $user->email,
                'phone' => $request->phone_number,
                'address' => $request->detailed_address,
            ],
        ];

        // Mendapatkan Snap Token dari Midtrans
        $snapToken = \Midtrans\Snap::getSnapToken($params);

        // Membuat URL Snap yang bisa langsung digunakan oleh frontend
        $snapUrl = "https://app.sandbox.midtrans.com/snap/v1/transactions/{$snapToken}";

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
            'snap_token' => $snapToken,
            'snap_url' => $snapUrl,
        ], 201);
    }


    public function callback(Request $request)
    {
        $serverkey = config('midtrans.server_key');

        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount. $serverkey);  
         
        if ($hashed == $request->signature_key) {
            if ($request->transaction_status == 'capture') {
                $order = Order::find($request->order_id);
                $order->update(['status' => 'paid']);
            }
        }
    }

    public function invoice($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

}
