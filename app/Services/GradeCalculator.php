<?php

namespace App\Services;

/**
 * Pure grading math, independent of the database so it is easy to test.
 *
 * Each entry is ['category' => string, 'score' => ?float, 'max' => float, 'excused' => bool].
 * Ungraded (score null) and excused entries are ignored. A category's percentage is
 * points earned / points possible. The final grade is the weighted mean of the categories
 * that have graded work, re-normalised so missing categories don't drag the grade down.
 */
class GradeCalculator
{
    /** @return array<string, float> category => percentage (0-100), only for categories with graded work */
    public static function categoryPercents(array $entries): array
    {
        $earned = [];
        $possible = [];

        foreach ($entries as $e) {
            if (($e['excused'] ?? false) || $e['score'] === null || $e['max'] <= 0) {
                continue;
            }
            $cat = $e['category'];
            $earned[$cat] = ($earned[$cat] ?? 0) + $e['score'];
            $possible[$cat] = ($possible[$cat] ?? 0) + $e['max'];
        }

        $out = [];
        foreach ($possible as $cat => $max) {
            $out[$cat] = $earned[$cat] / $max * 100;
        }

        return $out;
    }

    public static function weightedPercent(array $entries, array $weights): ?float
    {
        $percents = self::categoryPercents($entries);

        $sum = 0.0;
        $weightUsed = 0.0;
        foreach ($percents as $cat => $pct) {
            $w = (float) ($weights[$cat] ?? 0);
            if ($w <= 0) {
                continue;
            }
            $sum += $pct * $w;
            $weightUsed += $w;
        }

        return $weightUsed > 0 ? $sum / $weightUsed : null;
    }

    public static function letter(?float $percent): ?string
    {
        if ($percent === null) {
            return null;
        }

        foreach (config('lms.letter_grades') as $letter => $min) {
            if (round($percent, 2) >= $min) {
                return $letter;
            }
        }

        return array_key_last(config('lms.letter_grades'));
    }

    public static function isAtRisk(?float $percent): bool
    {
        return $percent !== null && $percent < config('lms.at_risk_below');
    }

    /** Share of sessions attended, ignoring excused absences. Null when nothing is recorded. */
    public static function attendanceRate(array $statusCounts): ?float
    {
        $attended = 0;
        $counted = 0;

        foreach (config('lms.attendance_statuses') as $status => $meta) {
            $n = $statusCounts[$status] ?? 0;
            if ($meta['attended'] === null) {
                continue;
            }
            $counted += $n;
            if ($meta['attended']) {
                $attended += $n;
            }
        }

        return $counted > 0 ? $attended / $counted * 100 : null;
    }
}
