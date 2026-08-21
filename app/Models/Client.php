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
        'gender',
        'photo_path',
        'id_document_path',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_relationship',
        'is_blacklisted',
        'blacklist_reason',
        'blacklisted_at',
        'blacklisted_by',
        'client_type',
        'business_name',
        'business_registration_number',
        'assigned_user_id',
        'preferred_notification_channel',
    ];

    protected $casts = [
        'is_blacklisted' => 'boolean',
        'blacklisted_at' => 'datetime',
    ];

    public function blacklistedBy()
    {
        return $this->belongsTo(User::class, 'blacklisted_by');
    }

    /**
     * The staff member (owner or cashier) assigned to look after this
     * client — a "field officer" style assignment.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

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

    /**
     * MFI accounts (savings/loan/shares/fixed deposit) held by this client.
     */
    public function mfiAccounts()
    {
        return $this->hasMany(MfiAccount::class);
    }

    /**
     * Client groups (e.g. village savings circles) this client belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(ClientGroup::class, 'client_group_client')->withTimestamps();
    }
}