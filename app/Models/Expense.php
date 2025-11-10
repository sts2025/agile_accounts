<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_manager_id',
        'expense_category_id', // <-- MUST BE FILLABLE
        'amount',
        'expense_date',
        // 'description', // Add any other fields you have
        // 'title', // Add any other fields you have
    ];

    /**
     * Get the category that this expense belongs to.
     * * *** THIS IS THE MISSING FIX ***
     * This function allows you to call $expense->category->name in your view.
     */
    public function category(): BelongsTo
    {
        // This links this model to the ExpenseCategory model
        // using the 'expense_category_id' column.
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
