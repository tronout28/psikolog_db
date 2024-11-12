<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketTransaction extends Model
{
    protected $table = 'paket_transactions';

    protected $fillable = [
        'user_id',
        'paket_id',
        'status',
        'expiry_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paket()
    {
        return $this->belongsTo(Paket::class);
    }


}
