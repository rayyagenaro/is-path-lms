@extends('layouts.app')

@section('title', 'Ringkasan Belajar')
@section('eyebrow', 'Ringkasan Belajar')

@section('content')
@php
    $readinessLabels = [
        'exploring' => 'Mulai menjelajah',
        'beginner' => 'Tahap awal',
        'developing' => 'Sedang berkembang',
        'career_ready' => 'Siap berkarier',
        'highly_ready' => 'Sangat siap',
    ];
    $activeEnrollments = $enrollments->where('enrollment_status', 'active');
    $completedEnrollments = $enrollments->where('enrollment_status', 'completed');
    $priorityGaps = $hasPostAssessment
        ? collect($target->readiness['breakdown'] ?? [])->filter(fn ($gap) => $gap['progress'] < 100)->take(4)
        : collect();
@endphp

<header class="dashboard-hero">
    <div>
        <h1>Halo, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p>Lanjutkan dari langkah berikutnya untuk mendekati target kariermu.</p>
    </div>
    <a href="{{ route('careers') }}" class="btn btn-outline">
        Eksplorasi karier
    </a>
</header>

<section class="target-overview" aria-labelledby="target-title">
    <div class="target-copy">
        <span class="eyebrow">{{ $hasPostAssessment ? 'TARGET KARIER' : 'PROFIL MINAT' }}</span>
        <h2 id="target-title">{{ $hasPostAssessment ? ($target->name ?? 'Tentukan arah pertamamu') : ($profile->primary_interest ?? 'Kenali arah belajarmu') }}</h2>
        <p>{{ $hasPostAssessment ? ($target->description ?? 'Jelajahi pilihan karier untuk menemukan peran yang paling selaras dengan kekuatanmu.') : 'Gunakan arah ini untuk mengeksplorasi kelas. Rekomendasi karier dibuka setelah post-assessment.' }}</p>
        @if($hasPostAssessment && $target)
            <a href="{{ route('careers.show', $target->slug) }}">
                Lihat detail target
            </a>
        @endif
    </div>

    <div class="readiness-orbit" style="--score: {{ $hasPostAssessment ? ($readiness['score'] ?? 0) : 0 }}" role="img" aria-label="{{ $hasPostAssessment ? 'Kesiapan '.($readiness['score'] ?? 0).' persen' : 'Kesiapan belum diukur' }}">
        <div>
            <small>Kesiapan</small>
            <strong>{{ $hasPostAssessment ? ($readiness['score'] ?? 0).'%' : 'Belum' }}</strong>
            <span>{{ $hasPostAssessment ? ($readinessLabels[$readiness['status_key'] ?? ''] ?? 'Mulai menjelajah') : 'Belum diukur' }}</span>
        </div>
    </div>

    <div class="next-focus">
        <small>Fokus berikutnya</small>
        <strong>{{ $journey['title'] }}</strong>
        <span>{{ $journey['detail'] }}</span>
        <a class="btn btn-primary" href="{{ $journey['url'] }}">
            {{ $journey['action'] }}
        </a>
    </div>
</section>

<section class="metric-grid dashboard-metrics" aria-label="Ringkasan progres">
    <div class="metric-card">
        <div><span>Kompetensi terukur</span><small>Dari {{ $competencyTotal }} kompetensi aktif</small></div>
        <strong>{{ $competencies->count() }}</strong>
    </div>
    <div class="metric-card">
        <div><span>Keandalan bukti</span><small>Berdasarkan jumlah &amp; variasi bukti</small></div>
        <strong>{{ round($competencies->avg('confidence_score')) }}<i>%</i></strong>
    </div>
    <div class="metric-card">
        <div><span>Pembelajaran aktif</span><small>{{ $completedEnrollments->count() }} kelas sudah selesai</small></div>
        <strong>{{ $activeEnrollments->count() }}</strong>
    </div>
    <div class="metric-card">
        <div><span>{{ $hasPostAssessment ? 'Kecocokan karier' : 'Rekomendasi karier' }}</span><small>{{ $hasPostAssessment ? 'Profil dan bukti assessment' : 'Terbuka setelah post-assessment' }}</small></div>
        <strong>{{ $hasPostAssessment ? ($target->score ?? 0) : 'Belum' }}@if($hasPostAssessment)<i>%</i>@endif</strong>
    </div>
</section>

@if($hasPreAssessment && $topCourses->isNotEmpty() && $activeEnrollments->isEmpty())
    <section class="card dashboard-top-courses" aria-labelledby="top-courses-title">
        <div class="card-head"><div><span class="eyebrow">Setelah pre-assessment</span><h2 id="top-courses-title">Kelas untuk dieksplorasi</h2></div><a href="{{ route('courses') }}">Lihat semua kelas</a></div>
        <div class="course-list">
            @foreach($topCourses as $course)
                <a href="{{ route('courses.show', $course->slug) }}" class="course-row"><div class="course-code-tile">{{ $course->code }}</div><div class="course-info"><span class="tag">{{ $course->category }}</span><h3>{{ $course->title }}</h3><small>Mencakup {{ $course->competency_count }} kompetensi</small></div><span class="row-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></span></a>
            @endforeach
        </div>
    </section>
@endif

<div class="dashboard-grid">
    <section class="card dashboard-learning" aria-labelledby="active-learning-title">
        <div class="card-head">
            <div>
                <span class="eyebrow">Pembelajaran aktif</span>
                <h2 id="active-learning-title">Lanjutkan kelasmu</h2>
            </div>
            <a href="{{ route('courses') }}">Lihat katalog</a>
        </div>

        <div class="course-list">
            @forelse($activeEnrollments as $course)
                <a href="{{ route('courses.show', $course->slug) }}" class="course-row">
                    <div class="course-code-tile">{{ $course->code }}</div>
                    <div class="course-info">
                        <span class="tag">{{ $course->category }}</span>
                        <h3>{{ $course->title }}</h3>
                        <div class="inline-progress">
                            <div class="progress" role="progressbar" aria-label="Progres {{ $course->title }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $course->progress }}">
                                <i style="width: {{ $course->progress }}%"></i>
                            </div>
                            <small>{{ $course->progress }}%</small>
                        </div>
                    </div>
                    <span class="row-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg>
                    </span>
                </a>
            @empty
                <div class="empty-inline">
                    <strong>Belum ada kelas aktif.</strong>
                    <p>Pilih kelas yang mendukung target kariermu.</p>
                    <a class="btn btn-outline" href="{{ route('courses') }}">Pilih kelas pertama</a>
                </div>
            @endforelse
        </div>
    </section>

    <aside class="dashboard-aside" aria-label="Prioritas belajar">
        <section class="card" aria-labelledby="gap-title">
            <div class="card-head">
                <div>
                    <span class="eyebrow">Gap kompetensi</span>
                    <h2 id="gap-title">Prioritas penguatan</h2>
                </div>
            </div>
            <div class="focus-list">
                @forelse($priorityGaps as $gap)
                    @php $gapProgress = min(100, max(0, ($gap['current'] / max(1, $gap['required'])) * 100)); @endphp
                    <div>
                        <div class="focus-heading"><span>{{ $gap['name'] }}</span><strong>{{ $gap['current'] }}/{{ $gap['required'] }}</strong></div>
                        <div class="progress" role="progressbar" aria-label="Penguasaan {{ $gap['name'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($gapProgress) }}"><i style="width: {{ $gapProgress }}%"></i></div>
                        <small>{{ $gap['blocking'] ? 'Kebutuhan utama' : 'Kompetensi pendukung' }}</small>
                    </div>
                @empty
                    <div class="empty-inline">
                        @if($hasPostAssessment)
                            <strong>{{ $target && !empty($target->readiness['breakdown']) ? 'Tidak ada gap pada kebutuhan yang sudah dipetakan.' : 'Peta kebutuhan belum tersedia.' }}</strong>
                            <p>Lihat detail target karier untuk memeriksa cakupan kompetensi dan langkah berikutnya.</p>
                        @else
                            <strong>Gap belum dapat dihitung.</strong>
                            <p>Selesaikan assessment agar prioritas penguatan berasal dari bukti kemampuanmu.</p>
                            <a class="btn btn-outline" href="{{ route('assessments.index') }}">Buka assessment</a>
                        @endif
                    </div>
                @endforelse
            </div>
        </section>
    </aside>

    <section class="card dashboard-competencies" aria-labelledby="strength-title">
        <div class="card-head">
            <div>
                <span class="eyebrow">Profil kompetensi</span>
                <h2 id="strength-title">Kekuatan yang sudah terukur</h2>
            </div>
            <a href="{{ route('competencies') }}">Lihat profil lengkap</a>
        </div>
        <div class="skill-list">
            @forelse($competencies->take(6) as $skill)
                <div class="skill-row">
                    <div class="skill-name"><span>{{ $skill->name }}</span><small>{{ $skill->category }}</small></div>
                    <div class="progress" role="progressbar" aria-label="Penguasaan {{ $skill->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $skill->score }}"><i style="width: {{ $skill->score }}%"></i></div>
                    <strong title="Tingkat {{ $skill->proficiency_level }}/5">{{ round($skill->score) }}%</strong>
                    @if($skill->confidence_score >= 70)
                        <span class="confidence">Bukti kuat</span>
                    @elseif($skill->confidence_score >= 50)
                        <span class="confidence">Bukti cukup</span>
                    @else
                        <a class="btn btn-outline btn-small" href="{{ route('competencies.evidence', $skill->id) }}" aria-label="Lihat bukti {{ $skill->name }}">Lihat bukti</a>
                    @endif
                </div>
            @empty
                <div class="empty-inline">
                    <strong>Kompetensimu belum terukur.</strong>
                    <p>Selesaikan assessment untuk mulai membangun profil.</p>
                    <a class="btn btn-primary" href="{{ route('assessments.index') }}">Mulai assessment</a>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
