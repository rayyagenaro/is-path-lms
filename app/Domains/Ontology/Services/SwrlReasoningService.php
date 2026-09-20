<?php

namespace App\Domains\Ontology\Services;

use Illuminate\Support\Facades\DB;

final class SwrlReasoningService
{
    public function __construct(private readonly OntologyRepository $ontology) {}

    public function reasonForCareer(int $studentId, object $role): array
    {
        try {
            $summary = $this->ontology->summary();
        } catch (\Throwable $exception) {
            return [
                'engine' => 'SWRL application bridge',
                'status' => 'unavailable',
                'message' => $exception->getMessage(),
                'post_assessment_completed' => false,
                'fired_rules' => [],
                'inferred_facts' => [],
            ];
        }
        $fired = [];
        $facts = [];
        $hasPostAssessment = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)
            ->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])
            ->exists();

        if ($hasPostAssessment && $this->fire('R07', $fired)) $facts[] = 'completedPostAssessment';
        if ($hasPostAssessment && $this->fire('R08', $fired)) $facts[] = 'postAssessmentEvaluatedCompetency';
        if ($hasPostAssessment && $this->fire('R16', $fired)) $facts[] = 'careerCandidateAfterPostAssessment';

        $blocking = count($role->details['blocking'] ?? []);
        $gaps = count($role->details['gaps'] ?? []);
        if ($gaps > 0 && $this->fire('R13', $fired)) $facts[] = 'hasCareerRequirementGap';
        if ($blocking > 0 && $this->fire('R14', $fired)) $facts[] = 'hasMandatoryGapForCareer';
        if ($gaps > $blocking && $this->fire('R15', $fired)) $facts[] = 'hasRecommendedGapForCareer';
        if ($hasPostAssessment && $gaps > 0 && $this->fire('R17', $fired)) $facts[] = 'developmentNeededForCareer';
        if ($gaps > 0 && $this->fire('R21', $fired)) $facts[] = 'hasCandidateCourse';

        $categoryRule = match (true) {
            (float) $role->score >= 80 => 'R22',
            (float) $role->score >= 65 => 'R23',
            default => 'R24',
        };
        if ($this->fire($categoryRule, $fired)) $facts[] = 'recommendationCategory';

        return [
            'engine' => 'SWRL application bridge',
            'status' => 'ready',
            'ontology_hash' => $summary['hash'],
            'ontology_modified_at' => $summary['modified_at'],
            'post_assessment_completed' => $hasPostAssessment,
            'fired_rules' => array_values(array_unique($fired)),
            'inferred_facts' => array_values(array_unique($facts)),
        ];
    }

    private function fire(string $rule, array &$fired): bool
    {
        if (!$this->ontology->hasEnabledRule($rule)) return false;
        $fired[] = $rule;
        return true;
    }
}
