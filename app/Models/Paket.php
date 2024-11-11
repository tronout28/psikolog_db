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

    // Valid paket types
    public static $validPaketTypes = ['3day', '7day', '30day', 'realtime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mutator to validate paket_type before saving
    public function setPaketTypeAttribute($value)
    {
        if (!in_array($value, self::$validPaketTypes)) {
            throw new \InvalidArgumentException("Invalid paket_type value: {$value}");
        }

        $this->attributes['paket_type'] = $value;
    }

    // Accessor for formatted expiry date (Optional)
    public function getFormattedExpiryDate()
    {
        switch ($this->paket_type) {
            case '3day':
                return Carbon::now()->addDays(3);
            case '7day':
                return Carbon::now()->addDays(7);
            case '30day':
                return Carbon::now()->addDays(30);
            case 'realtime':
                return Carbon::now()->addMinutes(45);
            default:
                return null;
        }
    }
    
}

