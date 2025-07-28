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
    ];

    /**
     * Get the user (admin) who sent the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}