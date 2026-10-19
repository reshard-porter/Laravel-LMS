<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\GradeCalculator;
use App\Services\Gradebook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $classrooms = $user->manageableClassrooms()->with('teacher')->orderBy('name')->get();
        $classroomIds = $classrooms->pluck('id');

        $letters = array_fill_keys(array_keys(config('lms.letter_grades')), 0);
        $allPercents = [];
        $classRows = [];
        $atRisk = [];
        $needsGrading = 0;

        foreach ($classrooms as $classroom) {
            $book = Gradebook::for($classroom);

            foreach ($book->letterDistribution() as $letter => $n) {
                $letters[$letter] += $n;
            }
            foreach ($book->students as $student) {
                $r = $book->results[$student->id];
                if ($r['percent'] !== null) {
                    $allPercents[] = $r['percent'];
                }
                if (GradeCalculator::isAtRisk($r['percent'])) {
                    $atRisk[] = [
                        'student' => $student,
                        'classroom' => $classroom,
                        'percent' => $r['percent'],
                        'letter' => $r['letter'],
                        'missing' => $r['missing'],
                    ];
                }
            }

            $ungraded = $book->ungradedCount();
            $needsGrading += $ungraded;
            $classRows[] = [
                'classroom' => $classroom,
                'average' => $book->classAverage(),
                'students' => $book->students->count(),
                'ungraded' => $ungraded,
            ];
        }

        usort($atRisk, fn ($a, $b) => $a['percent'] <=> $b['percent']);

        // Attendance over the last 30 days, and week by week for the trend chart.
        $since = Carbon::today()->subDays(29);
        $statusCounts = AttendanceRecord::whereIn('classroom_id', $classroomIds)
            ->where('date', '>=', $since->toDateString())
            ->selectRaw('status, count(*) as n')->groupBy('status')
            ->pluck('n', 'status')->all();

        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = Carbon::today()->startOfWeek()->subWeeks($i);
            $counts = AttendanceRecord::whereIn('classroom_id', $classroomIds)
                ->whereBetween('date', [$start->toDateString(), $start->copy()->endOfWeek()->toDateString()])
                ->selectRaw('status, count(*) as n')->groupBy('status')
                ->pluck('n', 'status')->all();
            $weeks[] = [
                'label' => $start->format('M j'),
                'rate' => GradeCalculator::attendanceRate($counts),
            ];
        }

        $upcoming = Assignment::with('classroom')
            ->whereIn('classroom_id', $classroomIds)
            ->whereBetween('due_date', [Carbon::today(), Carbon::today()->addDays(14)])
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $studentCount = $user->isAdmin()
            ? Student::where('status', 'active')->count()
            : Student::whereHas('classrooms', fn ($q) => $q->whereIn('classrooms.id', $classroomIds))->count();

        $average = $allPercents ? array_sum($allPercents) / count($allPercents) : null;

        usort($classRows, fn ($a, $b) => ($b['average'] ?? -1) <=> ($a['average'] ?? -1));

        return view('dashboard', [
            'stats' => [
                'students' => $studentCount,
                'teachers' => $user->isAdmin() ? Teacher::count() : null,
                'classes' => $classrooms->count(),
                'average' => $average,
                'averageLetter' => GradeCalculator::letter($average),
                'attendance' => GradeCalculator::attendanceRate($statusCounts),
                'needsGrading' => $needsGrading,
            ],
            'letters' => $letters,
            'classRows' => $classRows,
            'weeks' => $weeks,
            'atRisk' => array_slice($atRisk, 0, 8),
            'atRiskTotal' => count($atRisk),
            'upcoming' => $upcoming,
        ]);
    }
}
