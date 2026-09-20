<?php

namespace App\Http\Controllers;

use App\Domains\Assessment\Services\AssessmentService;
use App\Domains\Recommendation\Services\RecommendationWorkflowService;
use App\Http\Requests\SubmitAssessmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request, AssessmentService $service): View
    {
        $studentId = $this->studentId($request);
        $assessments = $service->availableFor($studentId);
        return view('assessments.index', compact('assessments'));
    }

    public function start(Request $request, int $assessment, AssessmentService $service): RedirectResponse
    {
        $attemptId = $service->start($assessment, $this->studentId($request));
        return redirect()->route('assessments.take', $attemptId);
    }

    public function take(Request $request, int $attempt, AssessmentService $service): View|RedirectResponse
    {
        $data = $service->attemptFor($attempt, $this->studentId($request));
        if ($data['attempt']->status !== 'in_progress') {
            return redirect()->route('assessments.result', $attempt);
        }
        return view('assessments.take', $data);
    }

    public function submit(SubmitAssessmentRequest $request, int $attempt, AssessmentService $service, RecommendationWorkflowService $workflow): RedirectResponse
    {
        $studentId = $this->studentId($request);
        $service->submit($attempt, $studentId, $request->validated('answers'));
        $workflow->refresh($studentId);
        $purpose = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.id', $attempt)
            ->value('a.assessment_purpose');
        if ($purpose === 'career_diagnostic') {
            $request->session()->forget('initial_onboarding');
            return redirect()->route('onboarding.index')->with('success', 'Pre-assessment selesai. Pilihan role sudah diurutkan berdasarkan hasilmu.');
        }
        return redirect()->route('assessments.result', $attempt)->with('success', 'Assessment selesai. Profil kompetensimu sudah diperbarui.');
    }

    public function result(Request $request, int $attempt, AssessmentService $service): View
    {
        return view('assessments.result', $service->resultFor($attempt, $this->studentId($request)));
    }

    public function saveDraft(Request $request, int $attempt, AssessmentService $service): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['answers' => ['required', 'array', 'max:500'], 'answers.*' => ['required', 'string', 'max:50']]);
        $service->saveDraft($attempt, $this->studentId($request), $data['answers']);
        return response()->json(['saved_at' => now()->toIso8601String()]);
    }

    private function studentId(Request $request): int
    {
        $studentId = DB::table('students')->where('user_id', $request->user()->id)->value('id');
        abort_unless($studentId, 403);
        return (int) $studentId;
    }
}
