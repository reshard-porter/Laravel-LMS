<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    public const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated'];

    protected $fillable = [
        'student_number', 'first_name', 'last_name', 'email', 'date_of_birth',
        'year_level', 'guardian_name', 'guardian_phone', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'year_level' => 'integer',
        ];
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class)->withPivot('enrolled_on')->withTimestamps();
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** "Last, First" for alphabetical lists such as the gradebook. */
    public function getSortNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn ($q) => $q
            ->where('first_name', 'like', $like)
            ->orWhere('last_name', 'like', $like)
            ->orWhere('student_number', 'like', $like)
            ->orWhere('email', 'like', $like));
    }

    public static function nextStudentNumber(): string
    {
        $year = now()->year;
        $last = static::where('student_number', 'like', "S{$year}-%")->max('student_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('S%d-%04d', $year, $next);
    }
}
