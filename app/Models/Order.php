<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'buku_id',
        'paket_id',
        'paket_transaction_id',
        'voucher_id',
        'voucher_usage_id',
        'name',
        'paket_type',
        'detailed_address',
        'postal_code',
        'note',
        'phone_number',
        'total_price',
        'status',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buku()
    {
        return $this->belongsTo(Buku::class);
    }

    public function paket()
    {
        return $this->belongsTo(Paket::class);
    }

    public function paketTransaction()
    {
        return $this->belongsTo(PaketTransaction::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function voucherUsage()
    {
        return $this->belongsTo(VoucherUsage::class);
    }
}
