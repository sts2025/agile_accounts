<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * We should add 'name' here for good practice.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        // 'description', // Add this if you have a description column
    ];

    /**
     * Get all of the expenses for this category.
     * This defines the other side of the relationship.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }
}

