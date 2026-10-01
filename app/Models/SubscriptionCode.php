<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',

        'expires_at',

        'is_used',

        'used_by_student_id',

        'used_at',

        'approval_status',

        'approved_at',
    ];

    protected $casts = [
        'expires_at' => 'date',

        'is_used' => 'boolean',

        'used_at' => 'datetime',

        'approved_at' => 'datetime',
    ];

    public function usedByStudent()
    {
        return $this->belongsTo(
            Student::class,
            'used_by_student_id'
        );
    }

    public function subscription()
    {
        return $this->hasOne(
            Subscription::class,
            'subscription_code_id'
        );
    }
}