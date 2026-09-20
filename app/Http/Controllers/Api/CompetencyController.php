<?php
namespace App\Http\Controllers\Api;

use App\Domains\Recommendation\Services\RecommendationEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompetencyController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first(); abort_unless($student, 404);
        $items = DB::table('student_competencies as sc')->join('competencies as c','c.id','=','sc.competency_id')
            ->where('student_id',$student->id)->select('c.code','c.name','c.category','sc.score','sc.confidence_score','sc.proficiency_level')->get();
        return response()->json(['data'=>$items]);
    }

    public function recommendations(Request $request, RecommendationEngine $engine): JsonResponse
    {
        $student = DB::table('students')->where('user_id', $request->user()->id)->first(); abort_unless($student, 404);
        $hasPostAssessment = DB::table('assessment_attempts as aa')->join('assessments as a', 'a.id', '=', 'aa.assessment_id')
            ->where('aa.student_id', $student->id)->where('aa.status', 'graded')
            ->whereIn('a.assessment_purpose', ['career_diagnostic', 'competency_post_assessment', 'role_competency_assessment'])->exists();
        if (!$hasPostAssessment) return response()->json([
            'data' => [],
            'meta' => ['status' => 'locked_until_post_assessment', 'message' => 'Selesaikan post-assessment untuk membuka rekomendasi karier.'],
        ], 409);
        $scores = DB::table('student_competencies')->where('student_id',$student->id)->pluck('score','competency_id')->all();
        $data = DB::table('career_roles')->where('is_active',true)->get()->map(function($job) use($scores,$engine){
            $requirements=DB::table('career_role_competencies as r')->join('competencies as c','c.id','=','r.competency_id')->where('job_role_id',$job->id)->select('r.*','c.name')->get()->map(fn($r)=>(array)$r)->all();
            return ['career_role'=>$job,'match'=>$engine->calculate($scores,$requirements)];
        })->sortByDesc('match.score')->values();
        return response()->json(['data'=>$data,'meta'=>['engine'=>'weighted-score + mandatory-gating','deterministic'=>true]]);
    }
}
