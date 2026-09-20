<?php

namespace App\Http\Controllers\Api;

use App\Domains\Career\Services\CareerIntelligenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareerController extends Controller
{
    public function clusters(): JsonResponse
    {
        $clusters=DB::table('career_clusters')->orderBy('display_order')->get();
        foreach ($clusters as $cluster) $cluster->roles=DB::table('career_roles')->where('career_cluster_id',$cluster->id)->where('is_active',true)->select('id','name','slug','career_level','description')->get();
        return response()->json(['data'=>$clusters]);
    }

    public function show(Request $request,string $slug,CareerIntelligenceService $career): JsonResponse
    {
        [$student,$scores]=$this->studentContext($request);
        $id=DB::table('career_roles')->where('slug',$slug)->value('id');
        abort_unless($id,404);
        if (!$this->hasPostAssessment($student->id)) {
            return response()->json(['data' => DB::table('career_roles')->where('id', $id)->first(), 'meta' => ['recommendation_status' => 'locked_until_post_assessment']]);
        }
        return response()->json(['data'=>$career->roleDetail($id,$student->id,$scores)]);
    }

    public function readiness(Request $request,string $slug,CareerIntelligenceService $career): JsonResponse
    {
        [$student,$scores]=$this->studentContext($request);
        $id=DB::table('career_roles')->where('slug',$slug)->value('id');
        abort_unless($id,404);
        if (!$this->hasPostAssessment($student->id)) return response()->json([
            'data' => null,
            'meta' => ['status' => 'locked_until_post_assessment', 'message' => 'Kesiapan karier tersedia setelah post-assessment.'],
        ], 409);
        $role=$career->roleDetail($id,$student->id,$scores);
        return response()->json(['data'=>['career_role'=>$role->name,'readiness'=>$role->readiness,'skill_gap'=>$role->requirements]]);
    }

    public function courses(): JsonResponse
    {
        return response()->json(['data'=>DB::table('courses')->where('status','published')->orderBy('code')->get()]);
    }

    public function modules(int $course): JsonResponse
    {
        return response()->json(['data'=>DB::table('modules')->where('course_id',$course)->orderBy('order')->get()]);
    }

    private function studentContext(Request $request): array
    {
        $student=DB::table('students')->where('user_id',$request->user()->id)->first();
        abort_unless($student,403);
        return [$student,DB::table('student_competencies')->where('student_id',$student->id)->pluck('score','competency_id')->all()];
    }

    private function hasPostAssessment(int $studentId): bool
    {
        return DB::table('assessment_attempts as aa')->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $studentId)->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])->exists();
    }
}
