<?php

namespace Tests\Unit;

use App\Services\GradeCalculator;
use Tests\TestCase;

class GradeCalculatorTest extends TestCase
{
    private array $weights = ['homework' => 20, 'quiz' => 20, 'project' => 20, 'exam' => 40];

    private function e(string $cat, ?float $score, float $max, bool $excused = false): array
    {
        return ['category' => $cat, 'score' => $score, 'max' => $max, 'excused' => $excused];
    }

    public function test_category_percent_is_points_earned_over_points_possible(): void
    {
        $p = GradeCalculator::categoryPercents([
            $this->e('homework', 5, 10),
            $this->e('homework', 20, 20),
        ]);

        $this->assertEqualsWithDelta(25 / 30 * 100, $p['homework'], 0.0001);
    }

    public function test_weighted_grade_uses_category_weights(): void
    {
        $pct = GradeCalculator::weightedPercent([
            $this->e('homework', 10, 10),  // 100%
            $this->e('quiz', 5, 10),       // 50%
            $this->e('project', 8, 10),    // 80%
            $this->e('exam', 70, 100),     // 70%
        ], $this->weights);

        // (100*20 + 50*20 + 80*20 + 70*40) / 100
        $this->assertEqualsWithDelta(74.0, $pct, 0.0001);
    }

    public function test_categories_without_graded_work_are_left_out(): void
    {
        $pct = GradeCalculator::weightedPercent([
            $this->e('homework', 9, 10),
            $this->e('exam', null, 100), // not graded yet
        ], $this->weights);

        $this->assertEqualsWithDelta(90.0, $pct, 0.0001);
    }

    public function test_excused_and_ungraded_work_is_ignored(): void
    {
        $pct = GradeCalculator::weightedPercent([
            $this->e('homework', 8, 10),
            $this->e('homework', null, 10, true),
            $this->e('homework', null, 10),
        ], $this->weights);

        $this->assertEqualsWithDelta(80.0, $pct, 0.0001);
    }

    public function test_no_graded_work_means_no_grade(): void
    {
        $this->assertNull(GradeCalculator::weightedPercent([$this->e('exam', null, 100)], $this->weights));
        $this->assertNull(GradeCalculator::letter(null));
    }

    public function test_letter_boundaries(): void
    {
        $this->assertSame('A', GradeCalculator::letter(90));
        $this->assertSame('B', GradeCalculator::letter(89.99));
        $this->assertSame('C', GradeCalculator::letter(70));
        $this->assertSame('D', GradeCalculator::letter(60));
        $this->assertSame('F', GradeCalculator::letter(59.9));
        $this->assertSame('F', GradeCalculator::letter(0));
    }

    public function test_attendance_rate_counts_late_as_attended_and_ignores_excused(): void
    {
        $rate = GradeCalculator::attendanceRate(['present' => 6, 'late' => 2, 'absent' => 2, 'excused' => 5]);

        $this->assertEqualsWithDelta(80.0, $rate, 0.0001);
        $this->assertNull(GradeCalculator::attendanceRate(['excused' => 3]));
    }
}
