<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'video_id',
        'subscription_id',
        'current_position_seconds',
        'watched_duration_seconds',
        'completion_percentage',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'current_position_seconds' => 'integer',
        'watched_duration_seconds' => 'integer',
        'completion_percentage' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}