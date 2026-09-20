<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_new_student_sees_pre_assessment_intro_before_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        DB::table('students')->insert([
            'user_id' => $user->id,
            'nim' => '241011477777',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/onboarding/career-role');
        $this->actingAs($user)->get('/onboarding/career-role')
            ->assertOk()
            ->assertSee('Petakan kemampuan awal sebelum memilih kelas.')
            ->assertSee('Mulai pre-assessment');
    }

    public function test_login_shows_pre_assessment_prompt_for_incomplete_student(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'email' => 'baru@kampus.ac.id',
            'password' => bcrypt('belajar123'),
        ]);
        DB::table('students')->insert([
            'user_id' => $user->id,
            'nim' => '241011466666',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', ['email' => 'baru@kampus.ac.id', 'password' => 'belajar123'])
            ->assertRedirect('/onboarding/career-role')
            ->assertSessionHas('show_preassessment_prompt', true);

        $this->get('/onboarding/career-role')
            ->assertOk()
            ->assertSee('Selesaikan pre-assessment terlebih dahulu.');
    }

    public function test_student_starts_initial_assessment_from_intro(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $user->id,
            'nim' => '241011455555',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/onboarding/initial-assessment');
        $attempt = DB::table('assessment_attempts')->where('student_id', $studentId)->first();

        $response->assertRedirect('/assessments/attempts/'.$attempt->id);
        $this->assertSame('in_progress', $attempt->status);
    }

    public function test_role_choice_starts_mandatory_assessment(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $user->id,
            'nim' => '241011488888',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('career_roles')->where('name', 'Data Analyst')->value('id');
        $initialAssessmentId = DB::table('assessments')->where('assessment_purpose', 'career_diagnostic')->value('id');
        DB::table('assessment_attempts')->insert([
            'assessment_id' => $initialAssessmentId,
            'student_id' => $studentId,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'graded',
            'attempt_number' => 1,
            'score' => 75,
            'started_at' => now(),
            'submitted_at' => now(),
            'graded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/onboarding/career-role', ['career_role_id' => $roleId]);
        $attempt = DB::table('assessment_attempts')->where('student_id', $studentId)->latest('id')->first();

        $response->assertRedirect('/assessments/attempts/'.$attempt->id);
        $this->assertDatabaseHas('students', ['id' => $studentId, 'target_job_role_id' => $roleId]);
        $this->assertDatabaseHas('student_career_interests', ['student_id' => $studentId, 'career_role_id' => $roleId, 'interest_type' => 'target_active']);
    }

    public function test_student_can_save_up_to_three_roles_with_alternatives(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $studentId = DB::table('students')->insertGetId([
            'user_id'=>$user->id, 'nim'=>'241011499999', 'cohort_year'=>2024, 'study_program'=>'Sistem Informasi', 'created_at'=>now(), 'updated_at'=>now(),
        ]);
        $initialAssessmentId = DB::table('assessments')->where('assessment_purpose', 'career_diagnostic')->value('id');
        DB::table('assessment_attempts')->insert([
            'assessment_id'=>$initialAssessmentId, 'student_id'=>$studentId, 'idempotency_key'=>(string) \Illuminate\Support\Str::uuid(), 'status'=>'graded', 'attempt_number'=>1, 'score'=>75, 'started_at'=>now(), 'submitted_at'=>now(), 'graded_at'=>now(), 'created_at'=>now(), 'updated_at'=>now(),
        ]);
        $roles = DB::table('career_roles')->orderBy('id')->limit(4)->pluck('id')->all();

        $this->actingAs($user)->post('/onboarding/career-role', ['career_role_ids'=>array_slice($roles, 0, 3)])
            ->assertRedirect();
        $this->assertDatabaseHas('students', ['id'=>$studentId, 'target_job_role_id'=>$roles[0]]);
        $this->assertDatabaseHas('student_career_interests', ['student_id'=>$studentId, 'career_role_id'=>$roles[1], 'interest_type'=>'explored']);
        $this->assertDatabaseHas('student_career_interests', ['student_id'=>$studentId, 'career_role_id'=>$roles[2], 'interest_type'=>'explored']);

        $this->actingAs($user)->post('/onboarding/career-role', ['career_role_ids'=>$roles])
            ->assertSessionHasErrors('career_role_ids');
    }
}
