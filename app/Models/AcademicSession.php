<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'starts_at', 'ends_at', 'active'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'active' => 'boolean'];
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }
}
