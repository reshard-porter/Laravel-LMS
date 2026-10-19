<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A believable demo school, generated relative to today so the dashboard always looks "live":
 * past assignments are graded, one is half graded, a few are still upcoming.
 * The random numbers are seeded, so every run produces the same school.
 */
class DemoSchoolSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const TEACHERS = [
        'reshard'  => ['Reshard', 'Porter', 'Computer Science', '2016-08-22', 92000, '876-555-0141'],
        'randy'    => ['Randy', 'Campbell', 'Computer Science', '2018-01-08', 84000, '876-555-0172'],
        'rochelle' => ['Rochelle', 'Brown', 'Education', '2015-09-01', 78000, '876-555-0113'],
        'marcia'   => ['Marcia', 'Williams', 'Mathematics', '2019-08-26', 81000, '876-555-0190'],
        'devon'    => ['Devon', 'Clarke', 'Mathematics', '2021-01-11', 76000, '876-555-0128'],
    ];

    // code => [name, subject, teacher, schedule, room, weekdays (1 = Mon), custom weights, topics]
    private const CLASSES = [
        'CS401' => ['Artificial Intelligence', 'Computer Science', 'reshard', 'Mon & Wed, 1:00–2:30', 'Lab 3', [1, 3], null,
            ['Search', 'Heuristics', 'Logic', 'Probability', 'Search agents', 'Neural networks']],
        'CS305' => ['Database Systems', 'Computer Science', 'reshard', 'Tue & Thu, 9:00–10:30', 'Lab 2', [2, 4],
            ['homework' => 15, 'quiz' => 15, 'project' => 40, 'exam' => 30],
            ['ER modelling', 'SQL joins', 'Normalisation', 'Indexes', 'Schema design', 'A working app']],
        'CS201' => ['Data Structures', 'Computer Science', 'randy', 'Mon, Wed & Fri, 10:00–11:00', 'B-204', [1, 3, 5], null,
            ['Arrays', 'Linked lists', 'Stacks and queues', 'Trees', 'Hash tables', 'Graph search']],
        'ED101' => ['Educational Studies', 'Education', 'rochelle', 'Tue & Thu, 1:00–2:30', 'C-110', [2, 4], null,
            ['Learning theories', 'Classroom management', 'Assessment', 'Inclusive practice', 'Lesson plan', 'Teaching portfolio']],
        'MA210' => ['Discrete Mathematics', 'Mathematics', 'marcia', 'Mon & Wed, 9:00–10:30', 'B-101', [1, 3], null,
            ['Sets', 'Proofs', 'Induction', 'Combinatorics', 'Graph theory', 'Recurrences']],
        'MA101' => ['Calculus I', 'Mathematics', 'devon', 'Tue, Thu & Fri, 11:00–12:00', 'B-102', [2, 4, 5], null,
            ['Limits', 'Derivatives', 'Chain rule', 'Optimisation', 'Related rates', 'Applications']],
    ];

    // The original app's students keep their classes.
    private const ORIGINAL_STUDENTS = [
        ['Michael', 'Peters', 21, 'CS201'], ['Mick', 'Peterson', 19, 'CS201'], ['Linda', 'Peterskin', 31, 'CS201'],
        ['Sarah', 'Michales', 21, 'CS401'], ['Elijah', 'Mickealson', 19, 'CS401'], ['Kalid', 'Kelly', 31, 'CS401'],
        ['Sarjah', 'Michelle', 22, 'ED101'], ['Derrick', 'Walcott', 25, 'ED101'], ['Venessa', 'Kelly', 24, 'ED101'],
        ['Jessica', 'Kelly', 26, 'ED101'],
    ];

    private const MORE_STUDENTS = [
        ['Aaliyah', 'Grant'], ['Andre', 'Thompson'], ['Brianna', 'Morgan'], ['Chadwick', 'Reid'], ['Danielle', 'Spencer'],
        ['Damion', 'Henry'], ['Ebony', 'Francis'], ['Gareth', 'Lewis'], ['Shanice', 'Gordon'], ['Jermaine', 'Blake'],
        ['Kerry-Ann', 'Douglas'], ['Kemar', 'Bennett'], ['Latoya', 'Simpson'], ['Marlon', 'Edwards'], ['Nadia', 'Hamilton'],
        ['Omar', 'Palmer'], ['Patrice', 'Wright'], ['Rajiv', 'Singh'], ['Renee', 'Fraser'], ['Shamar', 'Mitchell'],
        ['Tanisha', 'Allen'], ['Tyrone', 'Watson'], ['Alicia', 'Chin'], ['Brandon', 'Lee'], ['Crystal', 'Barrett'],
        ['Dwayne', 'Miller'], ['Fiona', 'Daley'], ['Horace', 'Johnson'], ['Imani', 'Stewart'], ['Javier', 'Ramos'],
        ['Keisha', 'Robinson'], ['Leon', 'McKenzie'], ['Monique', 'Clarke'], ['Nathan', 'Ellis'], ['Odessa', 'Rowe'],
        ['Peter', 'Nguyen'], ['Rochelle', 'Scott'], ['Sean', 'Murray'],
    ];

    private array $ability = [];

    public function run(): void
    {
        mt_srand(2026);
        $today = Carbon::today();

        DB::transaction(function () use ($today) {
            User::create(['name' => 'Andrea Lewis', 'email' => 'admin@lms.test', 'password' => self::PASSWORD, 'role' => User::ROLE_ADMIN]);

            $teachers = [];
            foreach (self::TEACHERS as $key => [$first, $last, $dept, $hired, $salary, $phone]) {
                $user = User::create(['name' => "$first $last", 'email' => "$key@lms.test", 'password' => self::PASSWORD, 'role' => User::ROLE_TEACHER]);
                $teacher = new Teacher([
                    'first_name' => $first, 'last_name' => $last, 'email' => "$key@lms.test", 'phone' => $phone,
                    'department' => $dept, 'hire_date' => $hired, 'salary' => $salary,
                ]);
                $teacher->user()->associate($user)->save();
                $teachers[$key] = $teacher;
            }

            $classrooms = [];
            foreach (self::CLASSES as $code => [$name, $subject, $teacherKey, $schedule, $room, $days, $weights, $topics]) {
                $classrooms[$code] = Classroom::create([
                    'name' => $name, 'code' => $code, 'subject' => $subject, 'teacher_id' => $teachers[$teacherKey]->id,
                    'schedule' => $schedule, 'room' => $room, 'term' => config('lms.current_term'), 'capacity' => 30,
                    'weights' => $weights,
                    'description' => "{$name} for {$subject} students. Weekly homework, two quizzes, a project and a final exam.",
                ]);
            }

            // Students
            $students = [];
            $n = 1;
            $year = $today->year;
            $make = function (string $first, string $last, ?int $age) use (&$n, $year, $today) {
                $age ??= mt_rand(17, 24);
                $s = Student::create([
                    'student_number' => sprintf('S%d-%04d', $year, $n++),
                    'first_name' => $first, 'last_name' => $last,
                    'email' => strtolower(preg_replace('/[^a-z]/i', '', $first).'.'.preg_replace('/[^a-z]/i', '', $last)).'@students.lms.test',
                    'date_of_birth' => $today->copy()->subYears($age)->subDays(mt_rand(0, 360)),
                    'year_level' => min(4, max(1, intdiv($age - 16, 2) + 1)),
                    'guardian_name' => $this->guardianFor($last),
                    'guardian_phone' => sprintf('876-555-%04d', mt_rand(1000, 9999)),
                    'status' => 'active',
                ]);
                $this->ability[$s->id] = $this->clamp($this->normal(80, 8), 52, 98);

                return $s;
            };

            foreach (self::ORIGINAL_STUDENTS as [$first, $last, $age, $code]) {
                $students[] = [$make($first, $last, $age), $code];
            }
            foreach (self::MORE_STUDENTS as [$first, $last]) {
                $students[] = [$make($first, $last, null), null];
            }

            // A few students who are struggling, so the dashboard has someone to flag.
            foreach ([3, 14, 27, 40] as $i) {
                $this->ability[$students[$i][0]->id] = $this->clamp($this->normal(61, 4), 50, 68);
            }

            // Enrolment: each student takes three classes; original students keep their original class.
            $codes = array_keys(self::CLASSES);
            $roster = array_fill_keys($codes, []);
            foreach ($students as [$student, $fixed]) {
                $picks = $fixed ? [$fixed] : [];
                $pool = $codes;
                usort($pool, fn ($a, $b) => count($roster[$a]) <=> count($roster[$b]) ?: mt_rand(-1, 1));
                foreach ($pool as $code) {
                    if (count($picks) >= 3) {
                        break;
                    }
                    if (! in_array($code, $picks, true) && count($roster[$code]) < 28) {
                        $picks[] = $code;
                    }
                }
                foreach ($picks as $code) {
                    $roster[$code][] = $student->id;
                }
            }

            $termStart = $today->copy()->subWeeks(7)->startOfWeek();
            foreach ($roster as $code => $ids) {
                $classrooms[$code]->students()->attach(
                    collect($ids)->mapWithKeys(fn ($id) => [$id => ['enrolled_on' => $termStart->toDateString()]])->all()
                );
            }

            // Assignments and scores
            $classIndex = 0;
            foreach (self::CLASSES as $code => $def) {
                $topics = $def[7];
                $shift = $classIndex++ % 3;
                $plan = [
                    ["Homework 1: {$topics[0]}", 'homework', 20, -45],
                    ["Homework 2: {$topics[1]}", 'homework', 20, -38],
                    ["Quiz 1: {$topics[0]} and ".lcfirst($topics[1]), 'quiz', 25, -31],
                    ["Homework 3: {$topics[2]}", 'homework', 20, -24],
                    ['Project proposal', 'project', 30, -19],
                    ['Midterm exam', 'exam', 100, -12],
                    ["Homework 4: {$topics[3]}", 'homework', 20, -2],
                    ["Quiz 2: {$topics[2]} and ".lcfirst($topics[3]), 'quiz', 25, 4],
                    ["Project: {$topics[4]}", 'project', 100, 12],
                    ["Homework 5: {$topics[5]}", 'homework', 20, 9],
                    ['Final exam', 'exam', 100, 45],
                ];
                $classOffset = $this->normal(0, 3);

                foreach ($plan as [$title, $category, $max, $offset]) {
                    $due = $today->copy()->addDays($offset - $shift);
                    $assignment = Assignment::create([
                        'classroom_id' => $classrooms[$code]->id, 'title' => $title, 'category' => $category,
                        'max_points' => $max, 'due_date' => $due,
                        'description' => $category === 'exam' ? 'Closed book. Bring a calculator and student ID.' : null,
                    ]);

                    if ($due->greaterThanOrEqualTo($today)) {
                        continue; // not due yet
                    }

                    $difficulty = $this->normal(0, 4);
                    $partlyGraded = $offset === -2; // the teacher is still working through this one
                    $rows = [];
                    foreach ($roster[$code] as $sid) {
                        if ($partlyGraded && mt_rand(1, 100) > 45) {
                            continue;
                        }
                        $roll = mt_rand(1, 1000);
                        if ($roll <= 15) {
                            $rows[] = $this->gradeRow($assignment->id, $sid, null, true);
                            continue;
                        }
                        if ($roll <= 40 + ($this->ability[$sid] < 70 ? 60 : 0)) {
                            continue; // never handed in
                        }
                        $pct = $this->clamp($this->ability[$sid] + $classOffset - $difficulty + $this->normal(0, 7), 15, 100);
                        $rows[] = $this->gradeRow($assignment->id, $sid, round($pct / 100 * $max * 2) / 2, false);
                    }
                    DB::table('grades')->insert($rows);
                }
            }

            // Attendance for every class meeting so far this term
            foreach (self::CLASSES as $code => $def) {
                $days = $def[5];
                $rows = [];
                for ($d = $termStart->copy(); $d->lessThan($today); $d->addDay()) {
                    if (! in_array($d->dayOfWeekIso, $days, true)) {
                        continue;
                    }
                    foreach ($roster[$code] as $sid) {
                        $absentChance = $this->ability[$sid] < 70 ? 16 : 4;
                        $r = mt_rand(1, 100);
                        $status = $r <= $absentChance ? 'absent' : ($r <= $absentChance + 5 ? 'late' : ($r <= $absentChance + 6 ? 'excused' : 'present'));
                        $rows[] = [
                            'classroom_id' => $classrooms[$code]->id, 'student_id' => $sid, 'date' => $d->toDateString(),
                            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('attendance_records')->insert($chunk);
                }
            }
        });
    }

    private function gradeRow(int $assignmentId, int $studentId, ?float $score, bool $excused): array
    {
        return [
            'assignment_id' => $assignmentId, 'student_id' => $studentId, 'score' => $score, 'excused' => $excused,
            'comment' => null, 'created_at' => now(), 'updated_at' => now(),
        ];
    }

    private function guardianFor(string $last): string
    {
        $firsts = ['Andrea', 'Paul', 'Marcia', 'Winston', 'Claudette', 'Everton', 'Joan', 'Leroy', 'Sonia', 'Trevor'];

        return $firsts[mt_rand(0, count($firsts) - 1)]." $last";
    }

    /** Normally distributed random number (Box–Muller). */
    private function normal(float $mean, float $sd): float
    {
        $u = max(mt_rand() / mt_getrandmax(), 1e-9);
        $v = mt_rand() / mt_getrandmax();

        return $mean + $sd * sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }

    private function clamp(float $x, float $min, float $max): float
    {
        return max($min, min($max, $x));
    }
}
