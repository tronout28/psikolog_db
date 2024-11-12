<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Buku;
use App\Models\Paket;
use App\Models\PaketTransaction;
use App\Models\VoucherUsage;
use App\Models\Voucher;
use App\Models\AlamatUser;
use Carbon\Carbon;


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
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $selectedAddress = AlamatUser::where('user_id', $user->id)->where('is_selected', true)->first();
        if (!$selectedAddress) {
            return response()->json(['success' => false, 'message' => 'No address selected'], 400);
        }

        $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ]);

        $buku = Buku::find($request->buku_id);
        if (!$buku) {
            return response()->json(['success' => false, 'message' => 'Book not found for this order'], 404);
        }

        $totalPrice = $buku->price;

        $voucher = null;
        if ($request->voucher_code) {
            $voucher = Voucher::where('code', $request->voucher_code)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')->orWhere('expiry_date', '>', Carbon::now());
                })
                ->first();

            if (!$voucher) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired voucher code.'], 400);
            }

            $hasUsedVoucher = VoucherUsage::where('user_id', $user->id)->where('voucher_id', $voucher->id)->exists();
            if ($hasUsedVoucher) {
                return response()->json(['success' => false, 'message' => 'You have already used this voucher.'], 400);
            }

            if ($voucher->discount_amount) {
                $totalPrice -= min($voucher->discount_amount, $totalPrice);
            } elseif ($voucher->discount_percentage) {
                $totalPrice -= ($voucher->discount_percentage / 100) * $totalPrice;
            }

            $totalPrice = max(0, $totalPrice);
        }

        $order = Order::create([
            'buku_id' => $request->buku_id,
            'user_id' => $user->id,
            'name' => $selectedAddress->name,
            'detailed_address' => $selectedAddress->address,
            'phone_number' => $selectedAddress->phone_number,
            'postal_code' => $selectedAddress->postal_code,
            'note' => $selectedAddress->note,
            'status' => 'unpaid',
            'total_price' => $totalPrice,
            'voucher_id' => $voucher ? $voucher->id : null,
        ]);

        if ($voucher) {
            VoucherUsage::create(['user_id' => $user->id, 'voucher_id' => $voucher->id]);
        }

        $transactionOrderId = 'ORDER-' . $order->id . '-' . time();
        $params = [
            'transaction_details' => [
                'order_id' => $transactionOrderId,
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'name' => $selectedAddress->name,
                'email' => $user->email,
                'phone' => $selectedAddress->phone_number,
                'postal_code' => $selectedAddress->postal_code,
                'address' => $selectedAddress->address,
                'note' => $selectedAddress->note,
            ],
        ];

        if (!$order->snap_token) {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);
        } else {
            $snapToken = $order->snap_token;
        }

        $snapViewUrl = route('snap.view', ['orderId' => $order->id]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
            'buku_info' => [
                'buku_id' => $buku->id,
                'title' => $buku->title,
                'price' => $buku->price,
                'image' => $buku->image,
                'description' => $buku->description,
            ],
            'snap_token' => $snapToken,
            'snap_view_url' => $snapViewUrl,
        ], 201);
    }


    public function checkoutPaket(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $selectedAddress = AlamatUser::where('user_id', $user->id)
            ->where('is_selected', true)
            ->first();

        if (!$selectedAddress) {
            return response()->json([
                'success' => false,
                'message' => 'No address selected',
            ], 400);
        }

        $request->validate([
            'paket_id' => 'required|exists:pakets,id',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ]);

        // Mengambil paket dan user yang memiliki paket
        $paket = Paket::with('user')->find($request->paket_id);
        if (!$paket) {
            return response()->json([
                'success' => false,
                'message' => 'Paket not found for this order',
            ], 404);
        }

        $totalPrice = $paket->price;

        // Apply voucher if provided
        $voucher = null;
        if ($request->voucher_code) {
            $voucher = Voucher::where('code', $request->voucher_code)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', Carbon::now());
                })
                ->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired voucher code.',
                ], 400);
            }

            $hasUsedVoucher = VoucherUsage::where('user_id', $user->id)
                ->where('voucher_id', $voucher->id)
                ->exists();

            if ($hasUsedVoucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already used this voucher.',
                ], 400);
            }

            if ($voucher->discount_amount) {
                $totalPrice -= min($voucher->discount_amount, $totalPrice);
            } elseif ($voucher->discount_percentage) {
                $totalPrice -= ($voucher->discount_percentage / 100) * $totalPrice;
            }

            $totalPrice = max(0, $totalPrice);
        }

        // Create PaketTransaction and Order
        $paketTransaction = PaketTransaction::create([
            'user_id' => $user->id,
            'paket_id' => $paket->id,
            'status' => 'inactive',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'paket_id' => $paket->id,
            'paket_transaction_id' => $paketTransaction->id,
            'voucher_id' => $voucher ? $voucher->id : null,
            'name' => $selectedAddress->name,
            'detailed_address' => $selectedAddress->address,
            'postal_code' => $selectedAddress->postal_code,
            'note' => $selectedAddress->note,
            'phone_number' => $selectedAddress->phone_number,
            'status' => 'unpaid',
            'total_price' => $totalPrice,
        ]);

        if ($voucher) {
            VoucherUsage::create([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
            ]);
        }

        // Generate a unique transaction order ID
        $transactionOrderId = 'ORDER-' . $order->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $transactionOrderId, // Use unique transaction order ID
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'name' => $selectedAddress->name,
                'email' => $user->email,
                'phone' => $selectedAddress->phone_number,
                'postal_code' => $selectedAddress->postal_code,
                'note' => $selectedAddress->note,
                'address' => $selectedAddress->address,
            ],
        ];

        // Only get a new Snap token if it hasn't been generated already
        if (!$order->snap_token) {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);
        } else {
            $snapToken = $order->snap_token;
        }

        $snapViewUrl = route('snap.view', ['orderId' => $order->id]);

        return response()->json([
            'success' => true,
            'message' => 'Paket order created successfully',
            'data' => $order,
            'paket_info' => [
                'paket_id' => $paket->id,
                'title' => $paket->title,
                'price' => $paket->price,
                'paket_type' => $paket->paket_type,
                'owner' => [
                    'owner_id' => $paket->user->id,
                    'owner_name' => $paket->user->name,
                    'owner_email' => $paket->user->email,
                    'owner_age' => $paket->user->ages,
                    'owner_phone' => $paket->user->phone_number,
                    'owner_address' => $paket->user->address,
                    'owner_profile_picture' => $paket->user->profile_picture,
                    'owner_str_number' => $paket->user->str_number,
                    'owner_school' => $paket->user->school,
                    'owner_experience' => $paket->user->experience,
                ],
            ],
            'snap_token' => $snapToken,
            'snap_view_url' => $snapViewUrl,
        ], 201);
    }

    public function snapView($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            abort(404, "Order not found");
        }

        $selectedAddress = AlamatUser::where('user_id', $order->user_id)->where('is_selected', true)->first();
        if (!$selectedAddress) {
            abort(404, "No address selected for this user");
        }

        $transactionOrderId = 'ORDER-' . $order->id . '-' . time();
        $params = [
            'transaction_details' => [
                'order_id' => $transactionOrderId,
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'name' => $selectedAddress->name,
                'email' => $order->user->email,
                'phone' => $selectedAddress->phone_number,
                'address' => $selectedAddress->address,
                'postal_code' => $selectedAddress->postal_code,
                'note' => $selectedAddress->note,
            ],
        ];

        if (!$order->snap_token) {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $order->update(['snap_token' => $snapToken]);
        } else {
            $snapToken = $order->snap_token;
        }

        return view('snap_view', [
            'snapToken' => $snapToken,
            'order_id' => $order->id,
        ]);
    }

    public function callback(Request $request)
    {
        // Validate required parameters
        if (!$request->has(['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'])) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        // Midtrans server key from configuration
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // Check if signature key matches for security
        if ($hashed !== $request->signature_key) {
            return response()->json(['error' => 'Invalid signature key'], 403);
        }

        // Extract Order ID
        $orderId = str_replace('ORDER-', '', $request->order_id);
        $order = Order::find($orderId);

        if (!$order || !in_array($request->transaction_status, ['capture', 'settlement'])) {
            return response()->json(['error' => 'Order not found or transaction not captured'], 404);
        }

        // Update order status
        $order->update(['status' => 'paid']);

        if ($order->paket_id) {
            // Handle Paket logic
            $paket = Paket::find($order->paket_id);
            if (!$paket) {
                return response()->json(['error' => 'Paket not found'], 404);
            }

            // Determine expiry date
            $expiry_date = match ($paket->paket_type) {
                '3day' => Carbon::now('Asia/Jakarta')->addDays(3),
                '7day' => Carbon::now('Asia/Jakarta')->addDays(7),
                '30day' => Carbon::now('Asia/Jakarta')->addDays(30),
                'realtime' => Carbon::now('Asia/Jakarta')->addMinutes(45),
                default => null,
            };
            

            $paketTransaction = PaketTransaction::find($order->paket_transaction_id);
            if ($paketTransaction) {
                $paketTransaction->update([
                    'status' => 'active',
                    'expiry_date' => $expiry_date
                ]);
            }
        }

        return response()->json(['success' => 'Order and package updated successfully'], 200);
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

    public function histories()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $orders = Order::with(['user'])->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function getOrders()
    {
        $orders = Order::with(['user'])->orderBy('created_at', 'desc')->where('status', 'paid')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}
