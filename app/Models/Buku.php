<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    protected $table = 'bukus';
    
    protected $fillable = [
        'title',
        'image',
        'description',
        'price',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];
}
