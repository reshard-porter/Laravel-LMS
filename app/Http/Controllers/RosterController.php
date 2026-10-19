<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RosterController extends Controller
{
    public function store(Request $request, Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ], [
            'student_ids.required' => 'Choose at least one student to enrol.',
        ]);

        $already = $classroom->students()->pluck('students.id');
        $new = collect($data['student_ids'])->unique()->diff($already);
        $seatsLeft = $classroom->capacity - $already->count();

        if ($new->count() > $seatsLeft) {
            return back()->withErrors([
                'student_ids' => "This class has {$seatsLeft} seat(s) left; you selected {$new->count()}.",
            ]);
        }

        $classroom->students()->attach($new->mapWithKeys(fn ($id) => [$id => ['enrolled_on' => now()->toDateString()]])->all());

        return redirect()->route('classrooms.show', [$classroom, 'tab' => 'roster'])
            ->with('success', trans_choice('{1} 1 student enrolled.|[2,*] :count students enrolled.', $new->count()));
    }

    public function destroy(Classroom $classroom, Student $student)
    {
        Gate::authorize('manage-classroom', $classroom);

        $classroom->students()->detach($student->id);

        // Their scores for this class no longer apply.
        $student->grades()->whereIn('assignment_id', $classroom->assignments()->pluck('id'))->delete();
        $student->attendance()->where('classroom_id', $classroom->id)->delete();

        return redirect()->route('classrooms.show', [$classroom, 'tab' => 'roster'])
            ->with('success', "{$student->full_name} removed from {$classroom->name}.");
    }
}
