<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Paket extends Model
{
   protected $table = 'pakets';

    protected $fillable = [
        'user_id',
        'title',
        'price',
        'paket_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}

