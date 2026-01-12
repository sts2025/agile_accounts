<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Make sure this is included

class Client extends Model
{
    use HasFactory, SoftDeletes; // <-- Make sure SoftDeletes is used

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_manager_id',
        'name',
        'phone_number',
        'address',
        'business_occupation'
    ];
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
