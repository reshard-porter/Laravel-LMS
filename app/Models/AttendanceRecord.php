<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = ['classroom_id', 'student_id', 'date', 'status'];

    // `date` is kept as a plain 'Y-m-d' string (no date cast) so lookups by day match exactly in every database.

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public static function statusOptions(): array
    {
        return collect(config('lms.attendance_statuses'))->map(fn ($s) => $s['label'])->all();
    }
}
