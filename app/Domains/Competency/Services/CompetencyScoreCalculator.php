<?php

namespace App\Domains\Competency\Services;

use Carbon\CarbonInterface;

final class CompetencyScoreCalculator
{
    public function calculate(array $evidences, int $expectedMinimum = 3): array
    {
        if ($evidences === []) return ['score' => 0.0, 'confidence' => 0.0, 'level' => 0];

        $valid = array_values(array_filter($evidences, fn ($e) => empty($e['valid_until']) || now()->lte($e['valid_until'])));
        if ($valid === []) return ['score' => 0.0, 'confidence' => 0.0, 'level' => 0];

        $weightTotal = array_sum(array_column($valid, 'weight'));
        $score = $weightTotal > 0
            ? array_sum(array_map(fn ($e) => $e['score'] * $e['weight'], $valid)) / $weightTotal
            : 0;

        $hasNonSelf = count(array_filter($valid, fn ($e) => $e['evidence_type'] !== 'self_assessment')) > 0;
        if (!$hasNonSelf) $score = min($score, 40);

        $types = count(array_unique(array_column($valid, 'evidence_type')));
        $latest = max(array_map(fn ($e) => strtotime((string) $e['earned_at']), $valid));
        $ageMonths = now()->diffInMonths(date('Y-m-d H:i:s', $latest));
        $recency = $ageMonths < 6 ? 30 : ($ageMonths <= 12 ? 20 : 10);
        $confidence = min(count($valid) / max($expectedMinimum, 1), 1) * 40 + ($types / 5) * 30 + $recency;

        return [
            'score' => round($score, 1),
            'confidence' => round(min($confidence, 100), 1),
            'level' => $score <= 0 ? 0 : min(5, (int) ceil($score / 20)),
        ];
    }
}
