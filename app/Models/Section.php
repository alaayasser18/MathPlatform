<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Section belongs to one grade.
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Section has many lessons.
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}