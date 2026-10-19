<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Gradebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $classrooms = $request->user()->manageableClassrooms()
            ->search($request->query('q'))
            ->when($request->query('term'), fn ($q, $term) => $q->where('term', $term))
            ->with('teacher')
            ->withCount(['students', 'assignments'])
            ->orderBy('name')
            ->get();

        $averages = $classrooms->mapWithKeys(fn ($c) => [$c->id => Gradebook::for($c)->classAverage()]);

        return view('classrooms.index', [
            'classrooms' => $classrooms,
            'averages' => $averages,
            'terms' => Classroom::distinct()->orderBy('term')->pluck('term'),
        ]);
    }

    public function show(Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        $book = Gradebook::for($classroom->load('teacher'));
        $enrolledIds = $book->students->pluck('id');

        return view('classrooms.show', [
            'classroom' => $classroom,
            'book' => $book,
            'available' => Student::where('status', 'active')
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'student_number']),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');

        return view('classrooms.form', [
            'classroom' => new Classroom(['term' => config('lms.current_term'), 'capacity' => 30]),
            'teachers' => Teacher::orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');
        $classroom = Classroom::create($this->validated($request));

        return redirect()->route('classrooms.show', $classroom)->with('success', "{$classroom->name} created. Add students and assignments next.");
    }

    public function edit(Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        return view('classrooms.form', [
            'classroom' => $classroom,
            'teachers' => Teacher::orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);
        $data = $this->validated($request, $classroom);

        // Only admins can reassign a class to another teacher.
        if (! $request->user()->isAdmin()) {
            unset($data['teacher_id']);
        }

        $classroom->update($data);

        return redirect()->route('classrooms.show', $classroom)->with('success', 'Class settings saved.');
    }

    public function destroy(Classroom $classroom)
    {
        Gate::authorize('admin');
        $name = $classroom->name;
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', "{$name} and its gradebook were deleted.");
    }

    private function validated(Request $request, ?Classroom $classroom = null): array
    {
        $categories = array_keys(config('lms.categories'));

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('classrooms')->ignore($classroom?->id)],
            'subject' => ['required', 'string', 'max:100'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'room' => ['nullable', 'string', 'max:50'],
            'schedule' => ['nullable', 'string', 'max:100'],
            'term' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'between:1,500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'custom_weights' => ['boolean'],
        ];
        foreach ($categories as $cat) {
            $rules["weights.$cat"] = ['nullable', 'numeric', 'between:0,100'];
        }

        $data = validator($request->all(), $rules)
            ->after(function (Validator $v) use ($request, $categories) {
                if (! $request->boolean('custom_weights')) {
                    return;
                }
                $total = collect($categories)->sum(fn ($c) => (float) $request->input("weights.$c", 0));
                if (abs($total - 100) > 0.01) {
                    $v->errors()->add('weights', "Category weights must add up to 100% (they add up to {$total}%).");
                }
            })
            ->validate();

        $data['code'] = strtoupper($data['code']);
        $data['weights'] = $request->boolean('custom_weights')
            ? collect($categories)->mapWithKeys(fn ($c) => [$c => (float) $request->input("weights.$c", 0)])->all()
            : null;
        unset($data['custom_weights']);

        return $data;
    }
}
