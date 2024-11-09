<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherController extends Controller
{
      /**
     * Generate kode voucher acak yang menyerupai kata.
     */
    private function generateVoucherCode($length = 8)
    {
        $vowels = ['A', 'E', 'I', 'O', 'U'];
        $consonants = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z'];
        
        $voucherCode = '';
        
        for ($i = 0; $i < $length / 2; $i++) {
            $voucherCode .= $consonants[array_rand($consonants)];
            $voucherCode .= $vowels[array_rand($vowels)];
        }
        
        $voucherCode .= rand(10, 99);

        return strtoupper(substr($voucherCode, 0, $length));
    }

    /**
     * Menambahkan voucher baru dengan kode custom atau acak.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|unique:vouchers,code',
            'discount_amount' => 'nullable|integer|min:0|exclude_unless:discount_percentage,null',
            'discount_percentage' => 'nullable|integer|min:0|max:100|exclude_unless:discount_amount,null',
            'expiry_date' => 'nullable|date|after:today',
        ], [
            'discount_amount.exclude_unless' => 'You can only set one discount type: either amount or percentage.',
            'discount_percentage.exclude_unless' => 'You can only set one discount type: either amount or percentage.'
        ]);

        // Jika code tidak disediakan, generate kode voucher acak
        $code = $request->code ?? $this->generateVoucherCode(8);

        // Buat voucher baru
        $voucher = Voucher::create([
            'code' => $code,
            'discount_amount' => $request->discount_amount,
            'discount_percentage' => $request->discount_percentage,
            'expiry_date' => $request->expiry_date,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Voucher created successfully', 'voucher' => $voucher], 201);
    }

    /**
     * Validasi voucher.
     */
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        if (!$voucher->is_active) {
            return response()->json(['message' => 'Voucher is inactive'], 400);
        }

        if ($voucher->expiry_date && Carbon::now()->gt($voucher->expiry_date)) {
            return response()->json(['message' => 'Voucher has expired'], 400);
        }

        return response()->json(['message' => 'Voucher is valid', 'voucher' => $voucher], 200);
    }

    /**
     * Menggunakan voucher untuk mendapatkan diskon.
     */
    public function applyVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher || !$voucher->is_active || ($voucher->expiry_date && Carbon::now()->gt($voucher->expiry_date))) {
            return response()->json(['message' => 'Voucher is invalid'], 400);
        }

        $discount = 0;

        if ($voucher->discount_amount) {
            $discount = min($voucher->discount_amount, $request->amount);
        } elseif ($voucher->discount_percentage) {
            $discount = ($voucher->discount_percentage / 100) * $request->amount;
        }

        $finalAmount = $request->amount - $discount;

        return response()->json([
            'message' => 'Voucher applied successfully',
            'original_amount' => $request->amount,
            'discount' => $discount,
            'final_amount' => $finalAmount,
        ], 200);
    }

    /**
     * Menghapus voucher berdasarkan ID.
     */
    public function destroy($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher deleted successfully'], 200);
    }
}

