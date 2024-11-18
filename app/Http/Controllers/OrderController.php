<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Buku;
use App\Models\Paket;
use App\Models\PaketTransaction;
use App\Models\VoucherUsage;
use App\Services\FirebaseService;
use App\Models\Voucher;
use App\Models\AlamatUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{
    protected $firebaseService; // Add the protected property

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService; // Initialize the FirebaseService
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
            'notification_token' => $user->notification_token,
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

        // Create PaketTransaction and Order without address details
        $paketTransaction = PaketTransaction::create([
            'user_id' => $user->id,
            'paket_id' => $paket->id,
            'status' => 'inactive',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'paket_id' => $paket->id,
            'paket_transaction_id' => $paketTransaction->id,
            'name' => $user->name,
            'detailed_address' => $user->address,
            'phone_number' => $user->phone_number,
            'voucher_id' => $voucher ? $voucher->id : null,
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
                'order_id' => $transactionOrderId,
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'ages' => $user->ages, // assuming age is a field in user table
                'gender' => $user->gender, // assuming gender is a field in user table
                'status' => $user->status, // assuming status is a field in user table
            ],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $order->update(['snap_token' => $snapToken]);

        $snapViewUrl = route('snap.view', ['orderId' => $order->id]);

        return response()->json([
            'success' => true,
            'message' => 'Paket order created successfully',
            'data' => $order,
            'notification_token' => $user->notification_token,
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

        $user = User::find($order->user_id);
    
        // Check if the order is for a book or a package
        if ($order->buku_id) {
            // Order is for a book, include address details
            $selectedAddress = AlamatUser::where('user_id', $order->user_id)
                ->where('is_selected', true)
                ->first();
    
            if (!$selectedAddress) {
                abort(404, "No address selected for this user");
            }
    
            $params = [
                'transaction_details' => [
                    'order_id' => 'ORDER-' . $order->id . '-' . time(),
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
        } else {
            // Order is for a package, exclude address details
            $params = [
                'transaction_details' => [
                    'order_id' => 'ORDER-' . $order->id . '-' . time(),
                    'gross_amount' => $order->total_price,
                ],
                'customer_details' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number,
                    'ages' => $user->ages, // Assuming ages is a field in user table
                    'gender' => $user->gender, // Assuming gender is a field in user table
                    'status' => $user->status, // Assuming status is a field in user table
                ],
            ];
        }
    
        // Generate or retrieve Snap Token
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
        // Validasi parameter yang diterima dari Midtrans
        if (!$request->has(['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'])) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        // Verifikasi signature key untuk keamanan
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['error' => 'Invalid signature key'], 403);
        }

        // Ambil Order ID dengan memisahkan 'ORDER-' dan ambil ID numerik
        $orderId = explode('-', str_replace('ORDER-', '', $request->order_id))[0];
        $order = Order::with('user')->find($orderId); // Mengambil data pengguna terkait

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Menangani status 'pending' jika pembayaran belum selesai
        if ($request->transaction_status == 'pending') {
            $order->update(['status' => 'pending']);  // Tandai status sebagai pending
            return response()->json(['success' => 'Order is pending payment'], 200);
        }

        // Menangani status 'capture' atau 'settlement'
        if (in_array($request->transaction_status, ['capture', 'settlement'])) {
            // Update status pesanan ke 'paid'
            $order->update(['status' => 'paid']);

            // Jika pesanan terkait paket, update status dan tanggal kedaluwarsa PaketTransaction
            if ($order->paket_id) {
                $paket = Paket::find($order->paket_id);
                if (!$paket) {
                    return response()->json(['error' => 'Paket not found'], 404);
                }

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

            // Kirim notifikasi jika pembayaran sukses
            $notificationToken = $order->user->notification_token; // Mengambil notification_token
            if ($notificationToken) {
                $this->firebaseService->sendNotification(
                    $notificationToken,
                    'Selamat '.$order->name.' telah berhasil melakukan pembayaran',
                    'Anda telah membayar pesanan Anda sebesar ' . $order->total_price . ' 🎉',
                    ''
                );
            }

            return response()->json(['success' => 'Order and package updated successfully'], 200);
        }

        return response()->json(['error' => 'Transaction status not supported'], 400);
    }

    public function invoiceView($id)
    {
        $order = Order::find($id);
        if (!$order) {
            abort(404, "Order not found");
        }

        $user = User::find($order->user_id);
        if (!$user) {
            abort(404, "User not found");
        }

        $buku = null;
        $paket = null;

        // Tentukan apakah pesanan untuk buku atau paket
        if ($order->buku_id) {
            $buku = Buku::find($order->buku_id);
            if (!$buku) {
                abort(404, "Book not found for this order");
            }
        } elseif ($order->paket_id) {
            $paket = Paket::find($order->paket_id);
            if (!$paket) {
                abort(404, "Package not found for this order");
            }
        }

        // Render view invoice dengan data yang sesuai
        return view('invoice_view', [
            'order' => $order,
            'user' => $user,
            'buku' => $buku,
            'paket' => $paket,
        ]);
    }


    public function histories(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $type = $request->query('type'); // 'buku', 'paket', or null

        $validTypes = ['buku', 'paket'];
        if ($type && !in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type parameter. Allowed values: buku, paket',
            ], 400);
        }

        $query = Order::with(['user']);

        if ($type === 'buku') {
            $query->whereNotNull('buku_id');
        } elseif ($type === 'paket') {
            $query->whereNotNull('paket_id')
                ->with('paketTransaction'); // Include expiry_date from paket_transaction
        }

        $orders = $query->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No orders found for the specified type',
                'data' => [],
            ]);
        }

        $formattedOrders = $orders->map(function ($order) use ($type) {
            $data = [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'buku_id' => $order->buku_id,
                'paket_id' => $order->paket_id,
                'total_price' => $order->total_price,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'expiry_date' =>  $order->paketTransaction->expiry_date ?? null,
            ];

            if ($type === 'paket' && $order->paketTransaction) {
                $data['expiry_date'] = $order->paketTransaction->expiry_date;
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'message' => 'Order histories retrieved successfully',
            'data' => $formattedOrders,
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

    public function getMonthlyRevenue()
    {
        // Query untuk mendapatkan total pendapatan dari pesanan yang dibayar, dikelompokkan berdasarkan bulan
        $monthlyRevenue = Order::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_price) as total_revenue')
        )
        ->where('status', 'paid') // Hanya menyertakan pesanan yang statusnya dibayar
        ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();

        // Dapatkan tahun dari pesanan pertama dan terakhir
        $firstOrder = Order::where('status', 'paid')->orderBy('created_at')->first();
        $lastOrder = Order::where('status', 'paid')->orderBy('created_at', 'desc')->first();

        if (!$firstOrder || !$lastOrder) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Definisikan awal dan akhir dari rentang tahun (misal: Januari - Desember untuk setiap tahun)
        $start = Carbon::parse($firstOrder->created_at)->startOfYear(); // Mulai dari Januari tahun pertama
        $end = Carbon::parse($lastOrder->created_at)->endOfYear();     // Sampai Desember tahun terakhir

        // Buat daftar semua bulan dari Januari hingga Desember dalam rentang tahun
        $allMonths = [];
        while ($start <= $end) {
            $allMonths[] = [
                'year' => $start->year,
                'month' => $start->month,
                'month_name' => $start->format('F'),
                'total_revenue' => 0, // Default pendapatan ke 0
            ];
            $start->addMonth();
        }

        // Peta data pendapatan yang ada ke dalam array allMonths
        $monthlyRevenue->each(function ($revenue) use (&$allMonths) {
            foreach ($allMonths as &$month) {
                if ($month['year'] == $revenue->year && $month['month'] == $revenue->month) {
                    $month['total_revenue'] = $revenue->total_revenue;
                    break;
                }
            }
        });

        return response()->json([
            'success' => true,
            'data' => $allMonths,
        ]);
    }



    public function getTotalPurchasedPaket()
    {
        // Menghitung total paket yang sudah terbeli (dengan status 'paid')
        $totalPaket = Order::whereNotNull('paket_id')
            ->where('status', 'paid')
            ->count();

        return response()->json([
            'success' => true,
            'total_paket_purchased' => $totalPaket,
        ]);
    }

    public function getTotalPurchasedBooks()
    {
        // Menghitung total buku yang sudah terbeli (dengan status 'paid')
        $totalBuku = Order::whereNotNull('buku_id')
            ->where('status', 'paid')
            ->count();

        return response()->json([
            'success' => true,
            'total_books_purchased' => $totalBuku,
        ]);
    }
}
