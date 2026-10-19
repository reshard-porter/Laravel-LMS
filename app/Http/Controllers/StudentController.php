<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\GradeCalculator;
use App\Services\Gradebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $classroomId = $request->query('classroom');

        $students = Student::search($request->query('q'))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($classroomId, fn ($q) => $q->whereHas('classrooms', fn ($c) => $c->where('classrooms.id', $classroomId)))
            ->withCount('classrooms')
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(20)->withQueryString();

        return view('students.index', [
            'students' => $students,
            'classrooms' => Classroom::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function show(Student $student)
    {
        return view('students.show', [
            'student' => $student,
            'summary' => $this->summary($student),
        ]);
    }

    public function reportCard(Student $student)
    {
        return view('students.report-card', [
            'student' => $student,
            'summary' => $this->summary($student),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        return view('students.form', ['student' => new Student([
            'student_number' => Student::nextStudentNumber(),
            'status' => 'active',
            'year_level' => 1,
        ])]);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');
        $student = Student::create($this->validated($request));

        return redirect()->route('students.show', $student)->with('success', "{$student->full_name} added.");
    }

    public function edit(Student $student)
    {
        Gate::authorize('admin');

        return view('students.form', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        Gate::authorize('admin');
        $student->update($this->validated($request, $student));

        return redirect()->route('students.show', $student)->with('success', 'Student details saved.');
    }

    public function destroy(Student $student)
    {
        Gate::authorize('admin');
        $name = $student->full_name;
        $student->delete();

        return redirect()->route('students.index')->with('success', "{$name} and their grades were deleted.");
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'student_number' => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student?->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students')->ignore($student?->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'year_level' => ['required', 'integer', 'between:1,12'],
            'guardian_name' => ['nullable', 'string', 'max:200'],
            'guardian_phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
        ]);
    }

    /** Per-class results for a student's profile and report card. */
    private function summary(Student $student): array
    {
        $rows = [];
        $percents = [];

        $classrooms = $student->classrooms()->with('teacher')->orderBy('name')->get();

        $attendance = AttendanceRecord::where('student_id', $student->id)
            ->selectRaw('classroom_id, status, count(*) as n')
            ->groupBy('classroom_id', 'status')
            ->get()
            ->groupBy('classroom_id');

        foreach ($classrooms as $classroom) {
            $book = Gradebook::for($classroom);
            $result = $book->results[$student->id] ?? ['percent' => null, 'letter' => null, 'categories' => [], 'missing' => 0];

            $counts = ($attendance[$classroom->id] ?? collect())->pluck('n', 'status')->all();

            $work = $book->assignments->map(fn ($a) => [
                'assignment' => $a,
                'grade' => $book->grade($student->id, $a->id),
            ]);

            if ($result['percent'] !== null) {
                $percents[] = $result['percent'];
            }

            $rows[] = [
                'classroom' => $classroom,
                'percent' => $result['percent'],
                'letter' => $result['letter'],
                'categories' => $result['categories'],
                'weights' => $book->weights,
                'missing' => $result['missing'],
                'attendance' => GradeCalculator::attendanceRate($counts),
                'absences' => $counts['absent'] ?? 0,
                'work' => $work,
            ];
        }

        $overall = $percents ? array_sum($percents) / count($percents) : null;

        return [
            'rows' => $rows,
            'overall' => $overall,
            'overallLetter' => GradeCalculator::letter($overall),
        ];
    }
}
