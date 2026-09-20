<?php

namespace App\Console\Commands;

use App\Domains\Assessment\Services\RoleQuestionBank;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishRoleAssessmentV2 extends Command
{
    protected $signature = 'assessment:publish-role-v2 {--dry-run : Show the planned revision without writing data}';
    protected $description = 'Publish competency-specific role assessments without changing historical attempts';

    public function handle(RoleQuestionBank $bank): int
    {
        $roles = DB::table('career_roles as r')->join('career_clusters as c', 'c.id', '=', 'r.career_cluster_id')
            ->where('r.is_active', true)->select('r.*', 'c.name as cluster_name')->orderBy('r.name')->get();
        $requirements = DB::table('career_role_competencies as crc')
            ->join('competencies as c', 'c.id', '=', 'crc.competency_id')
            ->select('crc.*', 'c.name as competency_name')
            ->orderByDesc('crc.is_required')->orderByDesc('crc.weight')->get()->groupBy('job_role_id');

        $questionCount = $roles->sum(fn ($role) => $requirements->get($role->id, collect())->count());
        $this->info("Revision v2: {$roles->count()} assessment, {$questionCount} pertanyaan spesifik.");
        if ($this->option('dry-run')) return self::SUCCESS;

        DB::transaction(function () use ($roles, $requirements, $bank) {
            foreach ($roles as $role) {
                $title = "Assessment Kompetensi {$role->name} · v2";
                $title = $this->titleFor($role->cluster_name, $role->name);
                $assessmentId = null;
                $assessmentId ??= DB::table('assessments')->where('assessment_scope', 'career_role')
                    ->where('assessment_purpose', 'role_competency_assessment')->where('scope_reference_id', $role->id)
                    ->where('title', 'like', '%v2%')->value('id');
                $assessmentId ??= DB::table('assessments as a')->where('a.assessment_scope', 'career_role')
                    ->where('a.assessment_purpose', 'role_competency_assessment')->where('a.scope_reference_id', $role->id)
                    ->whereExists(fn ($query) => $query->selectRaw('1')->from('assessment_questions as q')
                        ->whereColumn('q.assessment_id', 'a.id')->where('q.prompt', 'not like', 'SECTION -%'))
                    ->value('a.id');
                if (!$assessmentId) {
                    $items = $requirements->get($role->id, collect());
                    $mandatory = $items->where('requirement_type', 'mandatory');
                    $passingScore = min(85, max(65, (int) round(($mandatory->isNotEmpty() ? $mandatory->avg('minimum_level') * 20 : 70))));
                    $assessmentId = DB::table('assessments')->insertGetId([
                        'course_id'=>null, 'lesson_id'=>null, 'title'=>$title, 'type'=>'exam',
                        'passing_score'=>$passingScore, 'max_attempts'=>3, 'time_limit_minutes'=>null,
                        'attempt_strategy'=>'best', 'is_published'=>true, 'assessment_scope'=>'career_role',
                        'assessment_purpose'=>'role_competency_assessment', 'scope_reference_id'=>$role->id,
                        'opens_at'=>null, 'closes_at'=>null, 'created_at'=>now(), 'updated_at'=>now(),
                    ]);
                    foreach ($items->values() as $position => $requirement) {
                        $item = $bank->for($requirement->competency_name);
                        $questionId = DB::table('assessment_questions')->insertGetId([
                            'assessment_id'=>$assessmentId, 'type'=>'single_choice', 'prompt'=>$item['prompt'],
                            'options'=>json_encode($item['options']),
                            'answer_key'=>json_encode(['correct'=>$item['correct'], 'explanation'=>$item['explanation']]),
                            'rubric'=>null, 'max_score'=>$requirement->requirement_type === 'mandatory' ? 1.5 : 1,
                            'position'=>$position + 1, 'measures_competency'=>true,
                            'created_at'=>now(), 'updated_at'=>now(),
                        ]);
                        DB::table('question_competencies')->insert([
                            'assessment_question_id'=>$questionId, 'competency_id'=>$requirement->competency_id, 'weight'=>100,
                        ]);
                    }
                } else {
                    DB::table('assessments')->where('id', $assessmentId)->update(['title'=>$title, 'is_published'=>true, 'updated_at'=>now()]);
                }

                DB::table('assessments')->where('assessment_scope', 'career_role')
                    ->where('assessment_purpose', 'role_competency_assessment')->where('scope_reference_id', $role->id)
                    ->where('id', '!=', $assessmentId)->update(['is_published'=>false, 'updated_at'=>now()]);
            }
        });

        $this->info('Assessment v2 aktif. Attempt dan hasil versi sebelumnya tetap tersimpan.');
        return self::SUCCESS;
    }

    private function titleFor(string $cluster, string $role): string
    {
        return "Pemetaan Topik {$cluster}";
    }
}
