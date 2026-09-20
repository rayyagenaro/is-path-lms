<?php

namespace App\Domains\Assessment\Services;

use App\Domains\Competency\Services\CompetencyScoreCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AssessmentService
{
    public function __construct(private readonly CompetencyScoreCalculator $scoreCalculator) {}

    public function availableFor(int $studentId): Collection
    {
        $assessments = DB::table('assessments as a')
            ->leftJoin('courses as c', 'c.id', '=', 'a.course_id')
            ->where(function ($query) use ($studentId) {
                $query->where('a.is_published', true)->orWhereExists(function ($attempt) use ($studentId) {
                    $attempt->selectRaw('1')->from('assessment_attempts as open_attempt')
                        ->whereColumn('open_attempt.assessment_id', 'a.id')
                        ->where('open_attempt.student_id', $studentId)->where('open_attempt.status', 'in_progress');
                });
            })
            ->where(fn ($query) => $query->whereNull('a.opens_at')->orWhere('a.opens_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('a.closes_at')->orWhere('a.closes_at', '>=', now()))
            ->where(fn ($query) => $query->where('a.assessment_scope', '!=', 'career_role')->orWhereIn('a.scope_reference_id', $this->visibleRoleIds($studentId)))
            ->select('a.*', 'c.title as course_title')
            ->orderByRaw("CASE WHEN a.assessment_purpose IN ('career_diagnostic', 'competency_post_assessment') THEN 0 WHEN a.assessment_purpose = 'role_competency_assessment' THEN 1 ELSE 2 END")
            ->orderBy('a.title')
            ->get();

        $attempts = DB::table('assessment_attempts')
            ->where('student_id', $studentId)
            ->orderByDesc('attempt_number')
            ->get()
            ->groupBy('assessment_id');

        return $assessments->map(function ($assessment) use ($attempts) {
            $history = $attempts->get($assessment->id, collect());
            $assessment->question_count = DB::table('assessment_questions')->where('assessment_id', $assessment->id)->count();
            $assessment->attempt_count = $history->count();
            $assessment->latest_attempt = $history->first();
            $assessment->attempt_history = $history->where('status', 'graded')->values();
            $assessment->best_score = $history->where('status', 'graded')->max('score');
            $assessment->can_attempt = $history->contains('status', 'in_progress') || ($assessment->is_published && $assessment->attempt_count < $assessment->max_attempts);
            return $assessment;
        });
    }

    public function start(int $assessmentId, int $studentId): int
    {
        return DB::transaction(function () use ($assessmentId, $studentId) {
            $assessment = DB::table('assessments')->where('id', $assessmentId)->lockForUpdate()->first();
            abort_unless($assessment, 404);

            $inProgress = DB::table('assessment_attempts')
                ->where('assessment_id', $assessmentId)->where('student_id', $studentId)
                ->where('status', 'in_progress')->first();
            if ($inProgress) return (int) $inProgress->id;
            abort_unless($assessment->is_published, 404);

            if ($assessment->opens_at && now()->lt($assessment->opens_at)) {
                throw ValidationException::withMessages(['assessment' => 'Assessment ini belum dibuka.']);
            }
            if ($assessment->closes_at && now()->gt($assessment->closes_at)) {
                throw ValidationException::withMessages(['assessment' => 'Periode assessment ini sudah berakhir.']);
            }

            $attemptCount = DB::table('assessment_attempts')
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $studentId)
                ->count();
            if ($attemptCount >= $assessment->max_attempts) {
                throw ValidationException::withMessages(['assessment' => 'Batas percobaan assessment sudah tercapai.']);
            }

            return DB::table('assessment_attempts')->insertGetId([
                'assessment_id' => $assessmentId,
                'student_id' => $studentId,
                'idempotency_key' => (string) Str::uuid(),
                'status' => 'in_progress',
                'attempt_number' => $attemptCount + 1,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function attemptFor(int $attemptId, int $studentId): array
    {
        $attempt = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.id', $attemptId)
            ->select('aa.*', 'a.title', 'a.passing_score', 'a.time_limit_minutes', 'a.max_attempts', 'a.assessment_purpose', 'a.assessment_scope', 'a.scope_reference_id')
            ->first();
        abort_unless($attempt, 404);
        if ((int) $attempt->student_id !== $studentId) throw new AuthorizationException();

        $questions = DB::table('assessment_questions')
            ->where('assessment_id', $attempt->assessment_id)
            ->orderBy('position')
            ->get()
            ->map(function ($question) use ($attemptId) {
                $question->options = json_decode($question->options ?: '{}', true);
                // Stable display order per attempt; stored answer keys stay unchanged.
                uksort($question->options, fn ($a, $b) => strcmp(
                    hash('sha256', $attemptId.':'.$question->id.':'.$a),
                    hash('sha256', $attemptId.':'.$question->id.':'.$b)
                ));
                return $question;
            });

        $draftRows = DB::table('assessment_answers')->where('assessment_attempt_id', $attemptId)->get();
        $draftAnswers = $draftRows->mapWithKeys(fn ($row) => [$row->assessment_question_id => json_decode($row->answer ?: '{}', true)['selected'] ?? null])->all();
        $draftSavedAt = $draftRows->max('updated_at');
        return compact('attempt', 'questions', 'draftAnswers', 'draftSavedAt');
    }

    public function saveDraft(int $attemptId, int $studentId, array $answers): void
    {
        DB::transaction(function () use ($attemptId, $studentId, $answers) {
            $attempt = DB::table('assessment_attempts')->where('id', $attemptId)->lockForUpdate()->first();
            abort_unless($attempt, 404);
            if ((int) $attempt->student_id !== $studentId) throw new AuthorizationException();
            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages(['answers' => 'Assessment sudah dikirim. Draft tidak dapat diubah.']);
            }
            $questions = DB::table('assessment_questions')->where('assessment_id', $attempt->assessment_id)->get()->keyBy('id');
            foreach ($answers as $questionId => $selected) {
                $question = $questions->get($questionId);
                if (!$question || !array_key_exists($selected, json_decode($question->options ?: '{}', true))) {
                    throw ValidationException::withMessages(['answers' => 'Soal atau pilihan tidak sesuai assessment ini.']);
                }
            }
            foreach ($answers as $questionId => $selected) {
                DB::table('assessment_answers')->updateOrInsert(
                    ['assessment_attempt_id' => $attemptId, 'assessment_question_id' => $questionId],
                    ['answer' => json_encode(['selected' => $selected]), 'score' => null, 'feedback' => null, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });
    }

    public function submit(int $attemptId, int $studentId, array $submittedAnswers): void
    {
        DB::transaction(function () use ($attemptId, $studentId, $submittedAnswers) {
            $attempt = DB::table('assessment_attempts')->where('id', $attemptId)->lockForUpdate()->first();
            abort_unless($attempt, 404);
            if ((int) $attempt->student_id !== $studentId) throw new AuthorizationException();
            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages(['assessment' => 'Assessment ini sudah dikirim dan tidak dapat diubah.']);
            }

            $questions = DB::table('assessment_questions')
                ->where('assessment_id', $attempt->assessment_id)
                ->orderBy('position')
                ->get();
            $missing = $questions->filter(fn ($question) => !array_key_exists((string) $question->id, $submittedAnswers));
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages(['answers' => 'Jawab semua pertanyaan sebelum mengirim assessment.']);
            }

            $earnedTotal = 0.0;
            $maximumTotal = 0.0;
            foreach ($questions as $question) {
                $options = json_decode($question->options ?: '{}', true);
                $key = json_decode($question->answer_key ?: '{}', true);
                $selected = (string) $submittedAnswers[(string) $question->id];
                if (!array_key_exists($selected, $options)) {
                    throw ValidationException::withMessages(['answers.'.$question->id => 'Pilihan jawaban tidak valid.']);
                }

                $correct = hash_equals((string) ($key['correct'] ?? ''), $selected);
                $score = $correct ? (float) $question->max_score : 0.0;
                $earnedTotal += $score;
                $maximumTotal += (float) $question->max_score;

                DB::table('assessment_answers')->updateOrInsert(
                    ['assessment_attempt_id' => $attemptId, 'assessment_question_id' => $question->id],
                    [
                        'answer' => json_encode(['selected' => $selected]),
                        'score' => $score,
                        'feedback' => ($correct ? 'Jawaban tepat. ' : '').($key['explanation'] ?? 'Pelajari kembali konsep pada pertanyaan ini.'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $percentage = $maximumTotal > 0 ? round($earnedTotal / $maximumTotal * 100, 2) : 0;
            DB::table('assessment_attempts')->where('id', $attemptId)->update([
                'status' => 'graded',
                'score' => $percentage,
                'submitted_at' => now(),
                'graded_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recordCompetencyEvidence($attemptId, $studentId);
        });
    }

    public function resultFor(int $attemptId, int $studentId): array
    {
        $data = $this->attemptFor($attemptId, $studentId);
        $attempt = $data['attempt'];
        abort_if($attempt->status === 'in_progress', 404);

        $answers = DB::table('assessment_answers as an')
            ->join('assessment_questions as q', 'q.id', '=', 'an.assessment_question_id')
            ->leftJoin('question_competencies as qc', 'qc.assessment_question_id', '=', 'q.id')
            ->leftJoin('competencies as c', 'c.id', '=', 'qc.competency_id')
            ->where('an.assessment_attempt_id', $attemptId)
            ->select('an.*', 'q.prompt', 'q.options', 'q.answer_key', 'q.max_score', 'q.position', 'c.id as competency_id', 'c.name as competency_name')
            ->orderBy('q.position')
            ->get()
            ->map(function ($answer) {
                $answerData = json_decode($answer->answer ?: '{}', true);
                $options = json_decode($answer->options ?: '{}', true);
                $key = json_decode($answer->answer_key ?: '{}', true);
                $answer->selected_key = $answerData['selected'] ?? null;
                $answer->selected_label = $options[$answer->selected_key] ?? 'Tidak dijawab';
                $answer->correct_key = $key['correct'] ?? null;
                $answer->correct_label = $options[$answer->correct_key] ?? '';
                $answer->is_correct = (float) $answer->score >= (float) $answer->max_score;
                return $answer;
            });

        $breakdown = $answers->groupBy('competency_id')->map(function (Collection $items) {
            $maximum = (float) $items->sum('max_score');
            return (object) [
                'competency_id' => $items->first()->competency_id,
                'name' => $items->first()->competency_name,
                'score' => $maximum > 0 ? (int) round($items->sum('score') / $maximum * 100) : 0,
                'correct' => $items->where('is_correct', true)->count(),
                'total' => $items->count(),
            ];
        })->values();

        $roleReadiness = collect();
        if ($attempt->assessment_scope === 'career_role' && $attempt->scope_reference_id) {
            $requirements = DB::table('career_role_competencies as crc')
                ->join('competencies as c', 'c.id', '=', 'crc.competency_id')
                ->where('crc.job_role_id', $attempt->scope_reference_id)
                ->select('crc.*', 'c.name')
                ->get()
                ->keyBy('competency_id');

            $roleReadiness = $breakdown->map(function ($item) use ($requirements) {
                $requirement = $requirements->get($item->competency_id);
                $minimumScore = $requirement ? min(100, (int) $requirement->minimum_level * 20) : (int) 70;
                $item->minimum_score = $minimumScore;
                $item->requirement_type = $requirement->requirement_type ?? 'recommended';
                $item->gap = max(0, $minimumScore - $item->score);
                $item->ready = $item->gap === 0;
                return $item;
            })->sortByDesc(fn ($item) => $item->gap)->values();
        }

        return compact('attempt', 'answers', 'breakdown', 'roleReadiness');
    }

    private function visibleRoleIds(int $studentId): array
    {
        $target = DB::table('students')->where('id', $studentId)->value('target_job_role_id');
        if ($target) return [(int) $target];

        $hasPostAssessment = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)
            ->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])
            ->exists();

        if (!$hasPostAssessment) return [];

        return DB::table('career_recommendations')
            ->where('student_id', $studentId)
            ->orderByDesc('match_score')
            ->limit(3)
            ->pluck('job_role_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function recordCompetencyEvidence(int $attemptId, int $studentId): void
    {
        $results = DB::table('assessment_answers as an')
            ->join('assessment_questions as q', 'q.id', '=', 'an.assessment_question_id')
            ->join('question_competencies as qc', 'qc.assessment_question_id', '=', 'q.id')
            ->join('competencies as c', 'c.id', '=', 'qc.competency_id')
            ->where('an.assessment_attempt_id', $attemptId)
            ->select('c.id as competency_id', 'c.expected_evidence_count', 'an.score', 'q.max_score', 'qc.weight')
            ->get()
            ->groupBy('competency_id');

        foreach ($results as $competencyId => $items) {
            $earned = $items->sum(fn ($item) => (float) $item->score * (float) $item->weight);
            $maximum = $items->sum(fn ($item) => (float) $item->max_score * (float) $item->weight);
            $evidenceScore = $maximum > 0 ? round($earned / $maximum * 100, 2) : 0;

            $studentCompetencyId = DB::table('student_competencies')
                ->where('student_id', $studentId)
                ->where('competency_id', $competencyId)
                ->value('id');
            if (!$studentCompetencyId) {
                $studentCompetencyId = DB::table('student_competencies')->insertGetId([
                    'student_id' => $studentId,
                    'competency_id' => $competencyId,
                    'score' => 0,
                    'confidence_score' => 0,
                    'proficiency_level' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('competency_evidences')->updateOrInsert(
                ['student_competency_id' => $studentCompetencyId, 'source_type' => 'assessment_attempt', 'source_id' => $attemptId],
                [
                    'evidence_type' => 'technical_assessment',
                    'score' => $evidenceScore,
                    'weight' => 70,
                    'notes' => 'Hasil pemetaan kompetensi yang dinilai otomatis.',
                    'earned_at' => now(),
                    'valid_until' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $evidences = DB::table('competency_evidences')
                ->where('student_competency_id', $studentCompetencyId)
                ->get()
                ->map(fn ($evidence) => (array) $evidence)
                ->all();
            $calculated = $this->scoreCalculator->calculate($evidences, (int) $items->first()->expected_evidence_count);

            DB::table('student_competencies')->where('id', $studentCompetencyId)->update([
                'score' => $calculated['score'],
                'confidence_score' => $calculated['confidence'],
                'proficiency_level' => $calculated['level'],
                'calculated_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('competency_score_histories')->insert([
                'student_competency_id' => $studentCompetencyId,
                'score' => $calculated['score'],
                'confidence_score' => $calculated['confidence'],
                'recorded_at' => now(),
            ]);
        }
    }
}
