<?php

namespace App\Domains\Career\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

final class LearningPathService
{
    public function regenerate(int $studentId, int $roleId): int
    {
        return DB::transaction(function () use ($studentId, $roleId) {
            DB::table('career_learning_paths')->where('student_id', $studentId)->where('target_job_role_id', $roleId)->where('status', 'active')->update(['status' => 'superseded', 'updated_at' => now()]);
            $pathId = DB::table('career_learning_paths')->insertGetId(['student_id' => $studentId, 'target_job_role_id' => $roleId, 'status' => 'active', 'generated_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $levels = DB::table('student_competencies')->where('student_id', $studentId)->pluck('proficiency_level', 'competency_id');
            $requirements = DB::table('career_role_competencies')->where('job_role_id', $roleId)->orderByDesc('is_required')->orderByDesc('weight')->get();

            foreach ($requirements as $position => $requirement) {
                $current = (int) ($levels[$requirement->competency_id] ?? 0);
                $courseId = DB::table('course_competencies as cc')->join('courses as c', 'c.id', '=', 'cc.course_id')
                    ->where('cc.competency_id', $requirement->competency_id)->where('c.status', 'published')
                    ->orderByDesc('cc.contribution')->orderByDesc('cc.competency_gain')->orderBy('c.code')->value('c.id');
                $status = $current >= $requirement->minimum_level ? 'already_competent' : 'pending';
                DB::table('career_learning_path_items')->insert([
                    'learning_path_id' => $pathId, 'course_id' => $courseId, 'competency_id' => $requirement->competency_id,
                    'severity' => $status === 'already_competent' ? 'ready' : (($requirement->minimum_level - $current) >= 2 ? 'major_gap' : 'skill_gap'),
                    'is_mandatory' => (bool) $requirement->is_required, 'position' => $position + 1,
                    'is_completed' => $status === 'already_competent', 'status' => $status,
                ]);
            }
            return $pathId;
        });
    }

    public function applyProgressStatus(Collection $items): Collection
    {
        return $items->each(function ($item) {
            $progress = (int) ($item->enrollment_progress ?? 0);
            $item->display_status = match (true) {
                $item->enrollment_status === 'completed' || $progress >= 100 => 'completed',
                $progress > 0 || $item->enrollment_status === 'active' => 'in_progress',
                $item->status === 'already_competent' => 'already_competent',
                (bool) ($item->is_mandatory ?? false) => 'required',
                default => 'recommended',
            };
        });
    }
}
