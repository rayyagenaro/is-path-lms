<?php

namespace App\Http\Controllers;

use App\Domains\Assessment\Services\AssessmentService;
use App\Domains\Competency\Services\CompetencyScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompetencyEvidenceController extends Controller
{
    public function show(Request $request, int $competency, AssessmentService $assessments, CompetencyScoreCalculator $calculator): View
    {
        $studentId = DB::table('students')->where('user_id', $request->user()->id)->value('id');
        abort_unless($studentId, 403);
        $skill = DB::table('competencies')->where('id', $competency)->where('is_active', true)->first();
        abort_unless($skill, 404);
        $profile = DB::table('student_competencies')->where('student_id', $studentId)->where('competency_id', $competency)->first();
        $evidence = $profile ? DB::table('competency_evidences')->where('student_competency_id', $profile->id)->orderByDesc('earned_at')->get() : collect();
        $valid = $evidence->filter(fn ($item) => !$item->valid_until || now()->lte($item->valid_until));
        $calculated = $calculator->calculate($evidence->map(fn ($item) => (array) $item)->all(), $skill->expected_evidence_count);
        $matchingIds = DB::table('question_competencies as qc')->join('assessment_questions as q', 'q.id', '=', 'qc.assessment_question_id')
            ->where('qc.competency_id', $competency)->distinct()->pluck('q.assessment_id');
        $available = $assessments->availableFor((int) $studentId)->whereIn('id', $matchingIds)->filter(fn ($item) => $item->can_attempt)->values();
        $courses = DB::table('courses as c')->join('course_competencies as cc', 'cc.course_id', '=', 'c.id')
            ->where('cc.competency_id', $competency)->where('c.status', 'published')->select('c.id', 'c.title', 'c.slug')->distinct()->get();
        $attempts = DB::table('assessment_attempts as aa')->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)->where('aa.status', 'graded')->select('aa.id', 'a.title')->get()->keyBy('id');
        $projects = DB::table('projects as p')->join('project_competencies as pc', 'pc.project_id', '=', 'p.id')
            ->where('pc.competency_id', $competency)->where('p.is_published', true)->select('p.*')->get();
        $submissions = DB::table('project_submissions')->where('student_id', $studentId)->whereIn('project_id', $projects->pluck('id'))->latest('id')->get()->groupBy('project_id');
        return view('competencies.evidence', compact('skill', 'profile', 'evidence', 'valid', 'calculated', 'available', 'courses', 'attempts', 'projects', 'submissions'));
    }

    public function storeProject(Request $request, int $competency, int $project): RedirectResponse
    {
        $studentId = DB::table('students')->where('user_id', $request->user()->id)->value('id');
        abort_unless($studentId, 403);
        abort_unless(DB::table('projects as p')->join('project_competencies as pc', 'pc.project_id', '=', 'p.id')
            ->where('p.id', $project)->where('p.is_published', true)->where('pc.competency_id', $competency)->exists(), 404);
        $data = $request->validate(['submission_ref' => ['required', 'url:http,https', 'max:255'], 'student_note' => ['required', 'string', 'max:3000']]);
        DB::table('project_submissions')->insert([
            'student_id' => $studentId, 'project_id' => $project,
            'submission_ref' => $data['submission_ref'], 'student_note' => $data['student_note'],
            'status' => 'submitted', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('competencies.evidence', $competency)->with('success', 'Referensi proyek tersimpan. Nilai kompetensi belum berubah karena proyek belum dinilai.');
    }
}
