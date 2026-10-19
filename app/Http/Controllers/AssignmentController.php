<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    public function create(Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        return view('assignments.form', [
            'classroom' => $classroom,
            'assignment' => new Assignment(['category' => 'homework', 'max_points' => 100, 'due_date' => now()->addWeek()]),
        ]);
    }

    public function store(Request $request, Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);
        $assignment = $classroom->assignments()->create($this->validated($request));

        return redirect()->route('classrooms.show', [$classroom, 'tab' => 'assignments'])
            ->with('success', "“{$assignment->title}” added to the gradebook.");
    }

    public function edit(Classroom $classroom, Assignment $assignment)
    {
        Gate::authorize('manage-classroom', $classroom);

        return view('assignments.form', compact('classroom', 'assignment'));
    }

    public function update(Request $request, Classroom $classroom, Assignment $assignment)
    {
        Gate::authorize('manage-classroom', $classroom);
        $data = $this->validated($request);

        $highest = $assignment->grades()->max('score');
        if ($highest !== null && $data['max_points'] < $highest) {
            return back()->withInput()->withErrors([
                'max_points' => "A student already scored {$highest}. Max points can’t be lower than that.",
            ]);
        }

        $assignment->update($data);

        return redirect()->route('classrooms.show', [$classroom, 'tab' => 'assignments'])
            ->with('success', 'Assignment saved.');
    }

    public function destroy(Classroom $classroom, Assignment $assignment)
    {
        Gate::authorize('manage-classroom', $classroom);
        $title = $assignment->title;
        $assignment->delete();

        return redirect()->route('classrooms.show', [$classroom, 'tab' => 'assignments'])
            ->with('success', "“{$title}” and its scores were deleted.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(config('lms.categories')))],
            'max_points' => ['required', 'numeric', 'min:1', 'max:10000'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
