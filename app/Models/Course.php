<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['department_id', 'code', 'title', 'credit_units', 'active'];

    protected function casts(): array
    {
        return ['credit_units' => 'integer', 'active' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_lecturers', 'course_id', 'lecturer_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }
}