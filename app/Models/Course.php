<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Course belongs to one grade.
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Course has many sections.
     */
    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Course has many subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Course has many subscription codes.
     */
    public function subscriptionCodes()
    {
        return $this->hasMany(SubscriptionCode::class);
    }
}