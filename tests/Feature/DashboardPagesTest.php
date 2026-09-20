<?php
namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_experience_pages_render(): void
    {
        $student = User::where('role', 'student')->firstOrFail();
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('PROFIL MINAT')->assertSee('Kesiapan belum diukur');
        $this->actingAs($student)->get('/courses')->assertOk()->assertSee('Pilih kelas yang paling dekat');
        $this->actingAs($student)->get('/courses/sql-relational-database')->assertOk()->assertSee('SQL &amp; Relational Database', false)->assertSee('10 modul');
        $this->actingAs($student)->get('/careers')->assertOk()->assertSee('Business Analyst')->assertSee('22 peran ditemukan')->assertSee('Menampilkan 1-8 dari 22 peran')->assertDontSee('Tampilkan pilihan lainnya');
        $this->actingAs($student)->get('/careers/data-analyst')->assertOk()->assertSee('Kesiapan karier')->assertSee('Assessment mengubah penilaian awal');
        $this->actingAs($student)->get('/competencies')->assertOk()->assertSee('Profil berbasis bukti');
        $this->actingAs($student)->get('/career-profile')->assertOk()->assertSee('Peta kekuatan');
        $this->actingAs($student)->get('/assessments')->assertOk()->assertSee('Assessment untukmu');
    }

    public function test_every_catalog_module_has_a_specific_learning_guide(): void
    {
        $guides = app(\App\Domains\Career\Services\ModuleGuide::class);
        $modules = DB::table('modules as m')->join('courses as c', 'c.id', '=', 'm.course_id')
            ->select('c.code', 'm.title')->get();
        $this->assertCount(232, $modules);
        foreach ($modules as $module) {
            $guide = $guides->find($module->code, $module->title);
            $this->assertNotNull($guide, $module->code.': '.$module->title);
            $this->assertNotEmpty($guide['summary']);
            $this->assertNotEmpty($guide['exercise']);
        }
        $this->assertNull($guides->find('C99', 'Unlisted module'));
    }

    public function test_career_exploration_uses_pagination_instead_of_load_more(): void
    {
        $student = User::where('role', 'student')->firstOrFail();

        $this->actingAs($student)
            ->get('/careers?page=2')
            ->assertOk()
            ->assertSee('Menampilkan 9-16 dari 22 peran')
            ->assertSee('Sebelumnya')
            ->assertSee('Berikutnya')
            ->assertDontSee('Tampilkan pilihan lainnya');
    }

    public function test_student_can_update_interest_profile_without_running_final_recommendations(): void
    {
        $student = User::where('role', 'student')->firstOrFail();
        $competencies = \Illuminate\Support\Facades\DB::table('competencies')->pluck('id');
        $response = $this->actingAs($student)->post('/career-profile', [
            'primary_interest' => 'Business & Product', 'work_style' => 'collaborative',
            'career_goal' => 'Menjadi Business Analyst.', 'strengths' => $competencies->mapWithKeys(fn ($id) => [$id => 70])->all(),
        ]);
        $response->assertRedirect('/onboarding/career-role');
        $this->assertDatabaseHas('career_profiles', ['primary_interest' => 'Business & Product', 'work_style' => 'collaborative']);
    }

    public function test_seeded_application_has_one_student_account(): void
    {
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'rani@ispath.id', 'role' => 'student']);
        $this->assertDatabaseCount('lecturers', 0);
        $this->get('/guest')->assertOk()->assertSee('Mode guest')->assertSee('Data tidak dapat dibuka atau diubah');
    }

    public function test_non_student_account_cannot_access_the_application(): void
    {
        $legacyUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($legacyUser)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_student_can_register_and_is_saved_to_database(): void
    {
        $response = $this->post('/register', [
            'name' => 'Dimas Prakoso',
            'email' => 'dimas@kampus.ac.id',
            'nim' => '241011400287',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'password' => 'belajar123',
            'password_confirmation' => 'belajar123',
        ]);

        $response->assertRedirect('/onboarding/career-role');
        $this->assertAuthenticated();
        $response->assertSessionHas('show_preassessment_prompt', true);
        $this->assertDatabaseHas('users', ['email' => 'dimas@kampus.ac.id', 'role' => 'student']);
        $this->assertDatabaseHas('students', ['nim' => '241011400287', 'study_program' => 'Sistem Informasi']);
    }

    public function test_new_students_cannot_skip_pre_assessment_by_opening_profile_directly(): void
    {
        $this->post('/register', [
            'name' => 'Nadia Putri',
            'email' => 'nadia@kampus.ac.id',
            'nim' => '241011400288',
            'cohort_year' => 2024,
            'study_program' => 'Sistem Informasi',
            'password' => 'belajar123',
            'password_confirmation' => 'belajar123',
        ]);

        $this->get('/career-profile')->assertRedirect('/onboarding/career-role');
        $this->get('/courses')->assertRedirect('/onboarding/career-role');
        $this->assertDatabaseMissing('career_profiles', ['student_id' => DB::table('students')->where('nim', '241011400288')->value('id')]);
    }

    public function test_student_can_open_and_complete_an_ordered_course_module(): void
    {
        $student = User::where('role', 'student')->firstOrFail();
        $course = DB::table('courses')->where('code', 'C03')->first();
        $module = DB::table('modules')->where('course_id', $course->id)->orderBy('order')->first();

        $this->actingAs($student)->get("/courses/{$course->slug}/modules/{$module->id}")->assertOk()->assertSee($module->title);
        $this->actingAs($student)->post("/courses/{$course->slug}/modules/{$module->id}/complete")->assertRedirect();
        $studentId = DB::table('students')->where('user_id', $student->id)->value('id');
        $this->assertDatabaseHas('student_module_progress', ['student_id' => $studentId, 'module_id' => $module->id, 'status' => 'completed']);
    }

    public function test_prd_v2_master_data_is_complete(): void
    {
        $this->assertDatabaseCount('career_clusters', 8);
        $this->assertDatabaseCount('career_roles', 22);
        $this->assertDatabaseCount('courses', 26);
        $this->assertDatabaseCount('modules', 232);
        $this->assertDatabaseCount('competencies', 40);
    }

    public function test_student_can_choose_a_new_target_career(): void
    {
        $student = User::where('role','student')->firstOrFail();
        $studentId = \Illuminate\Support\Facades\DB::table('students')->where('user_id',$student->id)->value('id');
        $assessmentId = DB::table('assessments')->value('id');
        DB::table('assessment_attempts')->insert([
            'assessment_id' => $assessmentId, 'student_id' => $studentId, 'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'graded', 'attempt_number' => 1, 'score' => 75, 'started_at' => now(), 'submitted_at' => now(), 'graded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($student)->post('/careers/business-analyst/target')->assertRedirect();
        $roleId = \Illuminate\Support\Facades\DB::table('career_roles')->where('slug','business-analyst')->value('id');
        $this->assertDatabaseHas('student_career_interests',['student_id'=>$studentId,'career_role_id'=>$roleId,'interest_type'=>'target_active']);
        $pathId = \Illuminate\Support\Facades\DB::table('career_learning_paths')->where('student_id',$studentId)->where('target_job_role_id',$roleId)->where('status','active')->value('id');
        $this->assertNotNull($pathId);
        $this->assertGreaterThan(0, \Illuminate\Support\Facades\DB::table('career_learning_path_items')->where('learning_path_id',$pathId)->count());
    }

    public function test_learning_path_uses_course_progress_for_display_status(): void
    {
        $student = User::where('role','student')->firstOrFail();
        $studentId = DB::table('students')->where('user_id',$student->id)->value('id');
        $assessmentId = DB::table('assessments')->value('id');
        DB::table('assessment_attempts')->insert([
            'assessment_id' => $assessmentId, 'student_id' => $studentId, 'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'graded', 'attempt_number' => 1, 'score' => 75, 'started_at' => now(), 'submitted_at' => now(), 'graded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($student)->post('/careers/business-analyst/target')->assertRedirect();
        $roleId = DB::table('career_roles')->where('slug','business-analyst')->value('id');
        $pathId = DB::table('career_learning_paths')->where('student_id',$studentId)->where('target_job_role_id',$roleId)->where('status','active')->value('id');
        $courseId = DB::table('career_learning_path_items')->where('learning_path_id',$pathId)->whereNotNull('course_id')->value('course_id');

        DB::table('enrollments')->updateOrInsert(
            ['student_id' => $studentId, 'course_id' => $courseId],
            ['status' => 'completed', 'progress' => 100, 'last_activity_at' => now(), 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        $this->actingAs($student)->get('/careers/business-analyst')->assertOk()->assertSee('Kelas selesai')->assertSee('Selesai dari progres kelas');
    }

    public function test_critical_v2_api_endpoints_return_data(): void
    {
        $student = User::where('role','student')->firstOrFail();
        $this->actingAs($student,'sanctum')->getJson('/api/v1/career-clusters')->assertOk()->assertJsonCount(8,'data');
        $this->actingAs($student,'sanctum')->getJson('/api/v1/career-roles/data-analyst/readiness')->assertConflict()->assertJsonPath('meta.status','locked_until_post_assessment');
        $this->actingAs($student,'sanctum')->getJson('/api/v1/recommendations/me')->assertConflict()->assertJsonPath('meta.status','locked_until_post_assessment');
        $this->actingAs($student,'sanctum')->getJson('/api/v1/courses')->assertOk()->assertJsonCount(26,'data');
        $this->actingAs($student,'sanctum')->getJson('/api/v1/ontology/graph')->assertOk()->assertJsonPath('data.implementation','RDF/OWL ontology + PostgreSQL runtime graph')->assertJsonPath('data.ontology.enabled_rule_count',24);
    }
}
