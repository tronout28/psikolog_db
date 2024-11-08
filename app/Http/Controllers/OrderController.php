<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Buku;

class OrderController extends Controller
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = false;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    public function checkoutBooks(Request $request)
    {
        // Autentikasi pengguna
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

        // Cek apakah buku terkait ditemukan
        $buku = Buku::find($request->buku_id);
        if (!$buku) {
            return response()->json([
                'success' => false,
                'message' => 'Buku not found for this order',
            ], 404);
        }

        // Parameter untuk transaksi
        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $buku->price, // Harga buku sebagai jumlah transaksi
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

        // Membuat URL Snap untuk tampilan
        $snapViewUrl = route('snap.view', ['orderId' => $order->id]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
            'snap_token' => $snapToken,
            'snap_view_url' => $snapViewUrl, // URL untuk tampilan Snap View
        ], 201);
    }

    public function snapView($orderId)
    {
        // Cari pesanan berdasarkan ID
        $order = Order::find($orderId);
        if (!$order) {
            abort(404, "Order not found");
        }

        // Parameter transaksi
        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $order->buku->price,
            ],
            'customer_details' => [
                'name' => $order->name,
                'email' => $order->user->email,
                'phone' => $order->phone_number,
                'address' => $order->detailed_address,
            ],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        // Menampilkan Snap View dengan Snap Token
        return view('snap_view', [
            'snapToken' => $snapToken,
            'order_id' => $order->id,
        ]);
    }

    public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // Verifikasi callback Midtrans
        if ($hashed == $request->signature_key) {
            $order = Order::find($request->order_id);
            if ($request->transaction_status == 'capture' && $order) {
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
