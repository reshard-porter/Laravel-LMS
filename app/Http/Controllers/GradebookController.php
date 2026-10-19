<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Grade;
use App\Services\Gradebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GradebookController extends Controller
{
    public function show(Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        return view('gradebook.show', [
            'classroom' => $classroom->load('teacher'),
            'book' => Gradebook::for($classroom),
        ]);
    }

    /**
     * Save the whole grid. Each cell is blank (not graded), a number of points, or "EX" (excused).
     */
    public function update(Request $request, Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);

        $assignments = $classroom->assignments()->get()->keyBy('id');
        $studentIds = $classroom->students()->pluck('students.id')->flip();
        $cells = $request->input('scores', []);
        $errors = [];
        $changes = [];

        foreach ((array) $cells as $studentId => $row) {
            if (! isset($studentIds[$studentId]) || ! is_array($row)) {
                continue;
            }
            foreach ($row as $assignmentId => $raw) {
                $assignment = $assignments[$assignmentId] ?? null;
                if (! $assignment) {
                    continue;
                }
                $value = trim((string) $raw);
                $key = "scores.$studentId.$assignmentId";

                if ($value === '') {
                    $changes[] = [$studentId, $assignmentId, null, false];
                } elseif (strcasecmp($value, 'ex') === 0) {
                    $changes[] = [$studentId, $assignmentId, null, true];
                } elseif (is_numeric($value) && $value >= 0 && $value <= $assignment->max_points) {
                    $changes[] = [$studentId, $assignmentId, round((float) $value, 2), false];
                } else {
                    $errors[$key] = "Enter 0–{$this->fmt($assignment->max_points)} points, EX for excused, or leave blank.";
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $existing = Grade::whereIn('assignment_id', $assignments->keys())->get()
            ->keyBy(fn ($g) => "{$g->student_id}:{$g->assignment_id}");

        $saved = 0;
        DB::transaction(function () use ($changes, $existing, &$saved) {
            foreach ($changes as [$studentId, $assignmentId, $score, $excused]) {
                $grade = $existing["$studentId:$assignmentId"] ?? null;

                if ($score === null && ! $excused) {
                    if ($grade && $grade->comment === null) {
                        $grade->delete();
                        $saved++;
                    } elseif ($grade && ($grade->score !== null || $grade->excused)) {
                        $grade->update(['score' => null, 'excused' => false]);
                        $saved++;
                    }
                    continue;
                }

                if ($grade && $grade->score === $score && $grade->excused === $excused) {
                    continue;
                }

                Grade::updateOrCreate(
                    ['student_id' => $studentId, 'assignment_id' => $assignmentId],
                    ['score' => $score, 'excused' => $excused],
                );
                $saved++;
            }
        });

        return redirect()->route('classrooms.gradebook', $classroom)
            ->with('success', $saved ? trans_choice('{1} 1 score saved.|[2,*] :count scores saved.', $saved) : 'No changes to save.');
    }

    public function export(Classroom $classroom)
    {
        Gate::authorize('manage-classroom', $classroom);
        $book = Gradebook::for($classroom);

        $filename = Str::slug("{$classroom->code} gradebook ".now()->format('Y-m-d')).'.csv';

        return response()->streamDownload(function () use ($book) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the file as UTF-8

            $header = ['Student number', 'Last name', 'First name'];
            foreach ($book->assignments as $a) {
                $header[] = "{$a->title} (/{$this->fmt($a->max_points)})";
            }
            fputcsv($out, array_merge($header, ['Final %', 'Letter', 'Missing']));

            foreach ($book->students as $s) {
                $row = [$s->student_number, $s->last_name, $s->first_name];
                foreach ($book->assignments as $a) {
                    $g = $book->grade($s->id, $a->id);
                    $row[] = $g?->excused ? 'EX' : ($g?->score === null ? '' : $this->fmt($g->score));
                }
                $r = $book->results[$s->id];
                $row[] = $r['percent'] === null ? '' : number_format($r['percent'], 1);
                $row[] = $r['letter'] ?? '';
                $row[] = $r['missing'];
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
