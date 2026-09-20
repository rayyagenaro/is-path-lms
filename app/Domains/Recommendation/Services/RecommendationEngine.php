<?php

namespace App\Domains\Recommendation\Services;

final class RecommendationEngine
{
    public function calculate(array $studentScores, array $requirements, array $thresholds = []): array
    {
        $thresholds += ['highly_recommended' => 80, 'recommended' => 65, 'potential_match' => 50];
        $weighted = 0.0;
        $weightTotal = 0.0;
        $details = ['strong' => [], 'moderate' => [], 'gaps' => [], 'blocking' => []];

        foreach ($requirements as $requirement) {
            $id = (int) $requirement['competency_id'];
            $score = (float) ($studentScores[$id] ?? 0);
            $weight = (float) $requirement['weight'];
            $requiredScore = (int) $requirement['minimum_level'] * 20;
            $item = [
                'competency_id' => $id,
                'name' => $requirement['name'],
                'score' => round($score, 1),
                'required' => $requiredScore,
                'gap' => max(0, round($requiredScore - $score, 1)),
                'type' => $requirement['requirement_type'],
            ];

            $weighted += $score * $weight;
            $weightTotal += $weight;

            if ($score >= 70) $details['strong'][] = $item;
            elseif ($score >= 40) $details['moderate'][] = $item;
            if ($score < $requiredScore) $details['gaps'][] = $item;
            if ($requirement['requirement_type'] === 'mandatory' && $score < $requiredScore) {
                $details['blocking'][] = $item;
            }
        }

        $score = $weightTotal > 0 ? round($weighted / $weightTotal, 1) : 0.0;
        $category = match (true) {
            count($details['blocking']) > 0 => 'Not Ready',
            $score >= $thresholds['highly_recommended'] => 'Highly Recommended',
            $score >= $thresholds['recommended'] => 'Recommended',
            $score >= $thresholds['potential_match'] => 'Potential Match',
            default => 'Low Match',
        };

        return compact('score', 'category', 'details');
    }
}
