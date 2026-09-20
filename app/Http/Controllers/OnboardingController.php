<?php

namespace App\Http\Controllers;

use App\Domains\Assessment\Services\AssessmentService;
use App\Domains\Assessment\Services\OnboardingProgress;
use App\Domains\Career\Services\CareerIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(Request $request, CareerIntelligenceService $career, OnboardingProgress $progress): View|RedirectResponse
    {
        $student = $this->student($request);
        if (!$progress->hasCompletedInitialAssessment($student->id)) {
            if ($progress->isLegacyProfileReady($student->id)) {
                return redirect()->route('dashboard');
            }

            return view('onboarding.pre-assessment', [
                'showPrompt' => (bool) $request->session()->pull('show_preassessment_prompt', false),
                'assessment' => DB::table('assessments')
                    ->where('assessment_scope', 'global')
                    ->where('assessment_purpose', 'career_diagnostic')
                    ->where('is_published', true)
                    ->select('assessments.*')
                    ->selectSub(DB::table('assessment_questions')->selectRaw('COUNT(*)')->whereColumn('assessment_questions.assessment_id', 'assessments.id'), 'question_count')
                    ->first(),
            ]);
        }
        if ($student->target_job_role_id) return redirect()->route('onboarding.assessment');

        $roles = DB::table('career_roles as r')
            ->join('career_clusters as c', 'c.id', '=', 'r.career_cluster_id')
            ->where('r.is_active', true)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('assessments as a')
                    ->whereColumn('a.scope_reference_id', 'r.id')
                    ->where('a.assessment_scope', 'career_role')
                    ->where('a.assessment_purpose', 'role_competency_assessment')
                    ->where('a.is_published', true);
            })
            ->select('r.id', 'r.name', 'r.slug', 'r.description', 'r.career_level', 'c.id as cluster_id', 'c.name as cluster_name', 'c.description as cluster_description', 'c.display_order as cluster_order')
            ->orderBy('c.display_order')
            ->orderBy('r.name')
            ->get();

        $profile = DB::table('career_profiles')->where('student_id', $student->id)->first();
        $scores = DB::table('student_competencies')->where('student_id', $student->id)->pluck('score', 'competency_id')->all();
        if ($profile) foreach (DB::table('career_profile_strengths')->where('career_profile_id', $profile->id)->pluck('self_rating', 'competency_id') as $id => $rating) {
            $scores[$id] = array_key_exists($id, $scores) ? round($scores[$id] * .8 + $rating * .2, 1) : (float) $rating;
        }
        $ranked = $career->rankedRoles($student->id, $scores)->keyBy('id');
        $roles = $roles->map(function ($role) use ($ranked) {
            $match = $ranked->get($role->id);
            $role->score = $match?->score ?? 0;
            $role->category = $match?->category ?? 'Belum terpetakan';
            $role->details = $match?->details ?? ['strong' => [], 'gaps' => []];
            return $role;
        })->sortByDesc('score')->values();

        $clusters = $roles->groupBy('cluster_id')->map(function ($items) {
            $first = $items->first();
            return (object) [
                'id' => $first->cluster_id,
                'name' => $first->cluster_name,
                'description' => $first->cluster_description,
                'order' => $first->cluster_order,
                'roles' => $items->sortByDesc('score')->values(),
            ];
        })->sortBy('order')->values();

        return view('onboarding.career-role', compact('roles', 'clusters', 'profile'));
    }

    public function startInitial(Request $request, AssessmentService $assessments, OnboardingProgress $progress): RedirectResponse
    {
        $student = $this->student($request);
        if ($progress->hasCompletedInitialAssessment($student->id) || $progress->isLegacyProfileReady($student->id)) {
            return redirect()->route('onboarding.index');
        }

        return $this->startInitialAssessment($student->id, $assessments);
    }

    public function choose(Request $request, AssessmentService $assessments, OnboardingProgress $progress): RedirectResponse
    {
        $student = $this->student($request);
        if (!$progress->hasCompletedInitialAssessment($student->id) && !$progress->isLegacyProfileReady($student->id)) {
            return redirect()->route('onboarding.index');
        }
        $data = $request->validate([
            'career_role_ids' => ['nullable', 'array', 'max:3'],
            'career_role_ids.*' => ['integer', 'distinct', 'exists:career_roles,id'],
            'career_role_id' => ['nullable', 'integer', 'exists:career_roles,id'],
            'primary_role_id' => ['nullable', 'integer', 'exists:career_roles,id'],
        ]);
        $roleIds = collect($data['career_role_ids'] ?? [])->push($data['career_role_id'] ?? null)
            ->filter()->map(fn ($id) => (int) $id)->unique()->take(3)->values();
        if ($roleIds->isEmpty()) {
            return back()->withErrors(['career_role_ids' => 'Pilih setidaknya satu peran untuk dilanjutkan.'])->withInput();
        }
        $availableIds = DB::table('career_roles as r')->whereIn('r.id', $roleIds)
            ->where('r.is_active', true)->whereExists(function ($query) {
                $query->selectRaw('1')->from('assessments as a')->whereColumn('a.scope_reference_id', 'r.id')
                    ->where('a.assessment_scope', 'career_role')->where('a.assessment_purpose', 'role_competency_assessment')->where('a.is_published', true);
            })->pluck('r.id');
        if ($availableIds->count() !== $roleIds->count()) {
            return back()->withErrors(['career_role_ids' => 'Salah satu peran tidak tersedia untuk assessment.'])->withInput();
        }
        $roleId = in_array((int) ($data['primary_role_id'] ?? 0), $roleIds->all(), true)
            ? (int) $data['primary_role_id']
            : (int) $roleIds->first();

        DB::transaction(function () use ($student, $roleId, $roleIds) {
            DB::table('student_career_interests')->where('student_id', $student->id)->where('interest_type', 'target_active')->delete();
            DB::table('student_career_interests')->updateOrInsert(
                ['student_id' => $student->id, 'career_role_id' => $roleId, 'interest_type' => 'target_active'],
                ['created_at' => now(), 'updated_at' => now()]
            );
            foreach ($roleIds->reject(fn ($id) => $id === $roleId) as $alternativeRoleId) {
                DB::table('student_career_interests')->updateOrInsert(
                    ['student_id' => $student->id, 'career_role_id' => $alternativeRoleId, 'interest_type' => 'explored'],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
            DB::table('students')->where('id', $student->id)->update(['target_job_role_id' => $roleId, 'updated_at' => now()]);
        });

        return $this->startTargetAssessment($student->id, $roleId, $assessments);
    }

    public function assessment(Request $request, AssessmentService $assessments, OnboardingProgress $progress): RedirectResponse
    {
        $student = $this->student($request);
        if (!$progress->hasCompletedInitialAssessment($student->id) && !$progress->isLegacyProfileReady($student->id)) {
            return redirect()->route('onboarding.index');
        }
        if (!$student->target_job_role_id) return redirect()->route('onboarding.index');
        if ($this->hasCompletedTargetAssessment($student->id, (int) $student->target_job_role_id)) return redirect()->route('dashboard');

        return $this->startTargetAssessment($student->id, (int) $student->target_job_role_id, $assessments);
    }

    private function startTargetAssessment(int $studentId, int $roleId, AssessmentService $assessments): RedirectResponse
    {
        $assessmentId = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)->where('aa.status', 'in_progress')
            ->where('a.assessment_scope', 'career_role')->where('a.assessment_purpose', 'role_competency_assessment')
            ->where('a.scope_reference_id', $roleId)->value('a.id');
        $assessmentId ??= DB::table('assessments')
            ->where('assessment_scope', 'career_role')
            ->where('assessment_purpose', 'role_competency_assessment')
            ->where('scope_reference_id', $roleId)
            ->where('is_published', true)
            ->value('id');

        abort_unless($assessmentId, 404, 'Assessment mandatory untuk role ini belum tersedia.');

        return redirect()->route('assessments.take', $assessments->start((int) $assessmentId, $studentId));
    }

    private function startInitialAssessment(int $studentId, AssessmentService $assessments): RedirectResponse
    {
        $assessmentId = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)->where('aa.status', 'in_progress')
            ->where('a.assessment_scope', 'global')->where('a.assessment_purpose', 'career_diagnostic')
            ->value('a.id');
        $assessmentId ??= DB::table('assessments')
            ->where('assessment_scope', 'global')
            ->where('assessment_purpose', 'career_diagnostic')
            ->where('is_published', true)
            ->value('id');
        abort_unless($assessmentId, 404, 'Assessment awal belum tersedia.');
        return redirect()->route('assessments.take', $assessments->start((int) $assessmentId, $studentId))->with('initial_onboarding', true);
    }

    private function hasCompletedTargetAssessment(int $studentId, int $roleId): bool
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

    private function student(Request $request): object
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first();
        abort_unless($student, 403);
        return $student;
    }
}
