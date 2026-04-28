<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MfiTransaction extends Model
{
    use HasFactory;

    protected $table = 'mfi_transactions';

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    // Link the transaction back to the MFI Account
    public function account()
    {
        return $this->belongsTo(MfiAccount::class, 'mfi_account_id');
    }
}