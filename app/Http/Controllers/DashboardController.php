<?php
namespace App\Http\Controllers;

use App\Domains\Career\Services\CareerIntelligenceService;
use App\Domains\Career\Services\LearningPathService;
use App\Domains\Recommendation\Services\RecommendationEngine;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CareerIntelligenceService $career): View
    {
        return $this->student($request, $career);
    }
    private function student(Request $request, CareerIntelligenceService $career): View
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first();
        $competencies = DB::table('student_competencies as sc')->join('competencies as c', 'c.id', '=', 'sc.competency_id')
            ->where('sc.student_id', $student->id)->select('c.id', 'c.name', 'c.category', 'sc.score', 'sc.confidence_score', 'sc.proficiency_level')->orderByDesc('sc.score')->get();
        [$profile, $scores] = $this->profileScores($student->id, $competencies->pluck('score', 'id')->all());
        $jobs = $career->rankedRoles($student->id, $scores);
        $target = $jobs->firstWhere('interest_type', 'target_active') ?? $jobs->first();
        $readiness = $target?->readiness;
        $hasPostAssessment = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $student->id)
            ->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])
            ->exists();
        $hasPreAssessment = DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $student->id)->where('aa.status', 'graded')
            ->where('a.assessment_purpose', 'career_diagnostic')->exists();
        $enrollments = DB::table('enrollments as e')->join('courses as c', 'c.id', '=', 'e.course_id')->where('e.student_id', $student->id)
            ->select('c.*', 'e.progress', 'e.status as enrollment_status')->get();
        $topCourses = $hasPreAssessment ? DB::table('courses as c')
            ->join('course_competencies as cc', 'cc.course_id', '=', 'c.id')
            ->leftJoin('enrollments as e', fn ($join) => $join->on('e.course_id', '=', 'c.id')->where('e.student_id', $student->id))
            ->where('c.status', 'published')->where(fn ($query) => $query->whereNull('e.id')->orWhere('e.status', '!=', 'completed'))
            ->select('c.*')->selectRaw('COUNT(DISTINCT cc.competency_id) as competency_count')
            ->groupBy('c.id')->orderByDesc('competency_count')->orderBy('c.code')->limit(4)->get() : collect();
        $learningItems = DB::table('career_learning_path_items as i')->join('career_learning_paths as p', 'p.id', '=', 'i.learning_path_id')
            ->join('competencies as c', 'c.id', '=', 'i.competency_id')->leftJoin('courses as co', 'co.id', '=', 'i.course_id')
            ->leftJoin('enrollments as e', fn ($join) => $join->on('e.course_id','=','i.course_id')->where('e.student_id','=',$student->id))
            ->where('p.student_id', $student->id)->where('p.status', 'active')->select('i.*', 'c.name as competency_name', 'co.title as course_title', 'e.status as enrollment_status', 'e.progress as enrollment_progress')->orderBy('position')->get();
        $learningItems = app(LearningPathService::class)->applyProgressStatus($learningItems);
        $activeEnrollments = $enrollments->where('enrollment_status', 'active');
        $journey = match (true) {
            !$profile => ['title' => 'Lengkapi profil awal', 'detail' => 'Ceritakan minat, gaya kerja, dan kekuatanmu.', 'url' => route('career-profile'), 'action' => 'Isi profil karier'],
            !$hasPostAssessment && !$student->target_job_role_id => ['title' => 'Pilih role yang paling cocok', 'detail' => 'Profilmu sudah siap. Tentukan role utama sebelum memulai assessment.', 'url' => route('onboarding.index'), 'action' => 'Lihat rekomendasi role'],
            !$hasPostAssessment && $enrollments->isEmpty() => ['title' => 'Mulai dari learning path', 'detail' => 'Pilih kelas pembuka untuk membangun bukti kompetensi sesuai targetmu.', 'url' => route('courses'), 'action' => 'Pilih kelas pembuka'],
            !$hasPostAssessment && $activeEnrollments->isNotEmpty() => ['title' => $activeEnrollments->first()->title, 'detail' => 'Lanjutkan pembelajaran sebelum mengukur kompetensi melalui post-assessment.', 'url' => route('courses.show', $activeEnrollments->first()->slug), 'action' => 'Lanjutkan belajar'],
            !$hasPostAssessment => ['title' => 'Ukur kompetensi inti', 'detail' => 'Pembelajaran awal selesai. Ukur kompetensimu untuk membuka rekomendasi karier.', 'url' => route('assessments.index'), 'action' => 'Mulai assessment'],
            $learningItems->isNotEmpty() => ['title' => $learningItems->firstWhere('display_status', 'in_progress')->course_title ?? $learningItems->whereIn('display_status', ['required', 'recommended'])->first()?->course_title ?? 'Tinjau jalur belajarmu', 'detail' => $learningItems->whereIn('display_status', ['required', 'recommended', 'in_progress'])->count().' langkah aktif di jalur belajarmu.', 'url' => route('careers.show', $target->slug).'#learning-path', 'action' => 'Buka jalur belajar'],
            default => ['title' => 'Pilih target karier', 'detail' => 'Tetapkan satu peran agar IS-Path dapat menyusun urutan belajar.', 'url' => route('careers'), 'action' => 'Pilih target karier'],
        };
        return view('dashboard.student', compact('student', 'competencies', 'jobs', 'enrollments', 'learningItems', 'profile', 'target', 'readiness', 'hasPostAssessment', 'hasPreAssessment', 'topCourses', 'journey'));
    }
    private function profileScores(int $studentId, array $evidenceScores): array
    {
        $profile = DB::table('career_profiles')->where('student_id', $studentId)->first();
        if (!$profile) return [null, $evidenceScores];
        $strengths = DB::table('career_profile_strengths')->where('career_profile_id', $profile->id)->pluck('self_rating', 'competency_id');
        foreach ($strengths as $competencyId => $rating) {
            $evidenceScores[$competencyId] = array_key_exists($competencyId, $evidenceScores)
                ? round($evidenceScores[$competencyId] * .8 + $rating * .2, 1)
                : (float) $rating;
        }
        return [$profile, $evidenceScores];
    }
    public function courses(Request $request): View
    {
        $query = DB::table('courses')->where('status', 'published');
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%')->orWhere('code', 'like', '%'.$request->string('q').'%'));
        if ($request->filled('category')) $query->where('category', $request->string('category'));
        $courses = $query->orderBy('code')->get();
        $categories = DB::table('courses')->where('status','published')->distinct()->orderBy('category')->pluck('category');
        return view('courses.index', compact('courses','categories'));
    }
    public function course(string $slug): View
    {
        $course = DB::table('courses')->where('slug', $slug)->first(); abort_unless($course, 404);
        $student = DB::table('students')->where('user_id', auth()->id())->first();
        $moduleQuery = DB::table('modules as m')->when($student, fn ($query) => $query->leftJoin('student_module_progress as smp', fn ($join) => $join->on('smp.module_id','=','m.id')->where('smp.student_id','=',$student->id)))
            ->where('m.course_id', $course->id)->select('m.*');
        $moduleQuery->addSelect(DB::raw($student ? "COALESCE(smp.status, 'not_started') as student_status" : "'not_started' as student_status"));
        $modules = $moduleQuery->orderBy('m.order')->get();
        if ($student) {
            $completedIds = DB::table('student_module_progress')->where('student_id', $student->id)->where('status', 'completed')->pluck('module_id');
            $prerequisiteMap = DB::table('module_prerequisites')->whereIn('module_id', $modules->pluck('id'))->get()->groupBy('module_id');
            $modules->each(function ($module) use ($completedIds, $prerequisiteMap) {
                $module->locked = $prerequisiteMap->get($module->id, collect())->contains(fn ($item) => !$completedIds->contains($item->prerequisite_module_id));
            });
        } else {
            $modules->each(fn ($module) => $module->locked = false);
        }
        $skills = DB::table('course_competencies as cc')->join('competencies as c', 'c.id', '=', 'cc.competency_id')->where('cc.course_id', $course->id)->get();
        $prerequisites = DB::table('course_prerequisites as cp')->join('courses as c','c.id','=','cp.prerequisite_course_id')->where('cp.course_id',$course->id)->select('c.code','c.title','cp.is_required')->get();
        $progress = $student ? (int) (DB::table('enrollments')->where('student_id',$student->id)->where('course_id',$course->id)->value('progress') ?? 0) : 0;
        return view('courses.show', compact('course', 'modules', 'skills', 'prerequisites', 'progress'));
    }

    public function module(Request $request, string $slug, int $module): View
    {
        [$student, $course, $current, $modules] = $this->moduleContext($request, $slug, $module);
        abort_if($current->locked, 403, 'Selesaikan modul sebelumnya terlebih dahulu.');
        DB::table('enrollments')->updateOrInsert(
            ['student_id' => $student->id, 'course_id' => $course->id],
            ['status' => $modules->every(fn ($item) => $item->student_status === 'completed') ? 'completed' : 'active', 'last_activity_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('student_module_progress')->updateOrInsert(
            ['student_id' => $student->id, 'module_id' => $current->id],
            ['status' => $current->student_status === 'completed' ? 'completed' : 'in_progress', 'started_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
        $next = $modules->first(fn ($item) => $item->order > $current->order && !$item->locked);
        $guide = app(\App\Domains\Career\Services\ModuleGuide::class)->find($course->code, $current->title);
        return view('courses.module', compact('course', 'current', 'modules', 'next', 'guide'));
    }

    public function completeModule(Request $request, string $slug, int $module): RedirectResponse
    {
        [$student, $course, $current, $modules] = $this->moduleContext($request, $slug, $module);
        abort_if($current->locked, 403, 'Selesaikan modul sebelumnya terlebih dahulu.');
        DB::transaction(function () use ($student, $course, $current, $modules) {
            DB::table('student_module_progress')->updateOrInsert(
                ['student_id' => $student->id, 'module_id' => $current->id],
                ['status' => 'completed', 'started_at' => now(), 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
            $completed = DB::table('student_module_progress')->where('student_id', $student->id)->whereIn('module_id', $modules->pluck('id'))->where('status', 'completed')->count();
            $progress = $modules->count() ? (int) round($completed / $modules->count() * 100) : 0;
            DB::table('enrollments')->updateOrInsert(
                ['student_id' => $student->id, 'course_id' => $course->id],
                ['status' => $progress === 100 ? 'completed' : 'active', 'progress' => $progress, 'last_activity_at' => now(), 'completed_at' => $progress === 100 ? now() : null, 'created_at' => now(), 'updated_at' => now()]
            );
        });
        $next = $modules->first(fn ($item) => $item->order > $current->order);
        return $next
            ? redirect()->route('courses.modules.show', [$course->slug, $next->id])->with('success', 'Modul selesai. Lanjutkan ke langkah berikutnya.')
            : redirect()->route('courses.show', $course->slug)->with('success', 'Semua modul kelas ini telah selesai.');
    }

    private function moduleContext(Request $request, string $slug, int $moduleId): array
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first();
        abort_unless($student, 403);
        $course = DB::table('courses')->where('slug', $slug)->first();
        abort_unless($course, 404);
        $modules = DB::table('modules')->where('course_id', $course->id)->orderBy('order')->get();
        $current = $modules->firstWhere('id', $moduleId);
        abort_unless($current, 404);
        $progress = DB::table('student_module_progress')->where('student_id', $student->id)->whereIn('module_id', $modules->pluck('id'))->get()->keyBy('module_id');
        $prerequisites = DB::table('module_prerequisites')->whereIn('module_id', $modules->pluck('id'))->get()->groupBy('module_id');
        $completedIds = $progress->where('status', 'completed')->keys();
        $modules->each(function ($item) use ($progress, $prerequisites, $completedIds) {
            $item->student_status = $progress->get($item->id)->status ?? 'not_started';
            $item->locked = $prerequisites->get($item->id, collect())->contains(fn ($relation) => !$completedIds->contains($relation->prerequisite_module_id));
        });
        return [$student, $course, $modules->firstWhere('id', $moduleId), $modules];
    }
    public function careers(Request $request, RecommendationEngine $engine): View
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first(); abort_unless($student, 403);
        $evidenceScores = DB::table('student_competencies')->where('student_id', $student->id)->pluck('score', 'competency_id')->all();
        [$profile, $scores] = $this->profileScores($student->id, $evidenceScores);
        $jobs = app(CareerIntelligenceService::class)->rankedRoles($student->id, $scores); return view('careers.index', compact('jobs', 'profile'));
    }
    public function careerProfile(Request $request): View
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first(); abort_unless($student, 403);
        $profile = DB::table('career_profiles')->where('student_id', $student->id)->first();
        $ratings = $profile ? DB::table('career_profile_strengths')->where('career_profile_id', $profile->id)->pluck('self_rating', 'competency_id') : collect();
        $competencies = DB::table('competencies')->where('is_active', true)->orderBy('category_group')->orderBy('category')->orderBy('name')->get();
        return view('profile.career', compact('profile', 'ratings', 'competencies'));
    }
    public function updateCareerProfile(Request $request): RedirectResponse
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first(); abort_unless($student, 403);
        $data = $request->validate([
            'primary_interest' => ['required', 'string', 'max:100'], 'work_style' => ['required', 'in:analytical,collaborative,creative,structured'],
            'career_goal' => ['nullable', 'string', 'max:500'], 'strengths' => ['sometimes', 'array'], 'strengths.*' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $ratingIds = array_keys($data['strengths'] ?? []);
        if (DB::table('competencies')->where('is_active', true)->whereIn('id', $ratingIds)->count() !== count($ratingIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['strengths' => 'Daftar kompetensi berubah. Muat ulang profil dan periksa pilihanmu.']);
        }
        DB::transaction(function () use ($student, $data) {
            DB::table('career_profiles')->updateOrInsert(['student_id' => $student->id], [
                'primary_interest' => $data['primary_interest'], 'work_style' => $data['work_style'], 'career_goal' => $data['career_goal'] ?? null,
                'completed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $profileId = DB::table('career_profiles')->where('student_id', $student->id)->value('id');
            foreach ($data['strengths'] ?? [] as $competencyId => $rating) DB::table('career_profile_strengths')->updateOrInsert(
                ['career_profile_id' => $profileId, 'competency_id' => $competencyId], ['self_rating' => $rating, 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('audit_logs')->insert(['user_id' => auth()->id(), 'action' => 'updated', 'auditable_type' => 'career_profile', 'auditable_id' => $profileId, 'new_values' => json_encode(['profile_based_matching' => true]), 'created_at' => now()]);
        });
        return redirect()->route('onboarding.index')->with('success', 'Profil tersimpan. Pilih role yang paling dekat dengan arahmu untuk membuka assessment.');
    }
    public function profile(Request $request): View
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first(); abort_unless($student, 403);
        $competencies = DB::table('student_competencies as sc')->join('competencies as c', 'c.id', '=', 'sc.competency_id')->where('student_id', $student->id)
            ->select('sc.*', 'c.name', 'c.category', 'c.category_group')->orderBy('c.category_group')->orderByDesc('score')->get();
        return view('competencies.index', compact('competencies'));
    }
}
