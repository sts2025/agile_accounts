<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * We need this for the seeder to work.
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
    ];
    public function generalLedgerTransactions()
    {
        return $this->hasMany(GeneralLedgerTransaction::class);
    }
}