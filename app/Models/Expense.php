<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
  protected $fillable = [
    'loan_manager_id', 'expense_date', 'description', 'amount',
];
}
