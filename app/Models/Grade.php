<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Grade has many students.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Grade has many enrollments.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Grade has many sections.
     */
    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}