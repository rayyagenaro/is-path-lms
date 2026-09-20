@extends('layouts.app')

@section('title', $course->title)
@section('eyebrow', 'Detail Kelas')

@section('content')
@php
    $safeProgress = min(100, max(0, (int) $progress));
    $levelLabels = ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Lanjutan'];
    $moduleTypeLabels = ['video' => 'Video', 'article' => 'Bacaan', 'quiz' => 'Kuis', 'assessment' => 'Asesmen', 'project' => 'Praktik mandiri', 'lab' => 'Praktik'];
@endphp

<a class="back" href="{{ route('courses') }}">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="m15 18-6-6 6-6"/>
    </svg>
    <span>Kembali ke katalog kelas</span>
</a>

<section class="course-detail-hero" aria-labelledby="course-title">
    <div>
        <span class="tag light-tag">{{ $course->category }} · {{ $course->code }}</span>
        <h1 id="course-title">{{ $course->title }}</h1>
        <p>{{ $course->description }}</p>
        <div class="hero-meta" aria-label="Informasi kelas">
            <span>{{ round($course->duration_minutes / 60, 1) }} jam belajar</span>
            <span>{{ $levelLabels[strtolower($course->level)] ?? $course->level }}</span>
            <span>{{ $modules->count() }} modul</span>
        </div>
    </div>

    <div class="course-competency-box">
        <small>KOMPETENSI YANG DIKEMBANGKAN</small>
        @forelse($skills as $skill)
            <div>
                <strong>{{ $skill->name }}</strong>
                <span>Cakupan belajar: {{ $skill->competency_gain }} tingkat</span>
            </div>
        @empty
            <p class="muted">Pemetaan kompetensi untuk kelas ini belum tersedia.</p>
        @endforelse
    </div>
</section>

@if($prerequisites->isNotEmpty())
    <div class="prerequisite-note">
        <strong>Prasyarat</strong>
        @foreach($prerequisites as $item)
            <span>{{ $item->code }} · {{ $item->title }} {{ $item->is_required ? '' : '(disarankan)' }}</span>
        @endforeach
    </div>
@endif

<div class="course-layout">
    <section class="card module-card" aria-labelledby="module-list-title">
        <div class="card-head">
            <div>
                <span class="eyebrow">MODUL KELAS</span>
                <h2 id="module-list-title">Mulai dari fondasi, lanjut ke praktik</h2>
            </div>
            <span class="status success">{{ $modules->count() }} modul</span>
        </div>

        <div class="module-list">
            @forelse($modules as $module)
                @php
                    $isCompleted = $module->student_status === 'completed';
                    $moduleLabel = $isCompleted ? 'Selesai' : ($module->locked ? 'Terkunci' : 'Mulai');
                    $moduleClass = $isCompleted ? 'completed' : ($module->locked ? 'locked' : 'available');
                @endphp

                @if($module->locked)
                    <div class="module-row {{ $moduleClass }}" aria-label="Modul {{ $module->title }} terkunci">
                @else
                    <a class="module-row {{ $moduleClass }}" href="{{ route('courses.modules.show', [$course->slug, $module->id]) }}" aria-label="{{ $moduleLabel }} modul {{ $module->title }}">
                @endif
                        <span class="module-number" aria-hidden="true">{{ str_pad($module->order, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="module-copy">
                            <strong>{{ $module->title }}</strong>
                            <small>{{ $moduleTypeLabels[strtolower($module->type)] ?? ucwords(str_replace('_', ' ', $module->type)) }} · {{ $module->duration_minutes }} menit</small>
                        </span>
                        <span class="module-action {{ $moduleClass }}">
                            {{ $moduleLabel }}
                            @unless($module->locked)
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                            @endunless
                        </span>
                @if($module->locked)
                    </div>
                @else
                    </a>
                @endif
            @empty
                <div class="empty-inline">
                    <strong>Modul belum tersedia.</strong>
                    <p>Materi kelas sedang disiapkan. Kembali ke katalog kelas untuk memilih kelas lain.</p>
                    <a class="btn btn-outline" href="{{ route('courses') }}">Lihat kelas lain</a>
                </div>
            @endforelse
        </div>
    </section>

    <aside class="card sticky-card" aria-labelledby="course-progress-title">
        <span class="eyebrow">PROGRES KELAS</span>
        <div class="big-progress">
            <strong id="course-progress-title">{{ $safeProgress }}%</strong>
            <div
                class="progress"
                role="progressbar"
                aria-label="Progres kelas {{ $course->title }}"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="{{ $safeProgress }}"
            >
                <i class="progress-fill" style="--progress-scale: {{ $safeProgress / 100 }}"></i>
            </div>
        </div>
        <p>Progres dihitung dari modul dan assessment yang sudah selesai.</p>
        @php $continueModule = $modules->first(fn ($item) => $item->student_status !== 'completed' && !$item->locked) ?? $modules->first(); @endphp
        @if($continueModule)
            <a class="btn btn-primary btn-full course-cta" href="{{ route('courses.modules.show', [$course->slug, $continueModule->id]) }}" aria-describedby="module-action-note">
                <span>{{ $progress > 0 ? 'Lanjutkan belajar' : 'Mulai kelas' }}</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @endif
        <hr>
        <small id="module-action-note">Satu kelas dapat meningkatkan kesiapanmu untuk beberapa peran karier sekaligus.</small>
    </aside>
</div>
@endsection
