<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDeposit extends Model
{
   protected $fillable = [
    'loan_manager_id', 'deposit_date', 'amount', 'reference_number',
];
}
