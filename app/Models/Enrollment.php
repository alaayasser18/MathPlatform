<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'grade_id',
        'academic_year',
    ];

    /**
     * Enrollment belongs to one student.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Enrollment belongs to one grade.
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}