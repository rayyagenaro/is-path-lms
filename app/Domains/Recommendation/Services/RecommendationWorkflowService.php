<?php

namespace App\Domains\Recommendation\Services;

use App\Domains\Career\Services\CareerIntelligenceService;
use App\Domains\Career\Services\LearningPathService;
use App\Domains\Ontology\Services\SwrlReasoningService;
use Illuminate\Support\Facades\DB;

final class RecommendationWorkflowService
{
    public function __construct(
        private readonly StudentScoreProfile $scoreProfile,
        private readonly CareerIntelligenceService $career,
        private readonly SwrlReasoningService $reasoner,
        private readonly LearningPathService $learningPath,
    ) {}

    public function refresh(int $studentId): array
    {
        $context = $this->scoreProfile->compose($studentId);
        $hasPostAssessment = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)
            ->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])
            ->exists();
        if (!$hasPostAssessment) {
            return ['roles' => collect(), 'stored' => 0, 'status' => 'locked_until_post_assessment'];
        }
        $roles = $this->career->rankedRoles($studentId, $context['scores']);
        $ruleVersionId = DB::table('recommendation_rule_versions')->where('is_active', true)->latest('published_at')->value('id');
        $stored = 0;

        if ($ruleVersionId) {
            foreach ($roles->take(5) as $rank => $role) {
                $reasoning = $this->reasoner->reasonForCareer($studentId, $role);
                DB::table('career_recommendations')->insert([
                    'student_id' => $studentId, 'job_role_id' => $role->id, 'rule_version_id' => $ruleVersionId,
                    'match_score' => $role->score, 'category' => $role->category,
                    'explanation_snapshot' => json_encode([
                        'rank' => $rank + 1, 'basis' => $context['has_verified_evidence'] ? 'verified_and_profile' : 'profile_initial',
                        'strong' => $role->details['strong'], 'gaps' => $role->details['gaps'], 'blocking' => $role->details['blocking'],
                        'readiness' => $role->readiness, 'ontology' => $reasoning,
                    ]),
                    'generated_at' => now(),
                ]);
                $stored++;
            }
        }

        $targetRoleId = DB::table('students')->where('id', $studentId)->value('target_job_role_id');
        if ($targetRoleId) $this->learningPath->regenerate($studentId, (int) $targetRoleId);
        DB::table('learning_events')->insert(['event_type' => 'recommendation_recalculated', 'subject_type' => 'student', 'subject_id' => $studentId, 'payload' => json_encode(['recommendations_stored' => $stored]), 'occurred_at' => now()]);

        return ['roles' => $roles, 'stored' => $stored];
    }
}
