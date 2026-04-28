<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MfiAccount extends Model
{
    use HasFactory;

    protected $table = 'mfi_accounts';

    protected $guarded = [];

    // Link the account to the client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Link the account to its transactions (deposits/withdrawals)
    public function transactions()
    {
        return $this->hasMany(MfiTransaction::class);
    }
}