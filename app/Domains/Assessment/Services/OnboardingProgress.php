<?php

namespace App\Domains\Assessment\Services;

use Illuminate\Support\Facades\DB;

final class OnboardingProgress
{
    public function hasCompletedInitialAssessment(int $studentId): bool
    {
        return DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)
            ->where('aa.status', 'graded')
            ->where('a.assessment_purpose', 'career_diagnostic')
            ->exists();
    }

    public function hasCompletedTargetAssessment(int $studentId, int $roleId): bool
    {
        return DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)
            ->where('aa.status', 'graded')
            ->where('a.assessment_scope', 'career_role')
            ->where('a.assessment_purpose', 'role_competency_assessment')
            ->where('a.scope_reference_id', $roleId)
            ->exists();
    }

    public function isLegacyProfileReady(int $studentId): bool
    {
        $hasTarget = DB::table('students')
            ->where('id', $studentId)
            ->whereNotNull('target_job_role_id')
            ->exists();

        if (!$hasTarget || !DB::table('career_profiles')->where('student_id', $studentId)->exists()) {
            return false;
        }

        return DB::table('student_competencies as sc')
            ->join('competency_evidences as ce', 'ce.student_competency_id', '=', 'sc.id')
            ->where('sc.student_id', $studentId)
            ->exists();
    }

    public function attemptMatchesStage(int $attemptId, int $studentId, string $purpose, ?int $roleId = null): bool
    {
        return DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.id', $attemptId)
            ->where('aa.student_id', $studentId)
            ->where('a.assessment_purpose', $purpose)
            ->when($roleId !== null, fn ($query) => $query
                ->where('a.assessment_scope', 'career_role')
                ->where('a.scope_reference_id', $roleId))
            ->exists();
    }
}
