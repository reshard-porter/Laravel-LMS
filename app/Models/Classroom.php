<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'name', 'code', 'subject', 'teacher_id', 'room', 'schedule', 'term', 'capacity', 'description', 'weights',
    ];

    protected function casts(): array
    {
        return [
            'weights' => 'array',
            'capacity' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot('enrolled_on')->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** Category weights for this class: its own override, or the school defaults. */
    public function categoryWeights(): array
    {
        $defaults = collect(config('lms.categories'))->map(fn ($c) => $c['weight'])->all();

        if (! is_array($this->weights)) {
            return $defaults;
        }

        return collect($defaults)
            ->map(fn ($default, $key) => (float) ($this->weights[$key] ?? 0))
            ->all();
    }

    public function usesCustomWeights(): bool
    {
        return is_array($this->weights);
    }

    public function isFull(): bool
    {
        return $this->students()->count() >= $this->capacity;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn ($q) => $q
            ->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->orWhere('subject', 'like', $like)
            ->orWhere('room', 'like', $like));
    }
}
