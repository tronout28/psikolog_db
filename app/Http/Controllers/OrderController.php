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
        // Authenticate the user
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

         // Get the selected address
        $selectedAddress = AlamatUser::where('user_id', $user->id)->where('is_selected', true)->first();
        if (!$selectedAddress) {
            return response()->json([
                'success' => false,
                'message' => 'No address selected',
            ], 400);
        }

        // Validate input
        $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ]);

        // Find the book
        $buku = Buku::find($request->buku_id);
        if (!$buku) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found for this order',
            ], 404);
        }

        // Start with the original price of the book
        $totalPrice = $buku->price;

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

            // Check if the user has already used this voucher
            $hasUsedVoucher = VoucherUsage::where('user_id', $user->id)
                ->where('voucher_id', $voucher->id)
                ->exists();

            if ($hasUsedVoucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already used this voucher.',
                ], 400);
            }

            // Apply discount based on voucher type
            if ($voucher->discount_amount) {
                $totalPrice -= min($voucher->discount_amount, $totalPrice);
            } elseif ($voucher->discount_percentage) {
                $totalPrice -= ($voucher->discount_percentage / 100) * $totalPrice;
            }

            // Ensure total price doesn't go below zero
            $totalPrice = max(0, $totalPrice);
        }

        // Create a new order in the database with the discounted total price
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

        // Record the voucher usage if a voucher was applied
        if ($voucher) {
            VoucherUsage::create([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
            ]);
        }

        // Transaction parameters with the discounted total price
        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
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

        // Get Snap Token from Midtrans
        $snapToken = \Midtrans\Snap::getSnapToken($params);


        // Create Snap URL for viewing
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
            'snap_view_url' => $snapViewUrl, // URL for Snap View display
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

            // Check if the user has already used this voucher
            $hasUsedVoucher = VoucherUsage::where('user_id', $user->id)
                ->where('voucher_id', $voucher->id)
                ->exists();

            if ($hasUsedVoucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already used this voucher.',
                ], 400);
            }

            // Apply discount based on voucher type
            if ($voucher->discount_amount) {
                $totalPrice -= min($voucher->discount_amount, $totalPrice);
            } elseif ($voucher->discount_percentage) {
                $totalPrice -= ($voucher->discount_percentage / 100) * $totalPrice;
            }

            // Ensure total price doesn't go below zero
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
            'paket_type' => $paket->paket_type,
            'status' => 'unpaid',
            'total_price' => $totalPrice,
        ]);

        // Record the voucher usage if a voucher was applied
        if ($voucher) {
            VoucherUsage::create([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
            ]);
        }

        // Ambil data user pemilik paket
        $paketOwner = $paket->user;

        $order_id = 'ORDER-' . time() . '-' . uniqid();

        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'name' => $selectedAddress->name,
                'email' => $user->email,
                'phone' => $selectedAddress->phone_number,
                'postal_code' => $selectedAddress->postal_code,
                'note' => $selectedAddress->note,
                'paket_type' => $order->paket_type,
                'address' => $selectedAddress->address,
            ],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $snapViewUrl = route('snap.view', ['orderId' => $order->id]);

        // Menyusun respons dengan paket dan pemiliknya di satu bagian
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
                    'owner_id' => $paketOwner->id,
                    'owner_name' => $paketOwner->name,
                    'owner_email' => $paketOwner->email,
                    'owner_age' => $paketOwner->ages,
                    'owner_phone' => $paketOwner->phone_number,
                    'owner_address' => $paketOwner->address,
                    'owner_profile_picture' => $paketOwner->profile_picture,
                    'owner_str_number' => $paketOwner->str_number,
                    'owner_school' => $paketOwner->school,
                    'owner_experience' => $paketOwner->experience,
                ],
            ],
            'snap_token' => $snapToken,
            'snap_view_url' => $snapViewUrl,
        ], 201);
    }



    public function snapView($orderId)
    {
        // Find the order by ID
        $order = Order::find($orderId);
        if (!$order) {
            abort(404, "Order not found");
        }

        $selectedAddress = AlamatUser::where('user_id', $order->user_id)->where('is_selected', true)->first();
        if (!$selectedAddress) {
            abort(404, "No address selected for this user");
        }
    

        // Determine if the order is for a Buku or a Paket
        $grossAmount = null;
        if ($order->buku) {
            $grossAmount = $order->buku->price; // For Buku orders
        } elseif ($order->paket) {
            $grossAmount = $order->paket->price; // For Paket orders
        }

        if (is_null($grossAmount)) {
            abort(404, "Price not found for this order");
        }

        // Transaction parameters
        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'name' => $selectedAddress->name,
                'email' => $order->user->email,
                'phone' => $selectedAddress->phone_number,
                'address' => $selectedAddress->address,
                'postal_code' => $selectedAddress->postal_code,
                'paket_type' => $order->paket_type,
                'note' => $selectedAddress->note,
            ],
        ];

         $snapToken = \Midtrans\Snap::getSnapToken($params);
        // Display Snap View with Snap Token
        return view('snap_view', [
            'snapToken' => $snapToken,
            'order_id' => $order->id,
        ]);
    }


    public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // Verify Midtrans callback
        if ($hashed == $request->signature_key) {
            $order = Order::find($request->order_id);

            if ($order && $request->transaction_status == 'capture') {
                // Check if this order is for a Paket (not a Buku)
                if ($order->paket_id) {
                    // Update the order status to 'paid'
                    $order->update(['status' => 'paid']);

                    // Retrieve the Paket associated with the order
                    $paket = Paket::find($order->paket_type);
                    $expiry_date = null;

                    // Set expiry_date based on paket_type
                    switch ($paket->paket_type) {
                        case '3day':
                            $expiry_date = Carbon::now()->addDays(3);
                            break;
                        case '7day':
                            $expiry_date = Carbon::now()->addDays(7);
                            break;
                        case '30day':
                            $expiry_date = Carbon::now()->addDays(30);
                            break;
                        case 'realtime':
                            $expiry_date = Carbon::now()->addMinutes(45);
                            break;
                    }

                    // Update the associated PaketTransaction with expiry_date
                    $paketTransaction = PaketTransaction::where('id', $order->paket_transaction_id)->first();
                    if ($paketTransaction) {
                        $paketTransaction->update([
                            'status' => 'active',
                            'expiry_date' => $expiry_date
                        ]);
                    }
                }
                // If it's a Buku order, skip changing the status or expiry date
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
