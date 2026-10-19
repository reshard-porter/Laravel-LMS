<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = ['classroom_id', 'title', 'category', 'max_points', 'due_date', 'description'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'max_points' => 'float',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return config("lms.categories.{$this->category}.label", ucfirst($this->category));
    }

    public function isPastDue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public static function categoryOptions(): array
    {
        return collect(config('lms.categories'))->map(fn ($c) => $c['label'])->all();
    }
}
