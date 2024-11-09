<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlamatUser extends Model
{
    protected $table = 'alamat_users';

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'address',
        'postal_code',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'alamat_users_id');
    }
}
