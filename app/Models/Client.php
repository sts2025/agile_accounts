<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * We added national_id, date_of_birth, and email 
     * so Laravel allows them to be saved to the database.
     */
    protected $fillable = [
        'loan_manager_id',
        'name',
        'email',
        'phone_number',
        'address',
        'national_id',         // Allowed to save NIN
        'date_of_birth',       // Allowed to save DOB
        'business_occupation',
    ];

    /**
     * Get the manager that owns the client.
     */
    public function loanManager()
    {
        return $this->belongsTo(User::class, 'loan_manager_id');
    }

    /**
     * Get the loans associated with the client.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}