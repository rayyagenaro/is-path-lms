<?php

namespace App\Http\Controllers\Api;

use App\Domains\Ontology\Services\OntologyRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OntologyController extends Controller
{
    public function graph(Request $request, OntologyRepository $ontology): JsonResponse
    {
        $student=DB::table('students')->where('user_id',$request->user()->id)->first();
        abort_unless($student,403);
        $roleId=(int)($request->query('career_role_id') ?: DB::table('students')->where('id',$student->id)->value('target_job_role_id'));
        $studentEdges=DB::table('student_competencies as sc')->join('competencies as c','c.id','=','sc.competency_id')->where('sc.student_id',$student->id)->select('c.code as target','c.name','sc.proficiency_level as level')->get()->map(fn($item)=>['subject'=>'student:'.$student->id,'predicate'=>'HAS_COMPETENCY','object'=>'competency:'.$item->target,'label'=>$item->name,'level'=>(int)$item->level]);
        $roleEdges=DB::table('career_role_competencies as crc')->join('career_roles as cr','cr.id','=','crc.job_role_id')->join('competencies as c','c.id','=','crc.competency_id')->where('cr.id',$roleId)->select('cr.slug','c.code','c.name','crc.minimum_level','crc.weight')->get()->map(fn($item)=>['subject'=>'career:'.$item->slug,'predicate'=>'REQUIRES','object'=>'competency:'.$item->code,'label'=>$item->name,'required_level'=>(int)$item->minimum_level,'weight'=>(float)$item->weight]);
        $courseEdges=DB::table('course_competencies as cc')->join('courses as co','co.id','=','cc.course_id')->join('competencies as c','c.id','=','cc.competency_id')->select('co.code as course_code','c.code as competency_code','c.name')->get()->map(fn($item)=>['subject'=>'course:'.$item->course_code,'predicate'=>'TEACHES','object'=>'competency:'.$item->competency_code,'label'=>$item->name]);
        try {
            $ontologySummary = $ontology->summary();
            unset($ontologySummary['source'], $ontologySummary['rules']);
        } catch (\Throwable $exception) {
            $ontologySummary = ['status'=>'unavailable','message'=>$exception->getMessage()];
        }
        return response()->json(['data'=>['implementation'=>'RDF/OWL ontology + PostgreSQL runtime graph','ontology'=>$ontologySummary,'career_role_id'=>$roleId,'predicates'=>['HAS_COMPETENCY','REQUIRES','TEACHES','HAS_MODULE','PREREQUISITE_OF','PROVES'],'edges'=>$studentEdges->concat($roleEdges)->concat($courseEdges)->values()]]);
    }
}
