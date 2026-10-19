<?php

namespace Tests;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected User $admin;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected Classroom $classroom;
    protected Classroom $otherClassroom;

    /** A tiny school: one admin, two teachers each with a class, three students in the first class. */
    protected function makeSchool(): void
    {
        $this->admin = User::create(['name' => 'Ada Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin']);

        $this->teacherUser = User::create(['name' => 'Tina Teacher', 'email' => 'tina@test.local', 'password' => 'password', 'role' => 'teacher']);
        $this->teacher = new Teacher(['first_name' => 'Tina', 'last_name' => 'Teacher', 'email' => 'tina@test.local', 'department' => 'Maths', 'salary' => 1000]);
        $this->teacher->user()->associate($this->teacherUser)->save();

        $other = Teacher::create(['first_name' => 'Otto', 'last_name' => 'Other', 'email' => 'otto@test.local', 'department' => 'Art', 'salary' => 1000]);

        $this->classroom = Classroom::create([
            'name' => 'Algebra', 'code' => 'MA100', 'subject' => 'Maths', 'teacher_id' => $this->teacher->id,
            'term' => 'Fall 2026', 'capacity' => 3,
        ]);
        $this->otherClassroom = Classroom::create([
            'name' => 'Painting', 'code' => 'AR100', 'subject' => 'Art', 'teacher_id' => $other->id,
            'term' => 'Fall 2026', 'capacity' => 30,
        ]);

        foreach (['Amy', 'Ben', 'Cal'] as $i => $name) {
            $s = Student::create(['student_number' => 'S-'.($i + 1), 'first_name' => $name, 'last_name' => 'Test', 'year_level' => 1, 'status' => 'active']);
            $this->classroom->students()->attach($s->id);
        }
    }

    protected function addAssignment(string $category = 'homework', float $max = 10, ?string $due = '-3 days'): Assignment
    {
        return $this->classroom->assignments()->create([
            'title' => ucfirst($category).' '.uniqid(), 'category' => $category, 'max_points' => $max,
            'due_date' => $due ? now()->modify($due)->startOfDay() : null,
        ]);
    }
}
