<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'is_active', // *** ADDED: New field for toggling ***
    ];

    /**
     * Get the user (admin) who sent the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active messages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true); // *** ADDED: Scope for fetching active message ***
    }
}