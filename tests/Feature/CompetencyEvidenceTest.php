<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\AssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompetencyEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_evidence_page_only_displays_the_authenticated_students_evidence(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $student = DB::table('students')->where('user_id', $user->id)->first();
        $skill = DB::table('student_competencies')->where('student_id', $student->id)->first();
        $ownerEvidence = DB::table('competency_evidences')->where('student_competency_id', $skill->id)->first();
        DB::table('competency_evidences')->where('id', $ownerEvidence->id)->update(['weight' => 27.5]);
        $otherUser = User::factory()->create(['role' => 'student']);
        $other = DB::table('students')->insertGetId(['user_id' => $otherUser->id, 'nim' => 'evidence-private', 'cohort_year' => 2024, 'study_program' => 'Sistem Informasi', 'created_at' => now(), 'updated_at' => now()]);
        $otherSkill = DB::table('student_competencies')->insertGetId(['student_id' => $other, 'competency_id' => $skill->competency_id, 'score' => 10, 'confidence_score' => 10, 'proficiency_level' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('competency_evidences')->insert(['student_competency_id' => $otherSkill, 'evidence_type' => 'technical_assessment', 'score' => 10, 'weight' => 93.7, 'earned_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($user)->get(route('competencies.evidence', $skill->competency_id))
            ->assertOk()->assertSee('27.5')->assertDontSee('93.7')->assertSee('Cara menghitung nilai');
        $this->actingAs($user)->get('/competencies/999999/evidence')->assertNotFound();
    }

    public function test_project_reference_is_validated_and_does_not_award_unreviewed_evidence(): void
    {
        $user = User::where('role', 'student')->firstOrFail();
        $studentId = DB::table('students')->where('user_id', $user->id)->value('id');
        $mapping = DB::table('project_competencies')->first();
        $url = route('competencies.projects.store', [$mapping->competency_id, $mapping->project_id]);
        $before = DB::table('competency_evidences')->count();
        $this->actingAs($user)->post($url, ['submission_ref' => 'javascript:alert(1)', 'student_note' => 'Catatan'])
            ->assertSessionHasErrors('submission_ref');
        $this->actingAs($user)->post($url, ['submission_ref' => 'https://example.com/my-project', 'student_note' => 'Kontribusi saya'])
            ->assertRedirect(route('competencies.evidence', $mapping->competency_id));
        $this->assertDatabaseHas('project_submissions', ['student_id' => $studentId, 'project_id' => $mapping->project_id, 'status' => 'submitted', 'student_note' => 'Kontribusi saya']);
        $this->assertSame($before, DB::table('competency_evidences')->count());
        $this->actingAs($user)->post(route('competencies.projects.store', [999999, $mapping->project_id]), [])
            ->assertNotFound();
    }

    public function test_topic_titles_do_not_collapse_distinct_role_assessments_on_reseed(): void
    {
        $before = DB::table('assessments')->where('assessment_scope', 'career_role')->count();
        $this->seed(AssessmentSeeder::class);
        $this->assertSame($before, DB::table('assessments')->where('assessment_scope', 'career_role')->count());
        foreach (DB::table('career_roles')->where('is_active', true)->pluck('id') as $role) {
            $this->assertDatabaseHas('assessments', ['assessment_scope' => 'career_role', 'scope_reference_id' => $role, 'is_published' => true]);
        }
    }
}
