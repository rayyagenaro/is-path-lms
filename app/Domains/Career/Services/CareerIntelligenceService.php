<?php

namespace App\Domains\Career\Services;

use App\Domains\Recommendation\Services\RecommendationEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CareerIntelligenceService
{
    public function __construct(private readonly RecommendationEngine $recommendationEngine) {}

    public function rankedRoles(int $studentId, array $scores): Collection
    {
        $levels = DB::table('student_competencies')->where('student_id', $studentId)->pluck('proficiency_level', 'competency_id')->all();

        $roles = DB::table('career_roles as cr')
            ->join('career_clusters as cc', 'cc.id', '=', 'cr.career_cluster_id')
            ->where('cr.is_active', true)
            ->select('cr.*', 'cc.name as cluster_name', 'cc.slug as cluster_slug')
            ->get();
        $requirementsByRole = $this->requirementsForRoles($roles->pluck('id'));
        $interestsByRole = DB::table('student_career_interests')
            ->where('student_id', $studentId)
            ->whereIn('career_role_id', $roles->pluck('id'))
            ->get()
            ->groupBy('career_role_id');

        return $roles->map(function ($role) use ($scores, $levels, $requirementsByRole, $interestsByRole) {
                $requirements = $requirementsByRole->get($role->id, collect());
                $match = $this->recommendationEngine->calculate($scores, $requirements->map(fn ($item) => (array) $item)->all());
                $readiness = $this->calculateReadiness($requirements, $levels);
                $role->score = $match['score'];
                $role->category = $match['category'];
                $role->details = $match['details'];
                $role->readiness = $readiness;
                $role->top_skills = $requirements->take(4)->pluck('name');
                $role->interest_type = $this->interestTypeFrom($interestsByRole->get($role->id, collect())->pluck('interest_type'));
                return $role;
            })->sortByDesc('score')->values();
    }

    public function explorationRoles(?string $primaryInterest = null): Collection
    {
        $roles = DB::table('career_roles as cr')
            ->join('career_clusters as cc', 'cc.id', '=', 'cr.career_cluster_id')
            ->where('cr.is_active', true)
            ->select('cr.*', 'cc.name as cluster_name', 'cc.slug as cluster_slug')
            ->orderByRaw('CASE WHEN cc.name = ? THEN 0 ELSE 1 END', [$primaryInterest ?? ''])
            ->orderBy('cc.display_order')
            ->orderBy('cr.name')
            ->get();
        $requirementsByRole = $this->requirementsForRoles($roles->pluck('id'));

        return $roles->each(function ($role) use ($requirementsByRole) {
            $role->top_skills = $requirementsByRole->get($role->id, collect())->take(4)->pluck('name');
            $role->interest_type = null;
        });
    }

    public function roleDetail(int $roleId, int $studentId, array $scores): object
    {
        $role = DB::table('career_roles as cr')->join('career_clusters as cc', 'cc.id', '=', 'cr.career_cluster_id')
            ->where('cr.id', $roleId)->select('cr.*', 'cc.name as cluster_name', 'cc.slug as cluster_slug')->first();
        abort_unless($role, 404);
        $requirements = $this->requirements($roleId);
        $levels = DB::table('student_competencies')->where('student_id', $studentId)->pluck('proficiency_level', 'competency_id')->all();
        $match = $this->recommendationEngine->calculate($scores, $requirements->map(fn ($item) => (array) $item)->all());
        $readiness = $this->calculateReadiness($requirements, $levels);
        $requirements->each(function ($item) use ($levels) {
            $item->student_level = (int) ($levels[$item->competency_id] ?? 0);
            $item->delta = $item->minimum_level - $item->student_level;
            $item->gap_status = $item->delta <= 0 ? 'ready' : ($item->delta === 1 ? 'skill_gap' : 'major_gap');
            $item->progress = min(100, round(($item->student_level / max(1, $item->minimum_level)) * 100));
            $item->reused = $item->student_level >= $item->minimum_level;
        });
        $gapIds = $requirements->where('delta', '>', 0)->pluck('competency_id');
        $courses = DB::table('courses as co')->join('course_competencies as map', 'map.course_id', '=', 'co.id')
            ->join('competencies as c', 'c.id', '=', 'map.competency_id')
            ->leftJoin('enrollments as e', fn ($join) => $join->on('e.course_id','=','co.id')->where('e.student_id','=',$studentId))
            ->whereIn('map.competency_id', $gapIds)->where('co.status', 'published')
            ->where(fn ($query) => $query->whereNull('e.id')->orWhere('e.status', '!=', 'completed')->orWhere('e.progress', '<', 100))
            ->select('co.*', 'c.name as closes_gap')->orderBy('co.code')->get()->unique('id')->values();
        $role->match = $match;
        $role->readiness = $readiness;
        $role->requirements = $requirements;
        $role->recommended_courses = $courses;
        $role->interest_type = $this->interestType($studentId, $roleId);
        $role->responsibilities = json_decode($role->responsibilities ?: '[]', true);
        $role->tools = json_decode($role->tools ?: '[]', true);
        return $role;
    }

    public function comparison(int $roleA, int $roleB, int $studentId): array
    {
        $roles = DB::table('career_roles')->whereIn('id', [$roleA, $roleB])->get()->keyBy('id');
        abort_unless($roles->count() === 2, 404);
        $a = $this->requirements($roleA)->keyBy('competency_id');
        $b = $this->requirements($roleB)->keyBy('competency_id');
        $sharedIds = $a->keys()->intersect($b->keys());
        $levels = DB::table('student_competencies')->where('student_id', $studentId)->pluck('proficiency_level', 'competency_id');
        $met = $sharedIds->filter(fn ($id) => ($levels[$id] ?? 0) >= min($a[$id]->minimum_level, $b[$id]->minimum_level))->count();
        return [
            'role_a' => $roles[$roleA], 'role_b' => $roles[$roleB],
            'shared' => $sharedIds->map(fn ($id) => $a[$id]->name)->values(),
            'unique_a' => $a->except($sharedIds)->pluck('name')->values(),
            'unique_b' => $b->except($sharedIds)->pluck('name')->values(),
            'transferability' => $sharedIds->count() ? (int) round($met / $sharedIds->count() * 100) : 0,
        ];
    }

    public function snapshotReadiness(int $studentId, int $roleId, array $readiness): void
    {
        DB::table('career_readiness_scores')->insert([
            'student_id' => $studentId, 'career_role_id' => $roleId, 'readiness_score' => $readiness['score'], 'status' => $readiness['status_key'],
            'has_blocking_gap' => $readiness['has_blocking_gap'], 'breakdown_snapshot' => json_encode($readiness['breakdown']), 'calculated_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function requirements(int $roleId): Collection
    {
        return DB::table('career_role_competencies as r')->join('competencies as c', 'c.id', '=', 'r.competency_id')
            ->where('r.job_role_id', $roleId)->select('r.*', 'c.name', 'c.category', 'c.category_group')->orderByDesc('r.weight')->get();
    }

    private function requirementsForRoles(Collection $roleIds): Collection
    {
        if ($roleIds->isEmpty()) return collect();

        return DB::table('career_role_competencies as r')
            ->join('competencies as c', 'c.id', '=', 'r.competency_id')
            ->whereIn('r.job_role_id', $roleIds)
            ->select('r.*', 'c.name', 'c.category', 'c.category_group')
            ->orderByDesc('r.weight')
            ->get()
            ->groupBy('job_role_id');
    }

    private function calculateReadiness(Collection $requirements, array $levels): array
    {
        $weighted = 0.0;
        $totalWeight = (float) $requirements->sum('weight');
        $blocking = false;
        $breakdown = [];
        foreach ($requirements as $item) {
            $current = (int) ($levels[$item->competency_id] ?? 0);
            $ratio = min($current / max(1, $item->minimum_level), 1);
            $weighted += $ratio * (float) $item->weight;
            $delta = $item->minimum_level - $current;
            $isBlocking = (bool) $item->is_required && $delta >= 2;
            $blocking = $blocking || $isBlocking;
            $breakdown[] = ['competency_id'=>(int)$item->competency_id,'name'=>$item->name,'current'=>$current,'required'=>(int)$item->minimum_level,'progress'=>(int)round($ratio*100),'blocking'=>$isBlocking];
        }
        $score = $totalWeight ? round($weighted / $totalWeight * 100, 1) : 0;
        [$key, $label] = match (true) {
            $score <= 25 => ['exploring', 'Exploring'],
            $score <= 50 => ['beginner', 'Beginner'],
            $score <= 75 => ['developing', 'Developing'],
            $score <= 90 => ['career_ready', 'Career Ready'],
            default => ['highly_ready', 'Highly Ready'],
        };
        if ($blocking && in_array($key, ['career_ready', 'highly_ready'])) [$key, $label] = ['developing', 'Developing'];
        return ['score'=>$score,'status_key'=>$key,'status'=>$label,'has_blocking_gap'=>$blocking,'breakdown'=>$breakdown];
    }

    private function interestType(int $studentId, int $roleId): ?string
    {
        return $this->interestTypeFrom(DB::table('student_career_interests')->where('student_id', $studentId)->where('career_role_id', $roleId)->pluck('interest_type'));
    }

    private function interestTypeFrom(Collection $types): ?string
    {
        if ($types->contains('target_active')) return 'target_active';
        if ($types->contains('explored')) return 'explored';
        return $types->first();
    }
}
