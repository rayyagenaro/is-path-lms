<?php

namespace App\Http\Middleware;

use App\Domains\Assessment\Services\OnboardingProgress;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function __construct(private readonly OnboardingProgress $progress) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('logout', 'onboarding.*')) return $next($request);

        $student = DB::table('students')->where('user_id', $request->user()->id)->first();
        if (!$student) abort(403);

        if ($request->routeIs('assessments.take', 'assessments.submit', 'assessments.result', 'assessments.draft')) {
            $attemptId = (int) $request->route('attempt');
            if ($attemptId > 0 && !DB::table('assessment_attempts')->where('id', $attemptId)->where('student_id', $student->id)->exists()) {
                abort(403);
            }
        }

        if ($this->progress->isLegacyProfileReady($student->id)) return $next($request);

        if (!$this->progress->hasCompletedInitialAssessment($student->id)) {
            if ($this->isAssessmentAttemptFor($request, $student->id, 'career_diagnostic')) return $next($request);
            return redirect()->route('onboarding.index');
        }

        if (!$student->target_job_role_id) return redirect()->route('onboarding.index');

        if (!$this->progress->hasCompletedTargetAssessment($student->id, (int) $student->target_job_role_id)) {
            if ($this->isAssessmentAttemptFor($request, $student->id, 'role_competency_assessment', (int) $student->target_job_role_id)) return $next($request);
            return redirect()->route('onboarding.assessment');
        }

        return $next($request);
    }

    private function isAssessmentAttemptFor(Request $request, int $studentId, string $purpose, ?int $roleId = null): bool
    {
        if (!$request->routeIs('assessments.take', 'assessments.submit', 'assessments.result', 'assessments.draft')) return false;

        $attemptId = (int) $request->route('attempt');
        return $attemptId > 0 && $this->progress->attemptMatchesStage($attemptId, $studentId, $purpose, $roleId);
    }
}
