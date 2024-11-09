<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'vouchers';

    protected $fillable = [
        'code',
        'discount_amount',
        'discount_percentage',
        'expiry_date',
        'is_active',
    ];
        
}

