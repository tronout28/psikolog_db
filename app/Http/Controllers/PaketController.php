<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paket;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\PaketTransaction;

class PaketController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'price' => 'required|numeric',
            'paket_type' => ['required', Rule::in(['3day', '7day', '30day', 'realtime'])],
        ]);

        $paket = Paket::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'price' => $request->price,
            'paket_type' => $request->paket_type,
        ]);

        return response()->json([
            'message' => 'Paket created!',
            'data' => $paket
        ]);
    }

    public function index()
    {
        $pakets = Paket::with('user')->get();

        return response()->json([
            'message' => 'List paket',
            'data' => $pakets
        ]);
    }

    public function BuyPaket(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'paket_id' => 'required|exists:pakets,id',
        ]);

        $user_id = $request->user_id;
        $paket = Paket::find($request->paket_id);

        // Determine the expiry date based on paket type
        $expiry_date = null;
        if ($paket->paket_type === '3day') {
            $expiry_date = Carbon::now()->addDays(3);
        } elseif ($paket->paket_type === '7day') {
            $expiry_date = Carbon::now()->addDays(7);
        } elseif ($paket->paket_type === '30day') {
            $expiry_date = Carbon::now()->addDays(30);
        } elseif ($paket->paket_type === 'realtime') {
            $expiry_date = Carbon::now()->addMinutes(45); // 45 minutes for 'realtime' paket
        }

        // Create the paket transaction
        $paketTransaction = PaketTransaction::create([
            'user_id' => $user_id,
            'paket_id' => $paket->id,
            'status' => 'active',
            'expiry_date' => $expiry_date,
        ]);

        return response()->json([
            'message' => 'Paket purchased successfully!',
            'data' => $paketTransaction,
        ]);
    }

    public function showpaketuser($id)
    {
        $pakets = Paket::with('user')->where('user_id', $id)->get();

        return response()->json([
            'message' => 'List paket user',
            'data' => $pakets
        ]);
    }

    public function getTotalActivePaket()
    {
        $totalActivePaket = PaketTransaction::countActivePaket();

        return response()->json([
            'success' => true,
            'total_active_paket' => $totalActivePaket,
        ]);
    }

    public function filterbytype(Request $request)
    {
        // Ambil parameter 'type' dari query string
        $type = $request->query('type');
    
        // Validasi apakah parameter 'type' ada dan valid
        $validTypes = ['3day', '7day', '30day', 'realtime'];
        if (!$type || !in_array($type, $validTypes)) {
            return response()->json([
                'message' => 'Invalid paket type or missing type parameter',
                'data' => [],
                'status' => 'error'
            ], 400);
        }
    
        // Fetch pakets berdasarkan type
        $pakets = Paket::with('user')->where('paket_type', $type)->get();
    
        // Periksa jika hasil kosong
        if ($pakets->isEmpty()) {
            return response()->json([
                'message' => 'No pakets found for the specified type',
                'data' => [],
                'status' => 'success'
            ], 200);
        }
    
        return response()->json([
            'message' => 'List of pakets by type',
            'data' => $pakets,
            'status' => 'success'
        ], 200);
    }    

}