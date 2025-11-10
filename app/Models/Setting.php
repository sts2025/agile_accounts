<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_symbol',
        'support_phone',
    ];

    // Since there should only be one row, this is a helpful utility
    public static function getCurrency()
    {
        return self::first()->currency_symbol ?? 'UGX';
    }

    public static function getSupportPhone()
    {
        return self::first()->support_phone ?? 'N/A';
    }
}
