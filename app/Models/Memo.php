<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Memo extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'title',
        'description',
        'file_url',
        'file_size',
        'pages',
        'year',
        'cover_image_url',
        'features',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'grade_id' => 'integer',
        'pages' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    protected $appends = [
        'cover',
        'fileSize',
        'pdfFile',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors for Frontend Compatibility
    |--------------------------------------------------------------------------
    */

    public function getCoverAttribute(): string
    {
        return ! empty($this->attributes['cover_image_url']) ? $this->attributes['cover_image_url'] : '/logo.png';
    }

    public function getFileSizeAttribute(): ?string
    {
        return $this->attributes['file_size'] ?? null;
    }

    public function getPdfFileAttribute(): ?string
    {
        return $this->attributes['file_url'] ?? null;
    }
}
