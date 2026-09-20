@extends('layouts.app')

@section('title', 'Profil Kompetensi')
@section('eyebrow', 'Profil Kompetensi')

@section('content')
<section class="page-intro" aria-labelledby="competency-title">
    <span class="eyebrow">Profil berbasis bukti</span>
    <h1 id="competency-title">Kemampuanmu, terlihat jelas.</h1>
    <p>Lihat penguasaan dari hasil asesmen dan bukti yang tercatat. Buka sumber bukti untuk memahami nilai dan menentukan langkah belajar berikutnya.</p>
</section>

<div class="profile-summary" aria-label="Ringkasan profil kompetensi">
    <div><span>Skor rata-rata</span><strong>{{ round($competencies->avg('score') ?? 0) }}</strong><small>dari 100</small></div>
    <div><span>Kekuatan bukti</span><strong>{{ round($competencies->avg('confidence_score') ?? 0) }}%</strong><small>Jumlah, variasi, dan kebaruan hasil</small></div>
    <div><span>Kompetensi terukur</span><strong>{{ $competencies->count() }}</strong><small>dari {{ $competencyTotal }} kompetensi aktif</small></div>
</div>

@forelse($competencies->groupBy('category_group') as $group => $items)
    @php
        $groupNames = ['Technical' => 'Teknis', 'Business' => 'Bisnis', 'Professional' => 'Profesional'];
        $groupLabel = $groupNames[$group] ?? $group;
    @endphp
    <section class="card competency-group" aria-labelledby="competency-group-{{ $loop->index }}">
        <div class="card-head">
            <div><span class="eyebrow">Kompetensi {{ strtolower($groupLabel) }}</span><h2 id="competency-group-{{ $loop->index }}">Kemampuan {{ strtolower($groupLabel) }}</h2></div>
            <span class="muted">{{ $items->count() }} terukur</span>
        </div>
        <div class="competency-cards">
            @foreach($items as $skill)
                <article>
                    <div class="competency-title">
                        <div class="score-orb" aria-label="Tingkat {{ $skill->proficiency_level }} dari 5">{{ $skill->proficiency_level }}</div>
                        <div><h3>{{ $skill->name }}</h3><span>{{ $skill->category }}</span></div>
                        <b>Tingkat {{ $skill->proficiency_level }}/5</b>
                    </div>
                    <div class="dual-bars">
                        <label>Penguasaan <span>{{ $skill->score }}%</span></label>
                        <div class="progress" role="progressbar" aria-label="Penguasaan {{ $skill->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $skill->score }}"><i class="progress-fill is-filled" data-progress="{{ $skill->score }}" style="--progress-scale: {{ min(1, max(0, (float) $skill->score / 100)) }}"></i></div>
                        <label>Kekuatan bukti <span>{{ $skill->confidence_score }}%</span></label>
                        <div class="progress confidence-bar" role="progressbar" aria-label="Kekuatan bukti {{ $skill->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $skill->confidence_score }}"><i class="progress-fill is-filled" data-progress="{{ $skill->confidence_score }}" style="--progress-scale: {{ min(1, max(0, (float) $skill->confidence_score / 100)) }}"></i></div>
                    </div>
                    <a class="btn btn-outline btn-small" href="{{ route('competencies.evidence', $skill->competency_id) }}" aria-label="Lihat sumber bukti {{ $skill->name }}">Lihat sumber bukti</a>
                </article>
            @endforeach
        </div>
    </section>
@empty
    <div class="card empty-state">
        <strong>Belum ada kompetensi yang terukur.</strong>
        <p>Selesaikan asesmen pertama agar profil kompetensimu mulai terbentuk.</p>
        <a class="btn btn-primary" href="{{ route('assessments.index') }}">Mulai assessment</a>
    </div>
@endforelse
@endsection
