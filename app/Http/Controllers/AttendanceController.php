<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function show(Request $request, Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        $date = $this->date($request->query('date'));
        $students = $classroom->students()->orderBy('last_name')->orderBy('first_name')->get();
        $records = AttendanceRecord::where('classroom_id', $classroom->id)
            ->where('date', $date->toDateString())
            ->pluck('status', 'student_id');

        $recent = AttendanceRecord::where('classroom_id', $classroom->id)
            ->selectRaw('date, status, count(*) as n')
            ->groupBy('date', 'status')
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn ($r) => Carbon::parse($r->date)->toDateString())
            ->take(10)
            ->map(fn ($rows, $day) => [
                'date' => Carbon::parse($day),
                'counts' => $rows->pluck('n', 'status')->all(),
                'rate' => GradeCalculator::attendanceRate($rows->pluck('n', 'status')->all()),
            ]);

        return view('attendance.show', [
            'classroom' => $classroom->load('teacher'),
            'date' => $date,
            'students' => $students,
            'records' => $records,
            'recent' => $recent,
            'taken' => $records->isNotEmpty(),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'statuses' => ['required', 'array'],
            'statuses.*' => [Rule::in(array_keys(config('lms.attendance_statuses')))],
        ], [
            'date.before_or_equal' => 'Attendance can’t be recorded for a future date.',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $enrolled = $classroom->students()->pluck('students.id')->flip();

        DB::transaction(function () use ($data, $classroom, $date, $enrolled) {
            foreach ($data['statuses'] as $studentId => $status) {
                if (! isset($enrolled[$studentId])) {
                    continue;
                }
                AttendanceRecord::updateOrCreate(
                    ['classroom_id' => $classroom->id, 'student_id' => $studentId, 'date' => $date],
                    ['status' => $status],
                );
            }
        });

        return redirect()->route('classrooms.attendance', [$classroom, 'date' => $date])
            ->with('success', 'Attendance saved for '.Carbon::parse($date)->format('l, F j').'.');
    }

    private function date(?string $value): Carbon
    {
        try {
            $date = $value ? Carbon::parse($value)->startOfDay() : Carbon::today();
        } catch (\Throwable) {
            $date = Carbon::today();
        }

        return $date->isFuture() ? Carbon::today() : $date;
    }
}
