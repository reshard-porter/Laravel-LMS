<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::search($request->query('q'))
            ->withCount('classrooms')
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function show(Teacher $teacher)
    {
        $teacher->load(['classrooms' => fn ($q) => $q->withCount('students')->orderBy('name'), 'user']);

        return view('teachers.show', compact('teacher'));
    }

    public function create()
    {
        Gate::authorize('admin');

        return view('teachers.form', ['teacher' => new Teacher(['hire_date' => now()])]);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');
        $data = $this->validated($request);

        $teacher = DB::transaction(function () use ($data, $request) {
            $teacher = Teacher::create($data);
            $this->syncLogin($teacher, $request);

            return $teacher;
        });

        return redirect()->route('teachers.show', $teacher)->with('success', "{$teacher->full_name} added.");
    }

    public function edit(Teacher $teacher)
    {
        Gate::authorize('admin');

        return view('teachers.form', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        Gate::authorize('admin');
        $data = $this->validated($request, $teacher);

        DB::transaction(function () use ($teacher, $data, $request) {
            $teacher->update($data);
            $this->syncLogin($teacher, $request);
        });

        return redirect()->route('teachers.show', $teacher)->with('success', 'Teacher details saved.');
    }

    public function destroy(Teacher $teacher)
    {
        Gate::authorize('admin');

        $name = $teacher->full_name;
        DB::transaction(function () use ($teacher) {
            $teacher->user?->delete();
            $teacher->delete(); // their classes stay, unassigned
        });

        return redirect()->route('teachers.index')->with('success', "{$name} removed. Their classes are now unassigned.");
    }

    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        $userId = $teacher?->user_id;

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('teachers', 'email')->ignore($teacher?->id),
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'department' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['required', 'integer', 'min:0', 'max:100000000'],
            'login_password' => ['nullable', Password::min(8)],
        ]);
    }

    /** Give the teacher a sign-in (or update it) so they can manage their own classes. */
    private function syncLogin(Teacher $teacher, Request $request): void
    {
        $password = $request->input('login_password');
        $user = $teacher->user;

        if (! $user && ! $password) {
            return;
        }

        $attrs = ['name' => $teacher->full_name, 'email' => $teacher->email];
        if ($password) {
            $attrs['password'] = $password;
        }

        if ($user) {
            $user->update($attrs);
        } else {
            $user = User::create($attrs + ['role' => User::ROLE_TEACHER]);
            $teacher->user()->associate($user)->save();
        }
    }
}
