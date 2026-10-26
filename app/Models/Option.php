<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'option',
        'text',
    ];

    public function getOptionAttribute(): string
    {
        return $this->option_text ?? '';
    }

    public function getTextAttribute(): string
    {
        return $this->option_text ?? '';
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}