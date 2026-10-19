<?php

/*
|--------------------------------------------------------------------------
| LMS settings
|--------------------------------------------------------------------------
| School-wide grading rules. Each classroom can override the category
| weights from its settings page; everything else applies everywhere.
*/

return [

    'school_name' => env('LMS_SCHOOL_NAME', 'Innovation Learning Center'),

    'current_term' => env('LMS_CURRENT_TERM', 'Fall 2026'),

    // Assignment categories and their default share of the final grade (must add up to 100).
    'categories' => [
        'homework' => ['label' => 'Homework', 'weight' => 20],
        'quiz'     => ['label' => 'Quizzes',  'weight' => 20],
        'project'  => ['label' => 'Projects', 'weight' => 20],
        'exam'     => ['label' => 'Exams',    'weight' => 40],
    ],

    // Lowest percentage for each letter grade, highest first.
    'letter_grades' => [
        'A' => 90,
        'B' => 80,
        'C' => 70,
        'D' => 60,
        'F' => 0,
    ],

    // Students below this percentage in a class are flagged on the dashboard.
    'at_risk_below' => 70,

    // Attendance statuses, and whether each one counts as attending.
    'attendance_statuses' => [
        'present' => ['label' => 'Present', 'attended' => true],
        'late'    => ['label' => 'Late',    'attended' => true],
        'absent'  => ['label' => 'Absent',  'attended' => false],
        'excused' => ['label' => 'Excused', 'attended' => null], // not counted either way
    ],
];
