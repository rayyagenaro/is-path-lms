@extends('layouts.app')

@section('title', $current->title)
@section('eyebrow', $course->code.' · Modul '.$current->order)

@section('content')
@if(session('success'))
    <div class="success-banner" role="status">
        <div>
            <strong>Progres tersimpan</strong>
            <p>{{ session('success') }}</p>
        </div>
    </div>
@endif

<a class="back" href="{{ route('courses.show', $course->slug) }}">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="m15 18-6-6 6-6"/>
    </svg>
    <span>Kembali ke detail kelas</span>
</a>

<div class="course-layout">
    <article class="card assessment-workspace module-workspace" aria-labelledby="module-title">
        <span class="eyebrow">{{ strtoupper($course->title) }}</span>
        <h1 id="module-title">{{ $current->title }}</h1>
        <p class="muted">{{ $current->duration_minutes }} menit · {{ ucwords(str_replace('_', ' ', $current->type)) }}</p>
        <div class="module-content">
            @if(!$current->content_ref || $current->content_ref === 'Materi '.$current->title)
                @if($guide)
                    <p class="muted">Panduan belajar ringkas · {{ $guide['version'] }}</p>
                    <section aria-labelledby="module-concept">
                        <h2 id="module-concept">Konsep utama</h2>
                        <p>{{ $guide['summary'] }}</p>
                    </section>
                    <section aria-labelledby="module-practice">
                        <h2 id="module-practice">Latihan mandiri</h2>
                        <p>{{ $guide['exercise'] }}</p>
                    </section>
                    <p class="muted">Simpan hasil latihan di catatanmu. Panduan ini merupakan pengantar, bukan materi lengkap atau penilaian otomatis. Bukti kompetensi diperoleh melalui assessment.</p>
                @else
                <div class="blocking-note" role="note"><strong>Materi lengkap belum tersedia.</strong><span>Modul ini baru memuat judul dan urutan belajar. Penyelesaian dicatat secara mandiri, bukan sebagai bukti kelulusan assessment.</span></div>
                @endif
            @else
                <p>{{ $current->content_ref }}</p>
            @endif
        </div>
        <form method="POST" action="{{ route('courses.modules.complete', [$course->slug, $current->id]) }}" data-submit-loading>
            @csrf
            <button class="btn btn-primary module-complete-button" type="submit">
                <span>{{ $current->student_status === 'completed' ? 'Lanjutkan ke langkah berikutnya' : 'Catat selesai belajar mandiri' }}</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
            </button>
        </form>
    </article>

    <aside class="card sticky-card" aria-label="Daftar modul kelas">
        <span class="eyebrow">PROGRES KELAS</span>
        <div class="module-list compact-module-list">
            @foreach($modules as $item)
                @php
                    $isCompleted = $item->student_status === 'completed';
                    $itemClass = $item->id === $current->id ? 'active' : ($isCompleted ? 'completed' : ($item->locked ? 'locked' : 'available'));
                @endphp
                @if($item->locked)
                <div class="module-row {{ $itemClass }}">
                @else
                <a class="module-row {{ $itemClass }}" href="{{ route('courses.modules.show', [$course->slug, $item->id]) }}" @if($item->id === $current->id) aria-current="page" @endif>
                @endif
                    <span class="module-number">{{ str_pad($item->order, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="module-copy">
                        <strong>{{ $item->title }}</strong>
                        <small>{{ $isCompleted ? 'Selesai' : ($item->locked ? 'Terkunci' : 'Tersedia') }}</small>
                    </span>
                @if($item->locked)</div>@else</a>@endif
            @endforeach
        </div>
    </aside>
</div>
@endsection
