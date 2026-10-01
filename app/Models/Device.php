<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'device_identifier',
        'device_name',
        'platform',
        'browser',
        'operating_system',
        'user_agent',
        'ip_address',
        'registered_at',
        'last_seen_at',
        'is_trusted',
        'deactivated_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_trusted' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    protected $hidden = [
        'user_agent',
        'ip_address',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isTrusted(): bool
    {
        return $this->is_trusted === true
            && $this->deactivated_at === null;
    }
}