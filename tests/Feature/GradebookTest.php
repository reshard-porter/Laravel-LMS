<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Services\Gradebook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradebookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeSchool();
    }

    public function test_teacher_can_save_scores_excuse_and_clear(): void
    {
        $a = $this->addAssignment('homework', 10);
        [$amy, $ben, $cal] = $this->classroom->students()->orderBy('first_name')->get();
        Grade::create(['assignment_id' => $a->id, 'student_id' => $cal->id, 'score' => 4]);

        $this->actingAs($this->teacherUser)
            ->put("/classrooms/{$this->classroom->id}/gradebook", ['scores' => [
                $amy->id => [$a->id => '9.5'],
                $ben->id => [$a->id => 'ex'],
                $cal->id => [$a->id => ''],
            ]])
            ->assertRedirect("/classrooms/{$this->classroom->id}/gradebook")
            ->assertSessionHas('success');

        $this->assertSame(9.5, Grade::where('student_id', $amy->id)->first()->score);
        $this->assertTrue(Grade::where('student_id', $ben->id)->first()->excused);
        $this->assertNull(Grade::where('student_id', $cal->id)->first());
    }

    public function test_invalid_scores_are_rejected_and_nothing_is_saved(): void
    {
        $a = $this->addAssignment('homework', 10);
        [$amy, $ben] = $this->classroom->students()->orderBy('first_name')->get();

        $this->actingAs($this->teacherUser)
            ->from("/classrooms/{$this->classroom->id}/gradebook")
            ->put("/classrooms/{$this->classroom->id}/gradebook", ['scores' => [
                $amy->id => [$a->id => '8'],
                $ben->id => [$a->id => '11'], // above max
            ]])
            ->assertSessionHasErrors("scores.{$ben->id}.{$a->id}");

        $this->assertSame(0, Grade::count());
    }

    public function test_scores_for_students_outside_the_class_are_ignored(): void
    {
        $a = $this->addAssignment();
        $outsider = \App\Models\Student::create(['student_number' => 'X-1', 'first_name' => 'Out', 'last_name' => 'Sider', 'year_level' => 1]);

        $this->actingAs($this->teacherUser)
            ->put("/classrooms/{$this->classroom->id}/gradebook", ['scores' => [$outsider->id => [$a->id => '5']]]);

        $this->assertSame(0, Grade::count());
    }

    public function test_gradebook_results_use_class_weights(): void
    {
        $hw = $this->addAssignment('homework', 10);
        $exam = $this->addAssignment('exam', 100);
        $amy = $this->classroom->students()->where('first_name', 'Amy')->first();
        Grade::create(['assignment_id' => $hw->id, 'student_id' => $amy->id, 'score' => 10]);   // 100%
        Grade::create(['assignment_id' => $exam->id, 'student_id' => $amy->id, 'score' => 50]); // 50%

        $this->classroom->update(['weights' => ['homework' => 50, 'quiz' => 0, 'project' => 0, 'exam' => 50]]);
        $book = Gradebook::for($this->classroom->fresh());

        $this->assertEqualsWithDelta(75.0, $book->results[$amy->id]['percent'], 0.001);
        $this->assertSame('C', $book->results[$amy->id]['letter']);
        // Two students have no score on two past-due assignments each.
        $this->assertSame(4, $book->ungradedCount());
    }

    public function test_csv_export_contains_scores_and_final_grade(): void
    {
        $a = $this->addAssignment('homework', 10);
        $amy = $this->classroom->students()->where('first_name', 'Amy')->first();
        Grade::create(['assignment_id' => $a->id, 'student_id' => $amy->id, 'score' => 9]);

        $csv = $this->actingAs($this->teacherUser)
            ->get("/classrooms/{$this->classroom->id}/gradebook/export")
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Final %', $csv);
        $this->assertStringContainsString('S-1,Test,Amy,9,90.0,A,0', $csv);
    }

    public function test_max_points_cannot_drop_below_an_existing_score(): void
    {
        $a = $this->addAssignment('homework', 10);
        Grade::create(['assignment_id' => $a->id, 'student_id' => $this->classroom->students()->first()->id, 'score' => 9]);

        $this->actingAs($this->teacherUser)
            ->put("/classrooms/{$this->classroom->id}/assignments/{$a->id}", [
                'title' => 'HW', 'category' => 'homework', 'max_points' => 5,
            ])
            ->assertSessionHasErrors('max_points');

        $this->assertSame(10.0, $a->fresh()->max_points);
    }

    public function test_assignments_from_another_class_cannot_be_edited_through_this_one(): void
    {
        $foreign = $this->otherClassroom->assignments()->create(['title' => 'X', 'category' => 'quiz', 'max_points' => 5]);

        $this->actingAs($this->admin)
            ->get("/classrooms/{$this->classroom->id}/assignments/{$foreign->id}/edit")
            ->assertNotFound();
    }
}
