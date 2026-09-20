<?php

namespace App\Http\Controllers;

use App\Domains\Career\Services\CareerIntelligenceService;
use App\Domains\Career\Services\LearningPathService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(Request $request, CareerIntelligenceService $career): View
    {
        [$student, $profile, $scores] = $this->context($request);
        $hasPostAssessment = $this->hasPostAssessment($student->id);
        $roles = $hasPostAssessment
            ? $career->rankedRoles($student->id, $scores)
            : $career->explorationRoles($profile?->primary_interest);
        if ($request->filled('cluster')) $roles = $roles->where('cluster_slug', $request->string('cluster')->toString())->values();
        if ($request->filled('level')) $roles = $roles->where('career_level', $request->string('level')->toString())->values();
        if ($request->filled('status')) $roles = $roles->where('interest_type', $request->string('status')->toString())->values();
        if ($request->filled('q')) {
            $term = strtolower($request->string('q')->toString());
            $roles = $roles->filter(fn ($role) => str_contains(strtolower($role->name.' '.$role->description), $term))->values();
        }
        $roles = $this->paginateRoles($roles, $request);
        $clusters = DB::table('career_clusters')->orderBy('display_order')->get();
        $compareIds = collect(explode(',', (string) $request->query('compare')))->filter()->map(fn ($id) => (int) $id)->take(2);
        $comparison = $compareIds->count() === 2 ? $career->comparison($compareIds[0], $compareIds[1], $student->id) : null;
        $coverage = ['roles' => DB::table('career_roles')->where('is_active', true)->count(), 'courses' => DB::table('courses')->where('status', 'published')->count()];
        return view('careers.index', compact('roles', 'clusters', 'profile', 'comparison', 'hasPostAssessment', 'coverage'));
    }

    public function show(Request $request, string $slug, CareerIntelligenceService $career): View
    {
        [$student, $profile, $scores] = $this->context($request);
        $roleId = DB::table('career_roles')->where('slug', $slug)->value('id');
        abort_unless($roleId, 404);
        DB::table('student_career_interests')->updateOrInsert(['student_id'=>$student->id,'career_role_id'=>$roleId,'interest_type'=>'explored'], ['created_at'=>now(),'updated_at'=>now()]);
        $role = $career->roleDetail($roleId, $student->id, $scores);
        $career->snapshotReadiness($student->id, $roleId, $role->readiness);
        $path = DB::table('career_learning_paths')->where('student_id',$student->id)->where('target_job_role_id',$roleId)->where('status','active')->first();
        $pathItems = $path ? app(LearningPathService::class)->applyProgressStatus(DB::table('career_learning_path_items as i')->join('competencies as c','c.id','=','i.competency_id')->leftJoin('courses as co','co.id','=','i.course_id')->leftJoin('enrollments as e', fn ($join) => $join->on('e.course_id','=','i.course_id')->where('e.student_id','=',$student->id))->where('i.learning_path_id',$path->id)->select('i.*','c.name as competency_name','co.code as course_code','co.title as course_title','co.slug as course_slug','e.status as enrollment_status','e.progress as enrollment_progress')->orderBy('i.position')->get()) : collect();
        $hasPostAssessment = $this->hasPostAssessment($student->id);
        return view('careers.show', compact('role', 'profile', 'pathItems', 'hasPostAssessment'));
    }

    public function target(Request $request, string $slug, LearningPathService $learningPath): RedirectResponse
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first();
        abort_unless($student, 403);
        if (!$this->hasPostAssessment($student->id)) {
            return back()->with('error', 'Selesaikan post-assessment sebelum menetapkan target karier dan membuat learning path personal.');
        }
        $role = DB::table('career_roles')->where('slug', $slug)->first();
        abort_unless($role, 404);
        DB::transaction(function () use ($student, $role, $learningPath) {
            $previous = DB::table('student_career_interests')->where('student_id',$student->id)->where('interest_type','target_active')->where('career_role_id','!=',$role->id)->pluck('career_role_id');
            foreach ($previous as $previousRoleId) {
                DB::table('student_career_interests')->updateOrInsert(['student_id'=>$student->id,'career_role_id'=>$previousRoleId,'interest_type'=>'target_past'],['created_at'=>now(),'updated_at'=>now()]);
            }
            DB::table('student_career_interests')->where('student_id',$student->id)->where('interest_type','target_active')->where('career_role_id','!=',$role->id)->delete();
            DB::table('student_career_interests')->updateOrInsert(['student_id'=>$student->id,'career_role_id'=>$role->id,'interest_type'=>'target_active'],['created_at'=>now(),'updated_at'=>now()]);
            DB::table('students')->where('id',$student->id)->update(['target_job_role_id'=>$role->id,'updated_at'=>now()]);
            $learningPath->regenerate($student->id, $role->id);
            DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'target_selected','auditable_type'=>'career_role','auditable_id'=>$role->id,'new_values'=>json_encode(['career_role'=>$role->name]),'created_at'=>now()]);
        });
        return back()->with('success', "{$role->name} sekarang menjadi target karier utamamu.");
    }

    private function context(Request $request): array
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first();
        abort_unless($student, 403);
        $profile = DB::table('career_profiles')->where('student_id', $student->id)->first();
        $scores = DB::table('student_competencies')->where('student_id', $student->id)->pluck('score', 'competency_id')->all();
        if ($profile) foreach (DB::table('career_profile_strengths')->where('career_profile_id',$profile->id)->pluck('self_rating','competency_id') as $id=>$rating) {
            $scores[$id] = array_key_exists($id, $scores) ? round($scores[$id] * .8 + $rating * .2, 1) : (float) $rating;
        }
        return [$student, $profile, $scores];
    }

    private function hasPostAssessment(int $studentId): bool
    {
        return DB::table('assessment_attempts as aa')
            ->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)
            ->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])
            ->exists();
    }

    private function paginateRoles($roles, Request $request): LengthAwarePaginator
    {
        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $roles->values();

        return (new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        ))->withQueryString();
    }
}
