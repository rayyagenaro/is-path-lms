<?php

namespace App\Domains\Recommendation\Services;

use Illuminate\Support\Facades\DB;

final class StudentScoreProfile
{
    public function compose(int $studentId): array
    {
        $verified = DB::table('student_competencies')->where('student_id', $studentId)->pluck('score', 'competency_id')->all();
        $profile = DB::table('career_profiles')->where('student_id', $studentId)->first();
        $scores = $verified;

        if ($profile) {
            $ratings = DB::table('career_profile_strengths')->where('career_profile_id', $profile->id)->pluck('self_rating', 'competency_id');
            foreach ($ratings as $competencyId => $rating) {
                $scores[$competencyId] = array_key_exists($competencyId, $verified)
                    ? round($verified[$competencyId] * .8 + $rating * .2, 1)
                    : (float) $rating;
            }
        }

        return ['profile' => $profile, 'scores' => $scores, 'has_verified_evidence' => $verified !== []];
    }
}
