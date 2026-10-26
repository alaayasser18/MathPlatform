<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question_text',
        'sort_order',
        'points',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'points' => 'integer',
    ];

    protected $appends = [
        'question',
        'correctIndex',
    ];

    public function getQuestionAttribute(): string
    {
        return $this->question_text ?? '';
    }

    public function getCorrectIndexAttribute(): int
    {
        $options = $this->relationLoaded('options') ? $this->options : $this->options()->get();

        $index = 0;
        foreach ($options as $option) {
            if ($option->is_correct) {
                return $index;
            }
            $index++;
        }

        return 0;
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}