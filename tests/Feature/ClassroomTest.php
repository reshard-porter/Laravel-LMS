<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeSchool();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Geometry', 'code' => 'ma200', 'subject' => 'Maths', 'teacher_id' => $this->teacher->id,
            'term' => 'Fall 2026', 'capacity' => 25, 'custom_weights' => '0',
        ], $overrides);
    }

    public function test_admin_creates_a_class_and_the_code_is_uppercased(): void
    {
        $this->actingAs($this->admin)->post('/classrooms', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('classrooms', ['code' => 'MA200', 'name' => 'Geometry']);
    }

    public function test_custom_weights_must_add_up_to_100(): void
    {
        $this->actingAs($this->admin)
            ->post('/classrooms', $this->payload(['custom_weights' => '1', 'weights' => ['homework' => 50, 'quiz' => 10, 'project' => 10, 'exam' => 10]]))
            ->assertSessionHasErrors('weights');

        $this->actingAs($this->admin)
            ->post('/classrooms', $this->payload(['custom_weights' => '1', 'weights' => ['homework' => 50, 'quiz' => 10, 'project' => 10, 'exam' => 30]]))
            ->assertSessionHasNoErrors();
    }

    public function test_teacher_can_edit_settings_but_not_reassign_the_class(): void
    {
        $this->actingAs($this->teacherUser)
            ->put("/classrooms/{$this->classroom->id}", $this->payload(['name' => 'Algebra II', 'code' => 'MA100', 'teacher_id' => null]))
            ->assertRedirect();

        $fresh = $this->classroom->fresh();
        $this->assertSame('Algebra II', $fresh->name);
        $this->assertSame($this->teacher->id, $fresh->teacher_id);
    }

    public function test_enrolment_respects_capacity(): void
    {
        $extra = Student::create(['student_number' => 'S-9', 'first_name' => 'Dee', 'last_name' => 'Test', 'year_level' => 1]);

        $this->actingAs($this->teacherUser)
            ->post("/classrooms/{$this->classroom->id}/roster", ['student_ids' => [$extra->id]])
            ->assertSessionHasErrors('student_ids');

        $this->classroom->update(['capacity' => 4]);
        $this->actingAs($this->teacherUser)
            ->post("/classrooms/{$this->classroom->id}/roster", ['student_ids' => [$extra->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $this->classroom->students()->count());
    }

    public function test_removing_a_student_deletes_their_scores_for_that_class(): void
    {
        $a = $this->addAssignment();
        $amy = $this->classroom->students()->where('first_name', 'Amy')->first();
        Grade::create(['assignment_id' => $a->id, 'student_id' => $amy->id, 'score' => 5]);

        $this->actingAs($this->teacherUser)
            ->delete("/classrooms/{$this->classroom->id}/roster/{$amy->id}")
            ->assertRedirect();

        $this->assertSame(2, $this->classroom->students()->count());
        $this->assertSame(0, Grade::count());
    }

    public function test_attendance_is_saved_and_updated_for_a_day(): void
    {
        $students = $this->classroom->students()->pluck('students.id');
        $date = now()->subDay()->toDateString();
        $url = "/classrooms/{$this->classroom->id}/attendance";

        $this->actingAs($this->teacherUser)->put($url, [
            'date' => $date,
            'statuses' => $students->mapWithKeys(fn ($id) => [$id => 'present'])->all(),
        ])->assertRedirect();

        $this->actingAs($this->teacherUser)->put($url, [
            'date' => $date,
            'statuses' => [$students[0] => 'absent'],
        ]);

        $this->assertSame(3, AttendanceRecord::count());
        $this->assertSame('absent', AttendanceRecord::where('student_id', $students[0])->first()->status);
        $this->actingAs($this->teacherUser)->get("$url?date=$date")->assertOk()->assertSee('Taken');
    }

    public function test_attendance_cannot_be_recorded_for_the_future(): void
    {
        $this->actingAs($this->teacherUser)
            ->put("/classrooms/{$this->classroom->id}/attendance", [
                'date' => now()->addDays(3)->toDateString(),
                'statuses' => [$this->classroom->students()->first()->id => 'present'],
            ])
            ->assertSessionHasErrors('date');
    }

    public function test_admin_adds_a_student_and_a_teacher_with_a_sign_in(): void
    {
        $this->actingAs($this->admin)->post('/students', [
            'student_number' => 'S-100', 'first_name' => 'New', 'last_name' => 'Kid', 'year_level' => 2, 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('students', ['student_number' => 'S-100']);

        $this->actingAs($this->admin)->post('/teachers', [
            'first_name' => 'Nia', 'last_name' => 'New', 'email' => 'nia@test.local', 'department' => 'Science',
            'salary' => 50000, 'login_password' => 'secret-pass',
        ])->assertRedirect();

        $this->post('/logout');
        $this->post('/login', ['email' => 'nia@test.local', 'password' => 'secret-pass'])->assertRedirect('/');
        $this->assertAuthenticated();
    }
}
