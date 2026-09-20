<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_can_open_and_start_a_published_assessment(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $assessmentId = DB::table('assessments')->where('title', 'Pre-Assessment Kecocokan Karier SI')->value('id');
        $questionCount = DB::table('assessment_questions')->where('assessment_id', $assessmentId)->count();

        $this->actingAs($user)
            ->get('/assessments')
            ->assertOk()
            ->assertSee('Pre-Assessment Kecocokan Karier SI')
            ->assertSee('Pertanyaan</dt><dd>'.$questionCount, false);

        $response = $this->actingAs($user)->post('/assessments/'.$assessmentId.'/start');
        $attempt = DB::table('assessment_attempts')->latest('id')->first();

        $response->assertRedirect('/assessments/attempts/'.$attempt->id);
        $this->assertSame('in_progress', $attempt->status);
        $this->assertSame(1, $attempt->attempt_number);

        $this->actingAs($user)
            ->get('/assessments/attempts/'.$attempt->id)
            ->assertOk()
            ->assertSee('Bagian 1 dari '.(int) ceil($questionCount / 5))
            ->assertSee('Nomor soal')
            ->assertSee('data-question-jump="1"', false);
    }

    public function test_role_assessment_revision_preserves_old_attempts_and_uses_specific_questions(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $studentId = DB::table('students')->where('user_id', $user->id)->value('id');
        $old = DB::table('assessments')->where('assessment_scope', 'career_role')->where('is_published', true)->first();
        $oldPrompt = DB::table('assessment_questions')->where('assessment_id', $old->id)->orderBy('position')->value('prompt');
        $attemptId = DB::table('assessment_attempts')->insertGetId([
            'assessment_id'=>$old->id, 'student_id'=>$studentId, 'idempotency_key'=>(string) \Illuminate\Support\Str::uuid(),
            'status'=>'in_progress', 'attempt_number'=>1, 'started_at'=>now(), 'created_at'=>now(), 'updated_at'=>now(),
        ]);

        $this->artisan('assessment:publish-role-v2')->assertSuccessful();
        $this->artisan('assessment:publish-role-v2')->assertSuccessful();

        $new = DB::table('assessments')->where('scope_reference_id', $old->scope_reference_id)
            ->where('title', 'like', 'Pemetaan Topik %')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('assessment_questions as q')
                ->whereColumn('q.assessment_id', 'assessments.id')->where('q.prompt', 'not like', 'SECTION -%'))->first();
        $this->assertNotNull($new);
        $this->assertFalse((bool) DB::table('assessments')->where('id', $old->id)->value('is_published'));
        $this->assertSame($oldPrompt, DB::table('assessment_questions')->where('assessment_id', $old->id)->orderBy('position')->value('prompt'));
        $this->assertGreaterThanOrEqual(1, DB::table('assessments')->where('title', $new->title)->count());
        $this->assertSame(0, DB::table('assessment_questions')->where('assessment_id', $new->id)->where('prompt', 'like', 'SECTION -%')->count());
        $expected = DB::table('career_role_competencies')->where('job_role_id', $old->scope_reference_id)->count();
        $this->assertSame($expected, DB::table('assessment_questions')->where('assessment_id', $new->id)->count());
        $this->assertSame($attemptId, app(\App\Domains\Assessment\Services\AssessmentService::class)->start($old->id, $studentId));
    }

    public function test_completed_assessment_creates_evidence_and_updates_student_competencies(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $studentId = DB::table('students')->where('user_id', $user->id)->value('id');
        $assessmentId = DB::table('assessments')->where('title', 'Pre-Assessment Kecocokan Karier SI')->value('id');
        $sqlId = DB::table('competencies')->where('name', 'SQL')->value('id');
        $scoreBefore = (float) DB::table('student_competencies')->where('student_id', $studentId)->where('competency_id', $sqlId)->value('score');

        $this->actingAs($user)->post('/assessments/'.$assessmentId.'/start');
        $attemptId = DB::table('assessment_attempts')->latest('id')->value('id');
        $answers = DB::table('assessment_questions')->where('assessment_id', $assessmentId)->get()
            ->mapWithKeys(fn ($question) => [$question->id => json_decode($question->answer_key, true)['correct']])
            ->all();

        $this->actingAs($user)
            ->post('/assessments/attempts/'.$attemptId, ['answers' => $answers])
            ->assertRedirect('/onboarding/career-role');

        $this->assertDatabaseHas('assessment_attempts', ['id' => $attemptId, 'status' => 'graded', 'score' => 100]);
        $this->assertSame(count($answers), DB::table('assessment_answers')->where('assessment_attempt_id', $attemptId)->count());
        $measuredCompetencies = DB::table('question_competencies as qc')->join('assessment_questions as aq', 'aq.id', '=', 'qc.assessment_question_id')->where('aq.assessment_id', $assessmentId)->distinct()->count('qc.competency_id');
        $this->assertSame($measuredCompetencies, DB::table('competency_evidences')->where('source_type', 'assessment_attempt')->where('source_id', $attemptId)->count());
        $this->assertGreaterThan($scoreBefore, (float) DB::table('student_competencies')->where('student_id', $studentId)->where('competency_id', $sqlId)->value('score'));
        $this->assertSame(5, DB::table('career_recommendations')->where('student_id', $studentId)->count());
        $explanation = json_decode((string) DB::table('career_recommendations')->where('student_id', $studentId)->latest('id')->value('explanation_snapshot'), true);
        $this->assertSame('verified_and_profile', $explanation['basis']);
        $this->assertTrue($explanation['ontology']['post_assessment_completed']);
        $this->assertContains('R07', $explanation['ontology']['fired_rules']);
        $this->assertSame(1, DB::table('career_learning_paths')->where('student_id', $studentId)->where('status', 'active')->count());
        $this->assertGreaterThan(0, DB::table('career_learning_path_items as i')->join('career_learning_paths as p', 'p.id', '=', 'i.learning_path_id')->where('p.student_id', $studentId)->where('p.status', 'active')->count());

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Profil dan bukti assessment')
            ->assertDontSee('Kesiapan belum diukur');
    }
    public function test_incomplete_submission_keeps_attempt_open(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $assessmentId = DB::table('assessments')->value('id');
        $this->actingAs($user)->post('/assessments/'.$assessmentId.'/start');
        $attemptId = DB::table('assessment_attempts')->latest('id')->value('id');
        $question = DB::table('assessment_questions')->where('assessment_id', $assessmentId)->first();

        $this->actingAs($user)
            ->from('/assessments/attempts/'.$attemptId)
            ->post('/assessments/attempts/'.$attemptId, ['answers' => [$question->id => 'a']])
            ->assertRedirect('/assessments/attempts/'.$attemptId)
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseHas('assessment_attempts', ['id' => $attemptId, 'status' => 'in_progress']);
        $this->assertDatabaseCount('assessment_answers', 0);
    }

    public function test_student_cannot_open_another_students_attempt(): void
    {
        $owner = User::where('role', 'student')->firstOrFail();
        $assessmentId = DB::table('assessments')->value('id');
        $this->actingAs($owner)->post('/assessments/'.$assessmentId.'/start');
        $attemptId = DB::table('assessment_attempts')->latest('id')->value('id');

        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        DB::table('students')->insert([
            'user_id' => $other->id,
            'nim' => '241011499999',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other)->get('/assessments/attempts/'.$attemptId)->assertForbidden();
        $this->actingAs($other)->postJson('/assessments/attempts/'.$attemptId.'/draft', ['answers' => [1 => 'a']])->assertForbidden();
    }

    public function test_draft_is_restored_without_grading_and_rejects_foreign_questions(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $assessmentId = DB::table('assessments')->value('id');
        $this->actingAs($user)->post('/assessments/'.$assessmentId.'/start');
        $attemptId = DB::table('assessment_attempts')->latest('id')->value('id');
        $question = DB::table('assessment_questions')->where('assessment_id', $assessmentId)->first();
        $selected = array_key_first(json_decode($question->options, true));
        $evidenceCount = DB::table('competency_evidences')->count();
        $this->postJson('/assessments/attempts/'.$attemptId.'/draft', ['answers' => [$question->id => $selected]])->assertOk();
        $this->assertDatabaseHas('assessment_answers', ['assessment_attempt_id' => $attemptId, 'assessment_question_id' => $question->id, 'score' => null]);
        $this->assertDatabaseHas('assessment_attempts', ['id' => $attemptId, 'status' => 'in_progress', 'score' => null]);
        $this->assertDatabaseCount('competency_evidences', $evidenceCount);
        $this->get('/assessments/attempts/'.$attemptId)->assertViewHas('draftAnswers', fn ($answers) => $answers[$question->id] === $selected);
        $foreign = DB::table('assessment_questions')->where('assessment_id', '!=', $assessmentId)->first();
        $this->postJson('/assessments/attempts/'.$attemptId.'/draft', ['answers' => [$foreign->id => 'a']])->assertUnprocessable();
        DB::table('assessment_attempts')->where('id', $attemptId)->update(['status' => 'graded']);
        $this->postJson('/assessments/attempts/'.$attemptId.'/draft', ['answers' => [$question->id => $selected]])->assertUnprocessable();
    }
}
