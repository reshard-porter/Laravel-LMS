<?php

namespace App\Services;

use App\Models\Classroom;
use Illuminate\Support\Collection;

/**
 * Everything the gradebook, dashboard and report cards need for one classroom,
 * loaded in a handful of queries and computed once.
 */
class Gradebook
{
    public Collection $students;
    public Collection $assignments;

    /** @var array<int, array<int, \App\Models\Grade>> [student_id][assignment_id] => Grade */
    public array $grades = [];

    /** @var array<int, array{percent: ?float, letter: ?string, categories: array<string, float>, missing: int}> */
    public array $results = [];

    /** @var array<int, ?float> assignment_id => average percentage */
    public array $assignmentAverages = [];

    public array $weights;

    public function __construct(public Classroom $classroom)
    {
        $classroom->loadMissing([
            'students' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            'assignments' => fn ($q) => $q->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
            'assignments.grades',
        ]);

        $this->students = $classroom->students;
        $this->assignments = $classroom->assignments;
        $this->weights = $classroom->categoryWeights();

        foreach ($this->assignments as $assignment) {
            foreach ($assignment->grades as $grade) {
                $this->grades[$grade->student_id][$assignment->id] = $grade;
            }
        }

        $this->compute();
    }

    public static function for(Classroom $classroom): self
    {
        return new self($classroom);
    }

    public function grade(int $studentId, int $assignmentId)
    {
        return $this->grades[$studentId][$assignmentId] ?? null;
    }

    private function compute(): void
    {
        foreach ($this->students as $student) {
            $entries = [];
            $missing = 0;

            foreach ($this->assignments as $a) {
                $g = $this->grade($student->id, $a->id);
                $entries[] = [
                    'category' => $a->category,
                    'score' => $g?->score,
                    'max' => $a->max_points,
                    'excused' => (bool) $g?->excused,
                ];
                if ($a->isPastDue() && ($g === null || ($g->score === null && ! $g->excused))) {
                    $missing++;
                }
            }

            $percent = GradeCalculator::weightedPercent($entries, $this->weights);
            $this->results[$student->id] = [
                'percent' => $percent,
                'letter' => GradeCalculator::letter($percent),
                'categories' => GradeCalculator::categoryPercents($entries),
                'missing' => $missing,
            ];
        }

        foreach ($this->assignments as $a) {
            $pcts = [];
            foreach ($this->students as $student) {
                $g = $this->grade($student->id, $a->id);
                if ($g && $g->score !== null && ! $g->excused && $a->max_points > 0) {
                    $pcts[] = $g->score / $a->max_points * 100;
                }
            }
            $this->assignmentAverages[$a->id] = $pcts ? array_sum($pcts) / count($pcts) : null;
        }
    }

    public function classAverage(): ?float
    {
        $values = array_filter(array_column($this->results, 'percent'), fn ($v) => $v !== null);

        return $values ? array_sum($values) / count($values) : null;
    }

    /** Number of student/assignment cells past due with no score yet. */
    public function ungradedCount(): int
    {
        return array_sum(array_column($this->results, 'missing'));
    }

    /** @return array<string, int> letter => number of students */
    public function letterDistribution(): array
    {
        $dist = array_fill_keys(array_keys(config('lms.letter_grades')), 0);
        foreach ($this->results as $r) {
            if ($r['letter'] !== null) {
                $dist[$r['letter']]++;
            }
        }

        return $dist;
    }

    /** Data for the gradebook's live recalculation in the browser. */
    public function toClientConfig(): array
    {
        return [
            'weights' => $this->weights,
            'letters' => config('lms.letter_grades'),
            'atRisk' => config('lms.at_risk_below'),
            'assignments' => $this->assignments->map(fn ($a) => [
                'id' => $a->id,
                'category' => $a->category,
                'max' => $a->max_points,
            ])->values(),
        ];
    }
}
