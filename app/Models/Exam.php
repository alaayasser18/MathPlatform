<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'lesson_id',
        'title',
        'description',
        'duration_minutes',
        'passing_percentage',
        'is_active',
    ];

    protected $casts = [
        'grade_id' => 'integer',
        'duration_minutes' => 'integer',
        'passing_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'questions_count',
    ];

    public function getQuestionsCountAttribute(): int
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return (int) $this->attributes['questions_count'];
        }

        return $this->relationLoaded('questions')
            ? $this->questions->count()
            : $this->questions()->count();
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
